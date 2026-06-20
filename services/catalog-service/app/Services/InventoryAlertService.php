<?php

namespace App\Services;

// Comentario de mantenimiento: Este servicio concentra reglas de negocio para que los controladores no dupliquen lógica.

use App\Models\InventoryAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Agrupa reglas de negocio reutilizables para mantener controladores delgados.
 */
class InventoryAlertService
{
    private const STATUS_OUT = 'out';
    private const STATUS_RESOLVED = 'resolved';
    private const EMAIL_INITIAL = 'initial';
    private const EMAIL_REMINDER = 'reminder';

    /**
     * Actualiza el estado de alerta de una variante según disponibilidad real de stock.
     */

    public function syncVariantState(int $variantId, int $availableStock, bool $allowInitialEmail = true): void
    {
        if ($variantId <= 0 || !Schema::hasTable('inventory_alerts')) {
            return;
        }

        $normalizedStock = max(0, $availableStock);
        $alert = InventoryAlert::query()->firstOrNew(['variant_id' => $variantId]);

        if (!$alert->exists && $normalizedStock > 0) {
            return;
        }

        $context = $this->resolveVariantContext($variantId);
        if ($context === null) {
            return;
        }

        $this->fillAlertSnapshot($alert, $context, $normalizedStock);

        if ($normalizedStock <= 0) {
            $wasOut = $alert->status === self::STATUS_OUT;
            $alert->status = self::STATUS_OUT;
            $alert->out_of_stock_since = $wasOut
                ? ($alert->out_of_stock_since ?: now())
                : now();
            $alert->resolved_at = null;

            if (!$wasOut) {
                $alert->last_initial_notification_at = null;
                $alert->last_reminder_at = null;
            }

            $alert->save();

            if ($allowInitialEmail && $this->shouldSendInitialEmail() && $alert->last_initial_notification_at === null) {
                $sentAt = now();
                if ($this->sendAlertEmail($alert, self::EMAIL_INITIAL)) {
                    $alert->forceFill([
                        'last_initial_notification_at' => $sentAt,
                    ])->save();
                }
            }

            return;
        }

        if (!$alert->exists) {
            return;
        }

        $wasOut = $alert->status === self::STATUS_OUT;
        $alert->status = self::STATUS_RESOLVED;
        if ($wasOut) {
            $alert->resolved_at = now();
        }

        $alert->save();
    }

    /**
     * Explica la intención de reconcileCurrentInventory dentro del flujo del servicio.
     */

    public function reconcileCurrentInventory(bool $allowInitialEmails = true): array
    {
        if (!Schema::hasTable('product_size_variants')) {
            return [
                'scanned' => 0,
                'out_of_stock' => 0,
            ];
        }

        $stockColumn = $this->firstExistingColumn('product_size_variants', ['stock', 'quantity']);
        if ($stockColumn === null) {
            return [
                'scanned' => 0,
                'out_of_stock' => 0,
            ];
        }

        $rows = DB::table('product_size_variants')
            ->select('id', DB::raw("COALESCE({$stockColumn}, 0) as stock"))
            ->get();

        $scanned = 0;
        $outOfStock = 0;

        foreach ($rows as $row) {
            $variantId = (int) ($row->id ?? 0);
            if ($variantId <= 0) {
                continue;
            }

            $scanned++;
            $stock = max(0, (int) ($row->stock ?? 0));
            if ($stock <= 0) {
                $outOfStock++;
            }

            $this->syncVariantState($variantId, $stock, $allowInitialEmails);
        }

        return [
            'scanned' => $scanned,
            'out_of_stock' => $outOfStock,
        ];
    }

    /**
     * Explica la intención de dispatchReminderEmails dentro del flujo del servicio.
     */

    public function dispatchReminderEmails(bool $dryRun = false): array
    {
        if (!Schema::hasTable('inventory_alerts')) {
            return [
                'total_out_of_stock' => 0,
                'due' => 0,
                'sent' => 0,
                'items' => [],
            ];
        }

        $now = now();
        $reminderAfterDays = max(1, (int) config('inventory.reminder_after_days', 3));
        $reminderEveryDays = max(1, (int) config('inventory.reminder_every_days', $reminderAfterDays));

        $totalOutOfStock = InventoryAlert::query()
            ->where('status', self::STATUS_OUT)
            ->count();

        $dueAlerts = InventoryAlert::query()
            ->where('status', self::STATUS_OUT)
            ->whereNotNull('out_of_stock_since')
            ->where('out_of_stock_since', '<=', $now->copy()->subDays($reminderAfterDays))
            ->where(function ($query) use ($now, $reminderEveryDays): void {
                $query->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<=', $now->copy()->subDays($reminderEveryDays));
            })
            ->orderBy('out_of_stock_since')
            ->get();

        $items = [];
        $sent = 0;

        foreach ($dueAlerts as $candidate) {
            if (!$candidate instanceof InventoryAlert) {
                continue;
            }

            $alert = $candidate;
            $items[] = [
                'variant_id' => (int) $alert->variant_id,
                'label' => $this->formatVariantLabel($alert),
                'product_name' => (string) ($alert->product_name ?? 'Producto'),
            ];

            if ($dryRun) {
                continue;
            }

            $sentAt = now();
            if ($this->sendAlertEmail($alert, self::EMAIL_REMINDER)) {
                $alert->forceFill([
                    'last_reminder_at' => $sentAt,
                ])->save();
                $sent++;
            }
        }

        return [
            'total_out_of_stock' => $totalOutOfStock,
            'due' => $dueAlerts->count(),
            'sent' => $sent,
            'items' => $items,
        ];
    }

    /**
     * Explica la intención de fillAlertSnapshot dentro del flujo del servicio.
     */

    private function fillAlertSnapshot(InventoryAlert $alert, array $context, int $stock): void
    {
        $alert->fill([
            'product_id' => $context['product_id'],
            'product_name' => $context['product_name'],
            'color_name' => $context['color_name'],
            'size_label' => $context['size_label'],
            'sku' => $context['sku'],
            'stock' => $stock,
        ]);
    }

    /**
     * Carga datos de producto y variante necesarios para mensajes de inventario.
     */

    private function resolveVariantContext(int $variantId): ?array
    {
        if (!Schema::hasTable('product_size_variants') || !Schema::hasTable('product_color_variants') || !Schema::hasTable('products')) {
            return null;
        }

        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);
        $inlineColorColumn = $this->firstExistingColumn('product_color_variants', ['color_name', 'name']);
        $colorNameColumn = Schema::hasTable('colors') ? $this->firstExistingColumn('colors', ['name', 'nombre']) : null;
        $sizeNameColumn = Schema::hasTable('sizes') ? $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']) : null;
        $hasSizeId = Schema::hasColumn('product_size_variants', 'size_id');
        $hasInlineSizeLabel = Schema::hasColumn('product_size_variants', 'size_label');
        $hasSku = Schema::hasColumn('product_size_variants', 'sku');
        $hasColorId = Schema::hasColumn('product_color_variants', 'color_id');

        $query = DB::table('product_size_variants as psv')
            ->join('product_color_variants as pcv', 'psv.color_variant_id', '=', 'pcv.id')
            ->join('products as p', 'pcv.product_id', '=', 'p.id')
            ->where('psv.id', $variantId)
            ->select(
                'psv.id',
                'p.id as product_id',
                DB::raw(($productNameColumn ? "p.{$productNameColumn}" : "'Producto'") . ' as product_name'),
                DB::raw($hasSku ? 'psv.sku' : 'NULL as sku'),
            );

        if ($hasColorId && Schema::hasTable('colors')) {
            $query->leftJoin('colors as c', 'pcv.color_id', '=', 'c.id');
            $query->addSelect(DB::raw(($colorNameColumn ? "c.{$colorNameColumn}" : 'NULL') . ' as color_name'));
        } elseif ($inlineColorColumn) {
            $query->addSelect(DB::raw("pcv.{$inlineColorColumn} as color_name"));
        } else {
            $query->addSelect(DB::raw('NULL as color_name'));
        }

        if ($hasSizeId && Schema::hasTable('sizes')) {
            $query->leftJoin('sizes as s', 'psv.size_id', '=', 's.id');
            $query->addSelect(DB::raw(($sizeNameColumn ? "s.{$sizeNameColumn}" : 'NULL') . ' as size_label'));
        } elseif ($hasInlineSizeLabel) {
            $query->addSelect('psv.size_label');
        } else {
            $query->addSelect(DB::raw('NULL as size_label'));
        }

        $row = $query->first();
        if (!$row) {
            return null;
        }

        return [
            'product_id' => (int) ($row->product_id ?? 0),
            'product_name' => trim((string) ($row->product_name ?? 'Producto')) ?: 'Producto',
            'color_name' => $this->normalizeNullableString($row->color_name ?? null),
            'size_label' => $this->normalizeNullableString($row->size_label ?? null),
            'sku' => $this->normalizeNullableString($row->sku ?? null),
        ];
    }

    /**
     * Resuelve la primera columna disponible entre candidatos para soportar esquemas migrados o parciales.
     */

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Explica la intención de normalizeNullableString dentro del flujo del servicio.
     */

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Explica la intención de shouldSendInitialEmail dentro del flujo del servicio.
     */

    private function shouldSendInitialEmail(): bool
    {
        return (bool) config('inventory.send_initial_email', true);
    }

    /**
     * Explica la intención de sendAlertEmail dentro del flujo del servicio.
     */

    private function sendAlertEmail(InventoryAlert $alert, string $type): bool
    {
        $recipients = $this->resolveRecipients();
        if ($recipients === []) {
            Log::info('No hay destinatarios configurados para alertas de inventario.', [
                'variant_id' => (int) $alert->variant_id,
                'type' => $type,
            ]);

            return false;
        }

        $viewData = $this->buildEmailViewData($alert, $type);

        try {
            Mail::send('emails.inventory-alert', $viewData, function ($mail) use ($recipients, $viewData): void {
                $mail->to($recipients)
                    ->subject($viewData['subject']);
            });

            return true;
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar correo de inventario.', [
                'variant_id' => (int) $alert->variant_id,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Explica la intención de buildEmailViewData dentro del flujo del servicio.
     */

    private function buildEmailViewData(InventoryAlert $alert, string $type): array
    {
        $isReminder = $type === self::EMAIL_REMINDER;
        $outSince = $alert->out_of_stock_since instanceof Carbon
            ? $alert->out_of_stock_since->copy()->timezone('America/Bogota')
            : null;
        $daysOutOfStock = $outSince ? max(1, $outSince->diffInDays(now('America/Bogota'))) : 1;
        $variantLabel = $this->formatVariantLabel($alert);
        $productName = trim((string) ($alert->product_name ?? 'Producto')) ?: 'Producto';

        $subject = $isReminder
            ? "Recordatorio de reposición pendiente: {$productName}"
            : "Alerta de inventario agotado: {$productName}";

        $headline = $isReminder
            ? 'La reposición sigue pendiente'
            : 'Una variante quedó sin stock';

        $lead = $isReminder
            ? "La variante {$variantLabel} continúa agotada desde hace {$daysOutOfStock} día" . ($daysOutOfStock === 1 ? '' : 's') . '.'
            : "La variante {$variantLabel} llegó a 0 unidades disponibles y requiere reposición.";

        $note = $isReminder
            ? 'Revisa el inventario y carga existencias para evitar más quiebres de stock en ventas nuevas.'
            : 'Te recomendamos revisar el inventario admin y programar la reposición cuanto antes.';

        return [
            'subject' => $subject,
            'badge' => $isReminder ? 'Recordatorio' : 'Alerta inmediata',
            'headline' => $headline,
            'lead' => $lead,
            'note' => $note,
            'cta_label' => 'Revisar inventario',
            'cta_url' => $this->inventoryAdminUrl(),
            'summary_rows' => [
                ['label' => 'Producto', 'value' => $productName],
                ['label' => 'Variante', 'value' => $variantLabel],
                ['label' => 'Stock disponible', 'value' => (string) max(0, (int) $alert->stock)],
                ['label' => 'Sin stock desde', 'value' => $this->formatBogotaDate($outSince)],
            ],
        ];
    }

    /**
     * Explica la intención de formatVariantLabel dentro del flujo del servicio.
     */

    private function formatVariantLabel(InventoryAlert $alert): string
    {
        $parts = array_values(array_filter([
            trim((string) ($alert->color_name ?? '')),
            trim((string) ($alert->size_label ?? '')),
        ]));

        $base = $parts !== [] ? implode(' / ', $parts) : 'Variante sin detalle';
        $sku = trim((string) ($alert->sku ?? ''));

        return $sku !== '' ? "{$base} · SKU {$sku}" : $base;
    }

    /**
     * Explica la intención de formatBogotaDate dentro del flujo del servicio.
     */

    private function formatBogotaDate(?Carbon $date): string
    {
        if ($date === null) {
            return 'Sin fecha registrada';
        }

        return $date->translatedFormat('d/m/Y h:i A');
    }

    /**
     * Explica la intención de resolveRecipients dentro del flujo del servicio.
     */

    private function resolveRecipients(): array
    {
        $rawRecipients = str_replace(';', ',', (string) config('inventory.alert_emails', ''));
        $segments = array_map('trim', explode(',', $rawRecipients));
        $emails = [];

        foreach ($segments as $segment) {
            if ($segment === '' || !filter_var($segment, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emails[$segment] = $segment;
        }

        return array_values($emails);
    }

    /**
     * Explica la intención de inventoryAdminUrl dentro del flujo del servicio.
     */

    private function inventoryAdminUrl(): string
    {
        $baseUrl = rtrim((string) config('inventory.frontend_url', 'http://localhost:5173'), '/');
        $path = '/' . ltrim((string) config('inventory.inventory_admin_path', '/admin/inventario'), '/');

        return $baseUrl . $path;
    }
}