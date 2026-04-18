<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkDiscountRule;
use App\Models\DiscountCode;
use App\Models\DiscountType;
use App\Support\DiscountPdfAttachmentHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminDiscountController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';
    private const DEFAULT_NOTIFICATION_TYPE_ID = 1;

    // ── Codigos de descuento ────────────────────────────────

    public function codes(): JsonResponse
    {
        $codes = DiscountCode::query()
            ->with('type')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DiscountCode $code) => $this->transformCode($code));

        if ($codes->isEmpty()) {
            $codes = collect($this->loadLegacyCodes());
        }

        return response()->json(['success' => true, 'data' => $codes]);
    }

    public function storeCode(Request $request): JsonResponse
    {
        $admin = $request->input('_admin_user', []);
        $payload = $this->buildCodePayload($request, false);
        $payload['created_by'] = $admin['id'] ?? null;
        $payload['used_count'] = 0;

        $code = DiscountCode::query()->create($payload);

        return response()->json(['success' => true, 'message' => 'Codigo creado.', 'id' => $code->id], 201);
    }

    public function updateCode(Request $request, int $id): JsonResponse
    {
        $code = DiscountCode::query()->find($id);

        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        $code->fill($this->buildCodePayload($request, true));
        $code->save();

        return response()->json(['success' => true, 'message' => 'Codigo actualizado.']);
    }

    public function destroyCode(int $id): JsonResponse
    {
        $deleted = DiscountCode::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Codigo eliminado.']);
    }

    /**
     * Lista clientes disponibles para campañas de códigos.
     */
    public function campaignCustomers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'string', 'max:2000'],
        ]);

        $ids = collect(explode(',', (string) ($data['ids'] ?? '')))
            ->map(static fn ($id) => trim((string) $id))
            ->filter(static fn ($id) => $id !== '')
            ->unique()
            ->take(200)
            ->values()
            ->all();

        $customers = $this->loadCampaignCustomers($data['search'] ?? null, $ids, 200);

        return response()->json(['success' => true, 'data' => $customers]);
    }

    /**
     * Envio masivo de descuento para todos los clientes.
     */
    public function sendMassCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'discount_code_id' => ['required', 'integer', 'min:1'],
            'send_notification' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        return $this->dispatchCampaign(
            (int) $data['discount_code_id'],
            $this->loadCampaignCustomers(),
            (bool) ($data['send_notification'] ?? true),
            (bool) ($data['send_email'] ?? true),
        );
    }

    /**
     * Envio de descuento para un conjunto especifico de clientes.
     */
    public function sendSpecificCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'discount_code_id' => ['required', 'integer', 'min:1'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['string', 'max:40'],
            'send_notification' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $userIds = collect($data['user_ids'])
            ->map(static fn ($id) => trim((string) $id))
            ->filter(static fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        $customers = $this->loadCampaignCustomers(null, $userIds);

        if (empty($customers)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron usuarios para la campaña.',
            ], 422);
        }

        return $this->dispatchCampaign(
            (int) $data['discount_code_id'],
            $customers,
            (bool) ($data['send_notification'] ?? true),
            (bool) ($data['send_email'] ?? true),
        );
    }

    // ── Descuentos por volumen ──────────────────────────────

    public function bulkDiscounts(): JsonResponse
    {
        $rules = BulkDiscountRule::query()
            ->orderBy('min_quantity')
            ->get()
            ->map(fn (BulkDiscountRule $rule) => $this->transformBulkDiscount($rule));

        if ($rules->isEmpty()) {
            $rules = collect($this->loadLegacyBulkDiscounts());
        }

        return response()->json(['success' => true, 'data' => $rules]);
    }

    /**
     * Fallback legacy para codigos cuando el microservicio aun no tiene datos.
     */
    private function loadLegacyCodes(): array
    {
        if (!$this->legacyTableExists('discount_codes')) {
            return [];
        }

        try {
            return DB::connection(self::LEGACY_CONNECTION)
                ->table('discount_codes as dc')
                ->leftJoin('discount_types as dt', 'dc.discount_type_id', '=', 'dt.id')
                ->select(
                    'dc.id',
                    'dc.code',
                    'dc.discount_type_id',
                    'dc.discount_value',
                    'dc.max_uses',
                    'dc.used_count',
                    'dc.start_date',
                    'dc.end_date',
                    'dc.is_active',
                    'dc.is_single_use',
                    'dc.created_at',
                    'dc.updated_at',
                    'dt.name as discount_type_name',
                )
                ->orderByDesc('dc.created_at')
                ->get()
                ->map(fn (object $row) => $this->transformLegacyCode($row))
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Fallback legacy para reglas por cantidad cuando el microservicio aun no tiene datos.
     */
    private function loadLegacyBulkDiscounts(): array
    {
        if (!$this->legacyTableExists('bulk_discount_rules')) {
            return [];
        }

        try {
            return DB::connection(self::LEGACY_CONNECTION)
                ->table('bulk_discount_rules')
                ->select('id', 'min_quantity', 'max_quantity', 'discount_percentage', 'is_active', 'created_at', 'updated_at')
                ->orderBy('min_quantity')
                ->get()
                ->map(fn (object $row) => [
                    'id' => (int) $row->id,
                    'min_quantity' => (int) ($row->min_quantity ?? 0),
                    'max_quantity' => $row->max_quantity !== null ? (int) $row->max_quantity : null,
                    'discount_percentage' => (float) ($row->discount_percentage ?? 0),
                    'discount_percent' => (float) ($row->discount_percentage ?? 0),
                    'active' => (bool) ($row->is_active ?? false),
                    'is_active' => (bool) ($row->is_active ?? false),
                    'created_at' => $this->toIsoString($row->created_at ?? null),
                    'updated_at' => $this->toIsoString($row->updated_at ?? null),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function transformLegacyCode(object $row): array
    {
        $typeName = Str::lower((string) ($row->discount_type_name ?? ''));
        $type = str_contains($typeName, 'fixed') || str_contains($typeName, 'fijo') || str_contains($typeName, 'monto')
            ? 'fixed'
            : 'percent';

        return [
            'id' => (int) $row->id,
            'code' => (string) ($row->code ?? ''),
            'type' => $type,
            'type_label' => $type === 'fixed' ? 'Monto fijo' : 'Porcentaje',
            'discount_type_id' => $row->discount_type_id ? (int) $row->discount_type_id : null,
            'discount_type_name' => $row->discount_type_name,
            'value' => (float) ($row->discount_value ?? 0),
            'discount_value' => (float) ($row->discount_value ?? 0),
            'max_uses' => $row->max_uses !== null ? (int) $row->max_uses : null,
            'times_used' => (int) ($row->used_count ?? 0),
            'used_count' => (int) ($row->used_count ?? 0),
            'starts_at' => $this->toIsoString($row->start_date ?? null),
            'start_date' => $this->toIsoString($row->start_date ?? null),
            'expires_at' => $this->toIsoString($row->end_date ?? null),
            'end_date' => $this->toIsoString($row->end_date ?? null),
            'active' => (bool) ($row->is_active ?? false),
            'is_active' => (bool) ($row->is_active ?? false),
            'is_single_use' => (bool) ($row->is_single_use ?? false),
            'created_at' => $this->toIsoString($row->created_at ?? null),
            'updated_at' => $this->toIsoString($row->updated_at ?? null),
        ];
    }

    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection(self::LEGACY_CONNECTION)->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function toIsoString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Ejecuta una campaña de descuento para una lista de clientes.
     */
    private function dispatchCampaign(int $discountCodeId, array $customers, bool $sendNotification, bool $sendEmail): JsonResponse
    {
        if (!$sendNotification && !$sendEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar al menos un canal de envío.',
            ], 422);
        }

        if (empty($customers)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay clientes disponibles para enviar la campaña.',
            ], 422);
        }

        if ($sendEmail && !$this->emailDeliveryConfigured()) {
            Log::warning('Campaña de descuento sin envío real de correo: mailer no entregable.', [
                'mailer' => (string) config('mail.default', 'log'),
                'discount_code_id' => $discountCodeId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El servicio de correo no está configurado para envío real. Configura SMTP para continuar.',
            ], 422);
        }

        $discountCode = $this->findCampaignDiscountCode($discountCodeId);

        if ($discountCode === null) {
            return response()->json([
                'success' => false,
                'message' => 'Código de descuento no encontrado.',
            ], 404);
        }

        // Helper de adjunto PDF en memoria, reutilizable para ambos tipos de envío.
        $pdfAttachment = $sendEmail ? DiscountPdfAttachmentHelper::build($discountCode) : null;

        $summary = [
            'total_recipients' => count($customers),
            'notifications' => ['sent' => 0, 'failed' => 0],
            'emails' => ['sent' => 0, 'failed' => 0],
        ];

        foreach ($customers as $customer) {
            if ($sendNotification) {
                if ($this->sendCampaignNotification($customer, $discountCode)) {
                    $summary['notifications']['sent']++;
                } else {
                    $summary['notifications']['failed']++;
                }
            }

            if ($sendEmail) {
                if ($this->sendCampaignEmail($customer, $discountCode, $pdfAttachment)) {
                    $summary['emails']['sent']++;
                } else {
                    $summary['emails']['failed']++;
                }
            }
        }

        $message = 'Campaña procesada correctamente.';
        if ($sendEmail && $pdfAttachment === null) {
            $message .= ' No se pudo generar el PDF; los correos se enviaron sin adjunto.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'discount_code' => [
                    'id' => (int) ($discountCode['id'] ?? 0),
                    'code' => (string) ($discountCode['code'] ?? ''),
                ],
                'summary' => $summary,
            ],
        ]);
    }

    private function findCampaignDiscountCode(int $id): ?array
    {
        $code = DiscountCode::query()->with('type')->find($id);

        if ($code) {
            return $this->transformCode($code);
        }

        if (!$this->legacyTableExists('discount_codes')) {
            return null;
        }

        try {
            $row = DB::connection(self::LEGACY_CONNECTION)
                ->table('discount_codes as dc')
                ->leftJoin('discount_types as dt', 'dc.discount_type_id', '=', 'dt.id')
                ->select(
                    'dc.id',
                    'dc.code',
                    'dc.discount_type_id',
                    'dc.discount_value',
                    'dc.max_uses',
                    'dc.used_count',
                    'dc.start_date',
                    'dc.end_date',
                    'dc.is_active',
                    'dc.is_single_use',
                    'dc.created_at',
                    'dc.updated_at',
                    'dt.name as discount_type_name',
                )
                ->where('dc.id', $id)
                ->first();

            return $row ? $this->transformLegacyCode($row) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function loadCampaignCustomers(?string $search = null, array $ids = [], ?int $limit = null): array
    {
        $legacyCustomers = $this->queryCampaignCustomers(self::LEGACY_CONNECTION, $search, $ids, $limit);
        if (!empty($legacyCustomers)) {
            return $legacyCustomers;
        }

        return $this->queryCampaignCustomers(null, $search, $ids, $limit);
    }

    private function queryCampaignCustomers(?string $connection, ?string $search, array $ids, ?int $limit): array
    {
        if (!$this->tableExistsByConnection('users', $connection)) {
            return [];
        }

        try {
            $query = $this->customersQueryBuilder($connection)
                ->table('users')
                ->select(['id', 'name', 'email', 'phone']);

            if ($this->columnExistsByConnection('users', 'role', $connection)) {
                $query->where('role', 'customer');
            }

            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }

            $normalizedSearch = trim((string) $search);
            if ($normalizedSearch !== '') {
                $likeOperator = $this->likeOperatorByConnection($connection);
                $query->where(function ($q) use ($normalizedSearch, $likeOperator) {
                    $q->where('name', $likeOperator, "%{$normalizedSearch}%")
                        ->orWhere('email', $likeOperator, "%{$normalizedSearch}%");
                });
            }

            if ($this->columnExistsByConnection('users', 'name', $connection)) {
                $query->orderBy('name');
            } else {
                $query->orderBy('id');
            }

            if ($limit !== null) {
                $query->limit($limit);
            }

            /** @var Collection<int, object> $rows */
            $rows = $query->get();

            return $rows
                ->map(fn (object $row) => $this->normalizeCustomer($row))
                ->filter(fn (array $customer) => $customer['id'] !== '')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function sendCampaignNotification(array $customer, array $discountCode): bool
    {
        $userId = trim((string) ($customer['id'] ?? ''));
        if ($userId === '') {
            return false;
        }

        $endpoint = $this->resolveNotificationEndpoint();
        if ($endpoint === null) {
            return false;
        }

        $payload = [
            'user_id' => $userId,
            'type_id' => (int) config('services.notifications.discount_type_id', self::DEFAULT_NOTIFICATION_TYPE_ID),
            'title' => 'Nuevo descuento disponible',
            'message' => sprintf(
                'Usa el código %s y obtén %s. %s',
                (string) ($discountCode['code'] ?? ''),
                $this->formatCampaignValue($discountCode),
                $this->formatCampaignEndDate($discountCode['end_date'] ?? null),
            ),
            'related_entity_type' => 'discount_code',
            'related_entity_id' => (int) ($discountCode['id'] ?? 0),
        ];

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->post($endpoint, $payload);

            return $response->successful();
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar notificación de campaña de descuento.', [
                'user_id' => $userId,
                'discount_code_id' => (int) ($discountCode['id'] ?? 0),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendCampaignEmail(array $customer, array $discountCode, ?array $pdfAttachment): bool
    {
        $email = trim((string) ($customer['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $name = trim((string) ($customer['name'] ?? ''));
        if ($name === '') {
            $name = 'Cliente';
        }

        $html = $this->buildDiscountEmailHtml($name, $discountCode);

        try {
            Mail::html($html, function ($message) use ($email, $name, $pdfAttachment) {
                $message->to($email, $name)
                    ->subject('¡Tienes un descuento especial en Angelow!');

                if ($pdfAttachment !== null) {
                    $message->attachData(
                        $pdfAttachment['content'],
                        $pdfAttachment['filename'],
                        ['mime' => $pdfAttachment['mime'] ?? 'application/pdf'],
                    );
                }
            });

            return true;
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar correo de campaña de descuento.', [
                'email' => $email,
                'discount_code_id' => (int) ($discountCode['id'] ?? 0),
                'mailer' => (string) config('mail.default', 'log'),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function emailDeliveryConfigured(): bool
    {
        $defaultMailer = Str::lower((string) config('mail.default', 'log'));

        if (in_array($defaultMailer, ['log', 'array'], true)) {
            return false;
        }

        // Para SMTP exigimos datos base para evitar falsos positivos de envío.
        if ($defaultMailer === 'smtp') {
            $host = trim((string) config('mail.mailers.smtp.host', ''));
            $port = (int) config('mail.mailers.smtp.port', 0);
            $username = trim((string) config('mail.mailers.smtp.username', ''));

            return $host !== '' && $port > 0 && $username !== '';
        }

        return true;
    }

    private function buildDiscountEmailHtml(string $customerName, array $discountCode): string
    {
        $safeName = e($customerName);
        $safeCode = e((string) ($discountCode['code'] ?? 'PROMO'));
        $safeValue = e($this->formatCampaignValue($discountCode));
        $safeExpiry = e($this->formatCampaignEndDate($discountCode['end_date'] ?? null));
        $storeUrl = rtrim((string) config('services.frontend.store_url', 'http://localhost:5173'), '/');
        if ($storeUrl === '') {
            $storeUrl = 'http://localhost:5173';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>¡Descuento Especial!</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2968c8; color: #fff; padding: 20px; text-align: center; border-radius: 6px 6px 0 0; }
        .content { padding: 20px; background-color: #f9f9f9; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
        .discount-code {
            background-color: #2968c8;
            color: #fff;
            padding: 10px 20px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            margin: 15px 0;
            border-radius: 5px;
        }
        .btn {
            background-color: #2968c8;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 15px 0;
        }
        .footer { text-align: center; padding: 10px; font-size: 12px; color: #777; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Descuento Especial!</h1>
            <p>Hola {$safeName},</p>
        </div>
        <div class="content">
            <p>Hemos preparado un descuento especial para ti:</p>
            <div style="text-align: center;">
                <div class="discount-code">{$safeCode}</div>
                <p style="font-size: 18px; margin: 5px 0;">{$safeValue}</p>
                <p style="color: #666;">{$safeExpiry}</p>
            </div>
            <p>Para usar tu código de descuento:</p>
            <ol>
                <li>Agrega productos a tu carrito.</li>
                <li>Ingresa el código en el checkout.</li>
                <li>¡Disfruta de tu descuento!</li>
            </ol>
            <p style="text-align: center;">
                <a href="{$storeUrl}" class="btn">Ir a la tienda</a>
            </p>
        </div>
        <div class="footer">
            <p>© Angelow. Todos los derechos reservados.</p>
            <p>Este código es personal e intransferible.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function formatCampaignValue(array $discountCode): string
    {
        $value = (float) ($discountCode['discount_value'] ?? $discountCode['value'] ?? 0);
        $type = Str::lower((string) ($discountCode['type'] ?? 'percent'));

        if ($type === 'fixed') {
            return '$' . number_format($value, 0, ',', '.') . ' de descuento';
        }

        $normalized = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $normalized . '% de descuento';
    }

    private function formatCampaignEndDate(mixed $endDate): string
    {
        if ($endDate === null || $endDate === '') {
            return 'Sin fecha de expiración';
        }

        try {
            return 'Válido hasta ' . Carbon::parse($endDate)->format('d/m/Y');
        } catch (\Throwable) {
            return 'Sin fecha de expiración';
        }
    }

    private function resolveNotificationEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.notifications.base_url', 'http://notification-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/notifications';
        }

        return $baseUrl . '/api/notifications';
    }

    private function normalizeCustomer(object $row): array
    {
        $name = trim((string) ($row->name ?? ''));

        return [
            'id' => trim((string) ($row->id ?? '')),
            'name' => $name !== '' ? $name : 'Cliente',
            'email' => trim((string) ($row->email ?? '')),
            'phone' => trim((string) ($row->phone ?? '')),
        ];
    }

    private function customersQueryBuilder(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }

    private function tableExistsByConnection(string $table, ?string $connection): bool
    {
        try {
            $schemaConnection = $connection ?? DB::getDefaultConnection();
            return Schema::connection($schemaConnection)->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function columnExistsByConnection(string $table, string $column, ?string $connection): bool
    {
        try {
            $schemaConnection = $connection ?? DB::getDefaultConnection();
            return Schema::connection($schemaConnection)->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function likeOperatorByConnection(?string $connection): string
    {
        try {
            return $this->customersQueryBuilder($connection)->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        } catch (\Throwable) {
            return 'LIKE';
        }
    }

    public function storeBulkDiscount(Request $request): JsonResponse
    {
        $rule = BulkDiscountRule::query()->create($this->buildBulkDiscountPayload($request, false));

        return response()->json(['success' => true, 'message' => 'Regla creada.', 'id' => $rule->id], 201);
    }

    public function updateBulkDiscount(Request $request, int $id): JsonResponse
    {
        $rule = BulkDiscountRule::query()->find($id);

        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        $rule->fill($this->buildBulkDiscountPayload($request, true));
        $rule->save();

        return response()->json(['success' => true, 'message' => 'Regla actualizada.']);
    }

    public function destroyBulkDiscount(int $id): JsonResponse
    {
        $deleted = BulkDiscountRule::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
    }

    private function buildCodePayload(Request $request, bool $partial): array
    {
        $data = $request->validate([
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'max:30'],
            'discount_type_id' => ['nullable', 'integer'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'is_single_use' => ['nullable', 'boolean'],
        ]);

        $payload = [];

        if (array_key_exists('code', $data)) {
            $payload['code'] = strtoupper(trim((string) $data['code']));
        }

        if (array_key_exists('discount_type_id', $data) || array_key_exists('type', $data)) {
            $payload['discount_type_id'] = $this->resolveDiscountTypeId(
                $data['discount_type_id'] ?? null,
                $data['type'] ?? null,
            );
        }

        if (array_key_exists('value', $data) || array_key_exists('discount_value', $data)) {
            $payload['discount_value'] = (float) ($data['value'] ?? $data['discount_value'] ?? 0);
        }

        if (array_key_exists('max_uses', $data)) {
            $payload['max_uses'] = $data['max_uses'] ?? null;
        }

        if (array_key_exists('start_date', $data) || array_key_exists('starts_at', $data)) {
            $payload['start_date'] = $data['start_date'] ?? $data['starts_at'] ?? null;
        }

        if (array_key_exists('end_date', $data) || array_key_exists('expires_at', $data)) {
            $payload['end_date'] = $data['end_date'] ?? $data['expires_at'] ?? null;
        }

        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        if (array_key_exists('is_single_use', $data)) {
            $payload['is_single_use'] = (bool) ($data['is_single_use'] ?? false);
        }

        return $payload;
    }

    private function buildBulkDiscountPayload(Request $request, bool $partial): array
    {
        $data = $request->validate([
            'min_quantity' => [$partial ? 'sometimes' : 'required', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        $payload = [];

        if (array_key_exists('min_quantity', $data)) {
            $payload['min_quantity'] = (int) $data['min_quantity'];
        }

        if (array_key_exists('max_quantity', $data)) {
            $payload['max_quantity'] = $data['max_quantity'] ?? null;
        }

        if (array_key_exists('discount_percentage', $data) || array_key_exists('discount_percent', $data)) {
            $payload['discount_percentage'] = (float) ($data['discount_percentage'] ?? $data['discount_percent'] ?? 0);
        }

        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        return $payload;
    }

    private function transformCode(DiscountCode $code): array
    {
        $typeName = strtolower((string) ($code->type?->name ?? ''));
        $type = str_contains($typeName, 'fixed') || str_contains($typeName, 'fijo') ? 'fixed' : 'percent';

        return [
            'id' => $code->id,
            'code' => $code->code,
            'type' => $type,
            'type_label' => $type === 'fixed' ? 'Monto fijo' : 'Porcentaje',
            'discount_type_id' => $code->discount_type_id,
            'discount_type_name' => $code->type?->name,
            'value' => (float) ($code->discount_value ?? 0),
            'discount_value' => (float) ($code->discount_value ?? 0),
            'max_uses' => $code->max_uses,
            'times_used' => (int) ($code->used_count ?? 0),
            'used_count' => (int) ($code->used_count ?? 0),
            'starts_at' => optional($code->start_date)?->toISOString(),
            'start_date' => optional($code->start_date)?->toISOString(),
            'expires_at' => optional($code->end_date)?->toISOString(),
            'end_date' => optional($code->end_date)?->toISOString(),
            'active' => (bool) $code->is_active,
            'is_active' => (bool) $code->is_active,
            'is_single_use' => (bool) $code->is_single_use,
            'created_at' => optional($code->created_at)?->toISOString(),
            'updated_at' => optional($code->updated_at)?->toISOString(),
        ];
    }

    private function transformBulkDiscount(BulkDiscountRule $rule): array
    {
        return [
            'id' => $rule->id,
            'min_quantity' => (int) $rule->min_quantity,
            'max_quantity' => $rule->max_quantity,
            'discount_percentage' => (float) ($rule->discount_percentage ?? 0),
            'discount_percent' => (float) ($rule->discount_percentage ?? 0),
            'active' => (bool) $rule->is_active,
            'is_active' => (bool) $rule->is_active,
            'created_at' => optional($rule->created_at)?->toISOString(),
            'updated_at' => optional($rule->updated_at)?->toISOString(),
        ];
    }

    private function resolveDiscountTypeId(?int $discountTypeId, ?string $type): int
    {
        if ($discountTypeId) {
            return $discountTypeId;
        }

        $normalizedType = Str::lower(trim((string) $type));
        $targetName = in_array($normalizedType, ['fixed', 'monto fijo', 'fixed_amount'], true)
            ? 'fixed_amount'
            : 'percentage';

        $discountType = DiscountType::query()->firstOrCreate(
            ['name' => $targetName],
            [
                'description' => $targetName === 'fixed_amount'
                    ? 'Descuento de valor fijo.'
                    : 'Descuento porcentual.',
                'is_active' => true,
            ],
        );

        return (int) $discountType->id;
    }
}
