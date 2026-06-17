<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkDiscountRule;
use App\Models\DiscountCode;
use App\Models\DiscountType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Controlador administrativo del dominio de descuentos.
 * Gestiona el CRUD completo de códigos de descuento y reglas por cantidad,
 * así como el envío de campañas masivas o específicas de descuentos a clientes.
 * Soporta doble origen de datos (microservicio y legacy) durante la migración.
 */
class AdminDiscountController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';
    private const DEFAULT_NOTIFICATION_TYPE_ID = 1;

    // ── Códigos de descuento ────────────────────────────────

    /**
     * Lista todos los códigos de descuento con su tipo asociado.
     * Si no hay datos en microservicio, carga desde legacy.
     */
    public function codes(): JsonResponse
    {
        $codes = DiscountCode::query()
            ->with('type')
            ->orderByDesc('created_at')
            ->get()
                ->map(fn (DiscountCode $code) => $this->transformCode($code));

        // Si no hay códigos en microservicio, intenta cargar desde legacy como respaldo.
        if ($codes->isEmpty()) {
            $codes = collect($this->loadLegacyCodes());
        }

        return response()->json(['success' => true, 'data' => $codes]);
    }

    /**
     * Crea un código de descuento a partir del payload normalizado del panel.
     */
    public function storeCode(Request $request): JsonResponse
    {
        $admin = $request->input('_admin_user', []);
        $payload = $this->buildCodePayload($request, false);
        $payload['created_by'] = $admin['id'] ?? null;
        $payload['used_count'] = 0;

        $code = DiscountCode::query()->create($payload);

        return response()->json(['success' => true, 'message' => 'Codigo creado.', 'id' => $code->id], 201);
    }

    /**
     * Actualiza un código de descuento existente conservando campos no enviados en edición parcial.
     */
    public function updateCode(Request $request, int $id): JsonResponse
    {
        $code = DiscountCode::query()->find($id);

        // Si el código no existe, retorna 404 antes de intentar actualizar.
        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        $code->fill($this->buildCodePayload($request, true));
        $code->save();

        return response()->json(['success' => true, 'message' => 'Codigo actualizado.']);
    }

    /**
     * Elimina un código de descuento y reporta si el registro no existía.
     */
    public function destroyCode(int $id): JsonResponse
    {
        $deleted = DiscountCode::query()->whereKey($id)->delete();

        // Si no se eliminó ningún registro, el código no existía.
        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Codigo eliminado.']);
    }

    /**
     * Lista clientes disponibles para campañas de códigos.
     * Soporta búsqueda por nombre/email y filtro por IDs específicos.
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
     * Envio masivo de descuento para todos los clientes disponibles.
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
     * Envio de descuento para un conjunto especifico de clientes por IDs.
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

        // Sin clientes destino, no tiene sentido continuar con la campaña.
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

    // ── Descuentos por cantidad ─────────────────────────────

    /**
     * Lista todas las reglas de descuento por cantidad ordenadas.
     * Soporta fallback a legacy si no hay datos locales.
     */
    public function bulkDiscounts(): JsonResponse
    {
        $rules = BulkDiscountRule::query()
            ->orderBy('min_quantity')
            ->get()
                ->map(fn (BulkDiscountRule $rule) => $this->transformBulkDiscount($rule));

        // Fallback a legacy si aún no hay reglas de cantidad en microservicio.
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
        // Si la tabla legacy no existe, retorna vacío sin intentar la consulta.
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
            // Error de conexión con legacy: retorna arreglo vacío.
            return [];
        }
    }

    /**
     * Fallback legacy para reglas por cantidad cuando el microservicio aun no tiene datos.
     */
    private function loadLegacyBulkDiscounts(): array
    {
        // Si la tabla legacy no existe, no intenta la consulta.
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
            // Error de conexión con legacy: retorna arreglo vacío.
            return [];
        }
    }

    /**
     * Adapta un código de respaldo (legacy) al contrato administrativo actual.
     */
    private function transformLegacyCode(object $row): array
    {
        // Determina si es fijo o porcentual según el nombre del tipo de descuento.
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

    /**
     * Comprueba existencia de tablas legacy antes de consultar datos migrados.
     */
    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection(self::LEGACY_CONNECTION)->hasTable($table);
        } catch (\Throwable) {
            // Error de conexión: asume que la tabla no existe.
            return false;
        }
    }

    /**
     * Normaliza fechas a ISO para que frontend y APIs reciban un formato estable.
     */
    private function toIsoString(mixed $value): ?string
    {
        // Valor nulo o vacío: no hay fecha que formatear.
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toISOString();
        } catch (\Throwable) {
            // Si el valor no es una fecha válida, retorna null.
            return null;
        }
    }

    /**
     * Ejecuta una campaña de descuento para una lista de clientes.
     * Envía notificaciones push y/o emails según los canales seleccionados
     * y retorna un resumen con totales enviados, fallidos y omitidos.
     */
    private function dispatchCampaign(int $discountCodeId, array $customers, bool $sendNotification, bool $sendEmail): JsonResponse
    {
        // Al menos un canal de envío debe estar activo para continuar.
        if (!$sendNotification && !$sendEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar al menos un canal de envío.',
            ], 422);
        }

        // Sin destinatarios, la campaña no puede procesarse.
        if (empty($customers)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay clientes disponibles para enviar la campaña.',
            ], 422);
        }

        // Busca el código de descuento en microservicio o legacy.
        $discountCode = $this->findCampaignDiscountCode($discountCodeId);

        // Si el código no existe en ninguna fuente, no se puede enviar la campaña.
        if ($discountCode === null) {
            return response()->json([
                'success' => false,
                'message' => 'Código de descuento no encontrado.',
            ], 404);
        }

        $summary = [
            'total_recipients' => count($customers),
            'notifications' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
            'emails' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
        ];

        // Itera cada cliente para enviar notificación push y/o email según la configuración.
        foreach ($customers as $customer) {
            $delivery = $this->sendCampaignNotification($customer, $discountCode, $sendNotification, $sendEmail);

            // Acumula estadísticas de notificaciones push.
            if ($sendNotification) {
                if ($delivery['notification_sent']) {
                    $summary['notifications']['sent']++;
                } elseif ($delivery['skipped']) {
                    $summary['notifications']['skipped']++;
                } else {
                    $summary['notifications']['failed']++;
                }
            }

            // Acumula estadísticas de correos electrónicos.
            if ($sendEmail) {
                if ($delivery['email_sent']) {
                    $summary['emails']['sent']++;
                } elseif ($delivery['skipped'] || ($delivery['reason'] ?? '') === 'email_not_resolved') {
                    $summary['emails']['skipped']++;
                } else {
                    $summary['emails']['failed']++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaña procesada correctamente.',
            'data' => [
                'discount_code' => [
                    'id' => (int) ($discountCode['id'] ?? 0),
                    'code' => (string) ($discountCode['code'] ?? ''),
                ],
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Obtiene el código de descuento para la campaña, buscando en microservicio
     * y con fallback a legacy.
     */
    private function findCampaignDiscountCode(int $id): ?array
    {
        // Busca primero en microservicio con su tipo de descuento asociado.
        $code = DiscountCode::query()->with('type')->find($id);

        if ($code) {
            return $this->transformCode($code);
        }

        // Si no existe en microservicio, intenta fallback a legacy.
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
            // Error de conexión con legacy: no se encontró el código.
            return null;
        }
    }

    /**
     * Resuelve clientes desde auth-service y usa respaldo local si no hay respuesta.
     * Orden de resolución: auth-service -> legacy -> local.
     */
    private function loadCampaignCustomers(?string $search = null, array $ids = [], ?int $limit = null): array
    {
        // Primero intenta obtener clientes desde el auth-service vía HTTP.
        $authCustomers = $this->loadAuthServiceCustomers(request(), $search, $ids, $limit);
        if ($authCustomers !== null) {
            return $authCustomers;
        }

        // Si auth-service no responde, intenta con la base de datos legacy.
        $legacyCustomers = $this->queryCampaignCustomers(self::LEGACY_CONNECTION, $search, $ids, $limit);
        if (!empty($legacyCustomers)) {
            return $legacyCustomers;
        }

        // Último respaldo: consulta la base de datos local del microservicio.
        return $this->queryCampaignCustomers(null, $search, $ids, $limit);
    }

    /**
     * Consulta clientes al servicio de autenticación usando el token administrativo actual.
     */
    private function loadAuthServiceCustomers(?Request $request, ?string $search, array $ids, ?int $limit): ?array
    {
        // Resuelve el endpoint de clientes del auth-service y obtiene el token Bearer.
        $endpoint = $this->resolveAuthCustomersEndpoint();
        $token = trim((string) ($request?->bearerToken() ?? ''));

        // Sin endpoint configurado o sin token, no se puede consultar el auth-service.
        if ($endpoint === null || $token === '') {
            return null;
        }

        $params = [];
        $normalizedSearch = trim((string) $search);

        if ($normalizedSearch !== '') {
            $params['search'] = $normalizedSearch;
        }

        if (!empty($ids)) {
            $params['ids'] = collect($ids)
                ->map(static fn ($id) => trim((string) $id))
                ->filter(static fn ($id) => $id !== '')
                ->unique()
                ->take(200)
                ->implode(',');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->get($endpoint, $params);

            // Si la respuesta HTTP no es exitosa (2xx), retorna null.
            if (!$response->successful()) {
                return null;
            }

            $rows = $response->json('data');
            if (!is_array($rows)) {
                $rows = [];
            }

            return collect($rows)
                ->map(fn (array|object $row) => $this->normalizeCustomer($row))
                // Filtra clientes sin ID y aquellos que están bloqueados.
                ->filter(fn (array $customer) => $customer['id'] !== '' && !$customer['is_blocked'])
                ->when($limit !== null, fn (Collection $customers) => $customers->take($limit))
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::warning('No se pudo consultar clientes desde auth-service para la campaña de descuentos.', [
                'search' => $normalizedSearch,
                'ids_count' => count($ids),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Construye la URL interna para búsqueda de clientes en auth-service.
     */
    private function resolveAuthCustomersEndpoint(): ?string
    {
        $baseUrl = trim((string) env('AUTH_SERVICE_URL', 'http://auth-service:8000/api'));
        // Si no hay URL configurada, no se puede resolver el endpoint.
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        // Adapta la URL según si ya incluye /api o no.
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/admin/customers';
        }

        return $baseUrl . '/api/admin/customers';
    }

    /**
     * Consulta clientes en una conexión concreta aplicando filtros compatibles con el esquema.
     * Adapta el operador LIKE y las columnas según el motor de BD.
     */
    private function queryCampaignCustomers(?string $connection, ?string $search, array $ids, ?int $limit): array
    {
        // Verifica que la tabla users exista en la conexión antes de consultar.
        if (!$this->tableExistsByConnection('users', $connection)) {
            return [];
        }

        try {
            $query = $this->customersQueryBuilder($connection)
                ->table('users')
                ->select(['id', 'name', 'email', 'phone']);

            // Filtra por rol "customer" solo si la columna existe en el esquema.
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

            // Ordena por nombre si la columna existe, de lo contrario por ID.
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
            // Error de base de datos: retorna arreglo vacío para no interrumpir.
            return [];
        }
    }

    /**
     * Envía una notificación de campaña al servicio de notificaciones para un cliente.
     * Construye el payload con el código de descuento y lo envía por HTTP al notification-service.
     */
    private function sendCampaignNotification(array $customer, array $discountCode, bool $sendPush, bool $sendEmail): array
    {
        $userId = trim((string) ($customer['id'] ?? ''));
        // Si el cliente no tiene ID, no se puede enviar la notificación.
        if ($userId === '') {
            return [
                'notification_sent' => false,
                'email_sent' => false,
                'skipped' => false,
                'reason' => 'missing_user_id',
            ];
        }

        // Resuelve la URL del notification-service para el envío.
        $endpoint = $this->resolveNotificationEndpoint();
        // Si no hay endpoint configurado, no se puede enviar.
        if ($endpoint === null) {
            return [
                'notification_sent' => false,
                'email_sent' => false,
                'skipped' => false,
                'reason' => 'notification_endpoint_not_resolved',
            ];
        }

        $userEmail = trim((string) ($customer['email'] ?? ''));
        // Valida formato del email; si no es válido, lo limpia para evitar errores.
        if ($userEmail !== '' && !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $userEmail = '';
        }

        $payload = [
            'user_id' => $userId,
            'user_email' => $userEmail !== '' ? $userEmail : null,
            'type_id' => (int) config('services.notifications.discount_type_id', self::DEFAULT_NOTIFICATION_TYPE_ID),
            'event_key' => 'promotion',
            'title' => 'Nuevo descuento disponible',
            'message' => sprintf(
                'Usa el código %s y obtén %s. %s',
                (string) ($discountCode['code'] ?? ''),
                $this->formatCampaignValue($discountCode),
                $this->formatCampaignEndDate($discountCode['end_date'] ?? null),
            ),
            'related_entity_type' => 'discount_code',
            'related_entity_id' => (int) ($discountCode['id'] ?? 0),
            'send_push' => $sendPush,
            'send_email' => $sendEmail,
        ];

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->post($endpoint, $payload);

            // Si el notification-service no respondió correctamente, registra como fallido.
            if (!$response->successful()) {
                return [
                    'notification_sent' => false,
                    'email_sent' => false,
                    'skipped' => false,
                    'reason' => 'dispatch_failed',
                ];
            }

            $data = $response->json();

            return [
                'notification_sent' => (bool) ($data['notification_sent'] ?? false),
                'email_sent' => (bool) ($data['email_sent'] ?? false),
                'skipped' => (bool) ($data['skipped'] ?? false),
                'reason' => (string) ($data['reason'] ?? 'ok'),
            ];
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar notificación de campaña de descuento.', [
                'user_id' => $userId,
                'discount_code_id' => (int) ($discountCode['id'] ?? 0),
                'error' => $exception->getMessage(),
            ]);

            return [
                'notification_sent' => false,
                'email_sent' => false,
                'skipped' => false,
                'reason' => 'dispatch_failed',
            ];
        }
    }

    /**
     * Formatea el valor del descuento para textos de campaña.
     * Ejemplos: "$10.000 de descuento", "15% de descuento".
     */
    private function formatCampaignValue(array $discountCode): string
    {
        $value = (float) ($discountCode['discount_value'] ?? $discountCode['value'] ?? 0);
        $type = Str::lower((string) ($discountCode['type'] ?? 'percent'));

        // Descuento fijo: formatea como monto en pesos chilenos.
        if ($type === 'fixed') {
            return '$' . number_format($value, 0, ',', '.') . ' de descuento';
        }

        // Descuento porcentual: elimina decimales innecesarios (.00 -> sin decimales).
        $normalized = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $normalized . '% de descuento';
    }

    /**
     * Convierte la fecha de expiración en texto claro para el mensaje de campaña.
     */
    private function formatCampaignEndDate(mixed $endDate): string
    {
        // Si no hay fecha de expiración, muestra texto por defecto.
        if ($endDate === null || $endDate === '') {
            return 'Sin fecha de expiración';
        }

        try {
            return 'Válido hasta ' . Carbon::parse($endDate)->format('d/m/Y');
        } catch (\Throwable) {
            // Si la fecha no es válida, muestra texto genérico.
            return 'Sin fecha de expiración';
        }
    }

    /**
     * Construye la URL del servicio de notificaciones para eventos internos.
     */
    private function resolveNotificationEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.notifications.base_url', 'http://notification-service:8000/api'));
        // Sin URL configurada, no se puede resolver el endpoint de notificaciones.
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        // Adapta la ruta según si la URL base ya incluye /api.
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/notifications';
        }

        return $baseUrl . '/api/notifications';
    }

    /**
     * Convierte un registro de cliente (array u object) en el contrato interno usado por campañas.
     */
    private function normalizeCustomer(array|object $row): array
    {
        // Extrae campos comunes soportando tanto array como objeto (compatible con múltiples fuentes).
        $id = is_array($row) ? ($row['id'] ?? '') : ($row->id ?? '');
        $name = trim((string) (is_array($row) ? ($row['name'] ?? '') : ($row->name ?? '')));
        $email = trim((string) (is_array($row) ? ($row['email'] ?? '') : ($row->email ?? '')));
        $phone = trim((string) (is_array($row) ? ($row['phone'] ?? '') : ($row->phone ?? '')));
        $isBlocked = (bool) (is_array($row) ? ($row['is_blocked'] ?? false) : ($row->is_blocked ?? false));

        return [
            'id' => trim((string) $id),
            'name' => $name !== '' ? $name : 'Cliente',
            'email' => $email,
            'phone' => $phone,
            'is_blocked' => $isBlocked,
        ];
    }

    /**
     * Selecciona el query builder adecuado según la conexión disponible.
     */
    private function customersQueryBuilder(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }

    /**
     * Verifica existencia de tabla en una conexión concreta antes de consultarla.
     * Evita errores de esquema cuando la base legacy no tiene ciertas tablas.
     */
    private function tableExistsByConnection(string $table, ?string $connection): bool
    {
        try {
            $schemaConnection = $connection ?? DB::getDefaultConnection();
            return Schema::connection($schemaConnection)->hasTable($table);
        } catch (\Throwable) {
            // Error de conexión: asume que la tabla no existe.
            return false;
        }
    }

    /**
     * Verifica existencia de columna para adaptar consultas a esquemas distintos.
     */
    private function columnExistsByConnection(string $table, string $column, ?string $connection): bool
    {
        try {
            $schemaConnection = $connection ?? DB::getDefaultConnection();
            return Schema::connection($schemaConnection)->hasColumn($table, $column);
        } catch (\Throwable) {
            // Error de conexión: asume que la columna no existe.
            return false;
        }
    }

    /**
     * Resuelve el operador LIKE adecuado según el motor de base de datos.
     * PostgreSQL usa ILIKE (case-insensitive), MySQL/MariaDB usan LIKE.
     */
    private function likeOperatorByConnection(?string $connection): string
    {
        try {
            return $this->customersQueryBuilder($connection)->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        } catch (\Throwable) {
            // Por defecto usa LIKE si no se puede determinar el driver.
            return 'LIKE';
        }
    }

    /**
     * Crea una regla de descuento por cantidad.
     */
    public function storeBulkDiscount(Request $request): JsonResponse
    {
        $rule = BulkDiscountRule::query()->create($this->buildBulkDiscountPayload($request, false));

        return response()->json(['success' => true, 'message' => 'Regla creada.', 'id' => $rule->id], 201);
    }

    /**
     * Actualiza una regla de descuento por cantidad existente.
     */
    public function updateBulkDiscount(Request $request, int $id): JsonResponse
    {
        $rule = BulkDiscountRule::query()->find($id);

        // Si la regla no existe, retorna 404 antes de intentar actualizar.
        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        $rule->fill($this->buildBulkDiscountPayload($request, true));
        $rule->save();

        return response()->json(['success' => true, 'message' => 'Regla actualizada.']);
    }

    /**
     * Elimina una regla de descuento por cantidad y reporta ausencia cuando corresponde.
     */
    public function destroyBulkDiscount(int $id): JsonResponse
    {
        $deleted = BulkDiscountRule::query()->whereKey($id)->delete();

        // Si no se eliminó ningún registro, la regla no existía.
        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
    }

    /**
     * Normaliza nombres de campos de API y panel para persistir códigos de descuento.
     * Soporta alias como 'value'/'discount_value', 'start_date'/'starts_at', etc.
     */
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

        // Normaliza el código a mayúsculas sin espacios.
        if (array_key_exists('code', $data)) {
            $payload['code'] = strtoupper(trim((string) $data['code']));
        }

        // Resuelve el ID del tipo de descuento desde ID explícito o nombre lógico.
        if (array_key_exists('discount_type_id', $data) || array_key_exists('type', $data)) {
            $payload['discount_type_id'] = $this->resolveDiscountTypeId(
                $data['discount_type_id'] ?? null,
                $data['type'] ?? null,
            );
        }

        // Soporta tanto 'value' como 'discount_value' (alias del frontend).
        if (array_key_exists('value', $data) || array_key_exists('discount_value', $data)) {
            $payload['discount_value'] = (float) ($data['value'] ?? $data['discount_value'] ?? 0);
        }

        if (array_key_exists('max_uses', $data)) {
            $payload['max_uses'] = $data['max_uses'] ?? null;
        }

        // Soporta 'start_date' o 'starts_at' según lo que envíe el panel.
        if (array_key_exists('start_date', $data) || array_key_exists('starts_at', $data)) {
            $payload['start_date'] = $data['start_date'] ?? $data['starts_at'] ?? null;
        }

        // Soporta 'end_date' o 'expires_at' para compatibilidad con formularios legacy.
        if (array_key_exists('end_date', $data) || array_key_exists('expires_at', $data)) {
            $payload['end_date'] = $data['end_date'] ?? $data['expires_at'] ?? null;
        }

        // Soporta 'is_active' o 'active' según la convención del cliente.
        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        if (array_key_exists('is_single_use', $data)) {
            $payload['is_single_use'] = (bool) ($data['is_single_use'] ?? false);
        }

        return $payload;
    }

    /**
     * Normaliza nombres de campos para persistir reglas de descuento por cantidad.
     * Soporta alias como 'discount_percentage'/'discount_percent', 'is_active'/'active'.
     */
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

    /**
     * Convierte un modelo de código de descuento al contrato JSON del admin.
     * Proporciona alias para compatibilidad con el frontend (value/discount_value, starts_at/start_date, etc.).
     */
    private function transformCode(DiscountCode $code): array
    {
        // Determina el tipo (fixed o percent) según el nombre del tipo asociado.
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

    /**
     * Convierte una regla de cantidad al contrato JSON del admin.
     */
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

    /**
     * Resuelve el tipo de descuento desde ID explícito o nombre lógico.
     * Si no existe, lo crea automáticamente (firstOrCreate).
     */
    private function resolveDiscountTypeId(?int $discountTypeId, ?string $type): int
    {
        // Si ya se proporcionó un ID explícito, lo usa directamente.
        if ($discountTypeId) {
            return $discountTypeId;
        }

        // Mapea nombres lógicos a nombres internos de tipos de descuento.
        $normalizedType = Str::lower(trim((string) $type));
        $targetName = in_array($normalizedType, ['fixed', 'monto fijo', 'fixed_amount'], true)
            ? 'fixed_amount'
            : 'percentage';

        // Crea el tipo si no existe (firstOrCreate) para mantener consistencia.
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
