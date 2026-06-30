<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Controlador administrativo del módulo de pagos.
 * Gestiona el CRUD de transacciones de pago, verificación manual,
 * configuración de cuenta de pago y listado de bancos para el admin.
 * Soporta doble origen de datos (microservicio y legacy) durante la migración.
 */
class AdminPaymentController extends Controller
{
    /**
     * Nombre de la conexión a la base de datos legacy para fallback durante migración.
     */
    private const LEGACY_CONNECTION = 'legacy_mysql';

    /**
     * Lista todas las transacciones con filtros para el admin.
     * Soporta paginación, búsqueda por múltiples campos, filtro por estado y rango de fechas.
     * Si no hay datos en microservicio, hace fallback a legacy.
     * Hidrata datos del usuario (nombre, email) desde auth-service.
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = max(1, min($request->integer('per_page', 50), 100));

        $query = $this->buildPaymentsQuery($request);
        $payments = $query->paginate($perPage, ['*'], 'page', $page);

        if ($payments->total() === 0 && $this->canUseLegacyConnection()) {
            try {
                $legacyQuery = $this->buildPaymentsQuery($request, self::LEGACY_CONNECTION);
                $legacyTotal = (clone $legacyQuery)->count();
                $legacyRows = (clone $legacyQuery)
                    ->forPage($page, $perPage)
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $this->hydratePaymentsWithAuthProfiles(
                        collect($legacyRows)
                        ->map(fn ($payment) => $this->transformPaymentRow($payment, 'legacy'))
                        ->values()
                    ),
                    'meta' => [
                        'current_page' => $page,
                        'last_page' => $legacyTotal > 0 ? (int) ceil($legacyTotal / $perPage) : 1,
                        'total' => $legacyTotal,
                    ],
                ]);
            } catch (Throwable) {
                // Si falla el fallback, se mantiene la respuesta vacía de la BD distribuida.
            }
        }

        $paginationMeta = [
            'current_page' => $payments->currentPage(),
            'last_page'    => $payments->lastPage(),
            'total'        => $payments->total(),
        ];

        $paymentRows = collect($payments->items())
            ->map(fn ($payment) => $this->transformPaymentRow($payment, 'microservice'))
            ->values();

        $paymentRows = $this->hydratePaymentsWithAuthProfiles($paymentRows);

        return response()->json([
            'success' => true,
            'data'    => $paymentRows,
            'meta'    => $paginationMeta,
        ]);
    }

    /**
     * Construye la consulta base de pagos con joins y filtros dinámicos.
     * Adapta la consulta según la conexión (microservicio o legacy) y
     * la existencia de columnas en el esquema (users, billing_email, etc.).
     * Normaliza la comparación de estados para ambos orígenes de datos.
     */
    private function buildPaymentsQuery(Request $request, ?string $connection = null)
    {
        $query = $connection
            ? DB::connection($connection)->table('payment_transactions as pt')
            : DB::table('payment_transactions as pt');

        $hasUsersTable = $this->hasTable('users', $connection);
        $hasUsersNameColumn = $hasUsersTable && $this->hasColumn('users', 'name', $connection);
        $hasUsersEmailColumn = $hasUsersTable && $this->hasColumn('users', 'email', $connection);
        $hasUsersIdColumn = $hasUsersTable && $this->hasColumn('users', 'id', $connection);
        $joinedUsers = $hasUsersIdColumn;
        $canSearchUserEmail = $joinedUsers && $hasUsersEmailColumn;

        if ($joinedUsers) {
            $query->leftJoin('users as u', 'pt.user_id', '=', 'u.id');
        }

        $selectColumns = ['pt.*'];

        if ($joinedUsers && $hasUsersNameColumn) {
            $selectColumns[] = DB::raw("NULLIF(TRIM(u.name), '') as customer_name");
        }

        if ($joinedUsers && $hasUsersEmailColumn) {
            $selectColumns[] = DB::raw("NULLIF(TRIM(u.email), '') as customer_email");
        }

        $query->select($selectColumns);

        $query->orderByDesc('pt.created_at');

        if ($request->filled('order_id')) {
            $query->where('pt.order_id', $request->integer('order_id'));
        }

        if ($request->filled('status')) {
            $requestedStatus = $request->string('status')->toString();

            if ($connection === self::LEGACY_CONNECTION) {
                $query->whereIn('pt.status', $this->legacyStatusesForFilter($requestedStatus));
            } else {
                $query->where('pt.status', $this->normalizeStatusForResponse($requestedStatus));
            }
        }

        if ($request->filled('from')) {
            $query->where('pt.created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('pt.created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search, $connection, $joinedUsers, $canSearchUserEmail) {
                $q->where('pt.reference_number', 'like', "%{$search}%")
                    ->orWhere('pt.user_id', 'like', "%{$search}%")
                    ->orWhere('pt.order_id', 'like', "%{$search}%");

                if ($joinedUsers) {
                    $q->orWhere('u.name', 'like', "%{$search}%");
                    if ($canSearchUserEmail) {
                        $q->orWhere('u.email', 'like', "%{$search}%");
                    }
                }

                if ($this->hasColumn('payment_transactions', 'billing_email', $connection)) {
                    $q->orWhere('pt.billing_email', 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }

    /**
     * Verifica si una columna existe en una tabla para una conexión dada.
     * Se usa para adaptar consultas dinámicamente a esquemas distintos
     * entre microservicio y legacy sin romper la ejecución.
     */
    private function hasColumn(string $table, string $column, ?string $connection = null): bool
    {
        try {
            $dbConnection = $connection ?: config('database.default');

            return Schema::connection($dbConnection)->hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Verifica si una tabla existe en una conexión dada.
     * Evita errores de esquema al consultar tablas que solo existen en legacy.
     */
    private function hasTable(string $table, ?string $connection = null): bool
    {
        try {
            $dbConnection = $connection ?: config('database.default');

            return Schema::connection($dbConnection)->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Transforma una fila de pago al formato de respuesta del admin.
     * Resuelve la URL pública del comprobante, normaliza el estado
     * y verifica si el archivo físico existe en disco.
     * Agrega metadata de origen (source) para trazabilidad.
     */
    private function transformPaymentRow(object $payment, string $source = 'microservice'): array
    {
        $proofPath = trim((string) ($payment->payment_proof ?? ''));
        $resolvedProofPath = $this->resolveExistingProofPath($proofPath);
        $proofUrl = $this->buildPublicProofUrl($resolvedProofPath ?: $proofPath);
        $normalizedStatus = $this->normalizeStatusForResponse($payment->status ?? null);

        return [
            ...(array) $payment,
            'source' => $source,
            'status' => $normalizedStatus,
            'proof_name' => $proofPath !== '' ? basename(str_replace('\\', '/', $resolvedProofPath ?: $proofPath)) : null,
            'proof_url' => $proofUrl,
            'proof_exists' => $resolvedProofPath !== null,
        ];
    }

    /**
     * Hidrata los pagos con datos de perfil del usuario desde auth-service.
     * Consulta perfiles en lote por user_id y completa nombre/email
     * cuando faltan en la transacción local.
     * Usa caché implícita del auth-service y maneja errores sin romper la respuesta.
     */
    private function hydratePaymentsWithAuthProfiles(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $userIds = $rows
            ->map(static fn (array $row): string => trim((string) ($row['user_id'] ?? '')))
            ->filter(static fn (string $userId): bool => $userId !== '')
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return $rows;
        }

        $profilesById = $this->fetchAuthProfilesByUserIds($userIds);
        if ($profilesById === []) {
            return $rows;
        }

        return $rows->map(static function (array $row) use ($profilesById): array {
            $userId = trim((string) ($row['user_id'] ?? ''));
            if ($userId === '' || !array_key_exists($userId, $profilesById)) {
                return $row;
            }

            $profile = $profilesById[$userId];

            if (trim((string) ($row['customer_name'] ?? '')) === '' && !empty($profile['name'])) {
                $row['customer_name'] = $profile['name'];
            }

            if (trim((string) ($row['customer_email'] ?? '')) === '' && !empty($profile['email'])) {
                $row['customer_email'] = $profile['email'];
            }

            return $row;
        })->values();
    }

    /**
     * Consulta perfiles de usuarios al auth-service por sus IDs.
     * Requiere token interno configurado en services.auth.internal_token.
     * Retorna array indexado por user_id con nombre y email validados.
     * En entorno de testing retorna vacío para evitar dependencias externas.
     */
    private function fetchAuthProfilesByUserIds(array $userIds): array
    {
        if ($userIds === [] || app()->environment('testing')) {
            return [];
        }

        $endpoint = $this->resolveAuthProfilesEndpoint();
        if ($endpoint === null) {
            return [];
        }

        try {
            $request = Http::acceptJson()->timeout(4);
            $token = trim((string) config('services.auth.internal_token', ''));

            if ($token !== '') {
                $request = $request->withHeaders(['X-Internal-Token' => $token]);
            }

            $response = $request->get($endpoint, [
                'ids' => implode(',', $userIds),
            ]);

            if (!$response->successful()) {
                return [];
            }

            $profiles = $response->json('data');
            if (!is_array($profiles)) {
                return [];
            }

            $indexedProfiles = [];
            foreach ($profiles as $profile) {
                if (!is_array($profile)) {
                    continue;
                }

                $id = trim((string) ($profile['id'] ?? ''));
                if ($id === '') {
                    continue;
                }

                $email = trim((string) ($profile['email'] ?? ''));

                $indexedProfiles[$id] = [
                    'name' => trim((string) ($profile['name'] ?? '')),
                    'email' => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                ];
            }

            return $indexedProfiles;
        } catch (Throwable $exception) {
            Log::warning('No se pudieron consultar perfiles desde auth-service para pagos admin.', [
                'users_count' => count($userIds),
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Resuelve el endpoint interno de perfiles del auth-service.
     * Acepta URL base con o sin sufijo /api y construye la ruta completa.
     */
    private function resolveAuthProfilesEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.auth.base_url', 'http://auth-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/internal/users/profiles';
        }

        return $baseUrl . '/api/internal/users/profiles';
    }

    /**
     * Construye la URL pública absoluta del comprobante de pago.
     * Normaliza rutas relativas (uploads/, payment_proofs/) y
     * valida URLs ya absolutas. Retorna null si la ruta está vacía.
     */
    private function buildPublicProofUrl(string $proofPath): ?string
    {
        if ($proofPath === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $proofPath);

        if (preg_match('/^https?:\/\//i', $normalized)) {
            return $normalized;
        }

        if (str_starts_with($normalized, '/uploads/')) {
            return $normalized;
        }

        if (str_starts_with($normalized, 'uploads/')) {
            return '/' . ltrim($normalized, '/');
        }

        if (str_starts_with($normalized, 'payment_proofs/')) {
            return '/uploads/' . ltrim($normalized, '/');
        }

        return '/uploads/payment_proofs/' . ltrim($normalized, '/');
    }

    /**
     * Resuelve la ruta física real del comprobante en disco.
     * Prueba múltiples candidatos (ruta completa, relativa, solo nombre)
     * buscando en uploads/payment_proofs y uploads/ para compatibilidad legacy.
     * Retorna la ruta relativa válida o null si no se encuentra el archivo.
     */
    private function resolveExistingProofPath(string $proofPath): ?string
    {
        if ($proofPath === '') {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $proofPath), '/');

        if (preg_match('/^https?:\/\//i', $normalized)) {
            return $normalized;
        }

        $candidates = $this->buildProofCandidates($normalized);

        foreach ($candidates as $candidate) {
            if (is_file(public_path($candidate))) {
                return $candidate;
            }
        }

        $basename = basename($normalized);

        if ($basename === '') {
            return null;
        }

        foreach ([public_path('uploads/payment_proofs'), public_path('uploads')] as $directory) {
            $found = $this->findProofByBasename($directory, $basename);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Construye lista de rutas candidatas para buscar el comprobante.
     * Maneja variaciones legacy: solo basename, ruta con uploads/, con payment_proofs/, etc.
     * Elimina duplicados y retorna array único de candidatos priorizados.
     */
    private function buildProofCandidates(string $normalized): array
    {
        $candidates = [];

        if (str_starts_with($normalized, 'uploads/')) {
            $candidates[] = $normalized;
        } elseif (str_starts_with($normalized, 'payment_proofs/')) {
            $candidates[] = 'uploads/' . $normalized;
        } else {
            $candidates[] = 'uploads/payment_proofs/' . $normalized;
            $candidates[] = 'uploads/' . $normalized;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Busca un archivo por nombre exacto (case-insensitive) en un directorio y subdirectorios.
     * Retorna la ruta relativa desde public_path si se encuentra.
     * Usa RecursiveIteratorIterator para recorrer toda la estructura.
     */
    private function findProofByBasename(string $directory, string $basename): ?string
    {
        if (!is_dir($directory)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strcasecmp($file->getFilename(), $basename) !== 0) {
                continue;
            }

            return $this->toRelativePublicPath($file->getPathname());
        }

        return null;
    }

    /**
     * Convierte una ruta absoluta del filesystem a ruta relativa pública.
     * Elimina el prefijo de public_path para obtener la URL servible por web.
     */
    private function toRelativePublicPath(string $absolutePath): string
    {
        $publicPath = str_replace('\\', '/', public_path());
        $normalized = str_replace('\\', '/', $absolutePath);

        return ltrim(str_replace($publicPath, '', $normalized), '/');
    }

    /**
     * Verifica/actualiza el estado de una transaccion.
     * Actualiza en microservicio y, si falla, intenta fallback a legacy
     * mapeando el estado normalizado al valor legacy correspondiente.
     * Inyecta el admin que realiza la acción para auditoría.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status'      => ['required', 'in:approved,rejected,pending'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $admin = $request->input('_admin_user', []);
        $normalizedStatus = $this->normalizeStatusForResponse($data['status']);

        $updated = DB::table('payment_transactions')
            ->where('id', $id)
            ->update([
                'status'      => $normalizedStatus,
                'admin_notes' => $data['admin_notes'] ?? null,
                'verified_by' => $admin['id'] ?? null,
                'verified_at' => now(),
                'updated_at'  => now(),
            ]);

        if (!$updated && $this->canUseLegacyConnection()) {
            try {
                $updated = DB::connection(self::LEGACY_CONNECTION)
                    ->table('payment_transactions')
                    ->where('id', $id)
                    ->update([
                        'status'      => $this->mapStatusForLegacyWrite($normalizedStatus),
                        'admin_notes' => $data['admin_notes'] ?? null,
                        'verified_by' => $admin['id'] ?? null,
                        'verified_at' => now(),
                        'updated_at'  => now(),
                    ]);
            } catch (Throwable) {
                $updated = 0;
            }
        }

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Transaccion no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Transaccion actualizada.']);
    }

    /**
     * Devuelve cuenta activa y listado de bancos para configuración administrativa.
     * Combina datos de microservicio con fallback a legacy si es necesario.
     */
    public function accountSettings(): JsonResponse
    {
        $banks = $this->resolveActiveBanks();
        $account = $this->resolveActivePaymentAccount();

        if ((!$account || $banks->isEmpty()) && $this->canUseLegacyConnection()) {
            if ($banks->isEmpty()) {
                $banks = $this->resolveActiveBanks(self::LEGACY_CONNECTION);
            }

            if (!$account) {
                $account = $this->resolveActivePaymentAccount(self::LEGACY_CONNECTION);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $this->transformPaymentAccount($account),
                'banks' => $banks->values(),
            ],
        ]);
    }

    /**
     * Crea o actualiza la cuenta visible al cliente en el checkout de pagos.
     * Desactiva cuentas previas si la nueva se marca como activa.
     * Soporta creación e idempotencia de actualización por ID.
     * Retorna la cuenta guardada con etiquetas legibles.
     */
    public function saveAccountSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'bank_code' => ['required', 'string', 'max:10'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_type' => ['required', 'in:ahorros,corriente'],
            'account_holder' => ['required', 'string', 'max:100'],
            'identification_type' => ['required', 'in:cc,ce,nit'],
            'identification_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:15'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $admin = $request->input('_admin_user', []);
        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;
        $accountId = (int) ($data['id'] ?? 0);
        $now = now();

        if ($isActive) {
            DB::table('bank_account_config')->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
        }

        $payload = [
            'bank_code' => $data['bank_code'],
            'account_number' => trim((string) $data['account_number']),
            'account_type' => strtolower(trim((string) $data['account_type'])),
            'account_holder' => trim((string) $data['account_holder']),
            'identification_type' => strtolower(trim((string) $data['identification_type'])),
            'identification_number' => trim((string) $data['identification_number']),
            'email' => $data['email'] ? trim((string) $data['email']) : null,
            'phone' => $data['phone'] ? trim((string) $data['phone']) : null,
            'is_active' => $isActive,
            'updated_at' => $now,
        ];

        $savedId = null;
        $message = 'Configuración de cuenta guardada.';

        if ($accountId > 0 && DB::table('bank_account_config')->where('id', $accountId)->exists()) {
            DB::table('bank_account_config')
                ->where('id', $accountId)
                ->update($payload);

            $savedId = $accountId;
            $message = 'Configuración de cuenta actualizada.';
        } else {
            $savedId = DB::table('bank_account_config')->insertGetId([
                ...$payload,
                'created_by' => (string) ($admin['id'] ?? 'system'),
                'created_at' => $now,
            ]);
        }

        $account = $this->resolvePaymentAccountById((int) $savedId);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $this->transformPaymentAccount($account),
        ]);
    }

    /**
     * Consulta los bancos activos desde una conexión específica.
     * Retorna colección vacía si la tabla no existe o hay error de conexión.
     */
    private function resolveActiveBanks(?string $connection = null)
    {
        try {
            $query = $connection
                ? DB::connection($connection)->table('colombian_banks')
                : DB::table('colombian_banks');

            return $query
                ->where('is_active', true)
                ->orderBy('bank_name')
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Consulta la cuenta de pago activa desde una conexión específica.
     * Realiza join con la tabla de bancos para obtener el nombre del banco.
     * Agrega una marca 'source' para indicar si los datos vienen de microservicio o legacy.
     */
    private function resolveActivePaymentAccount(?string $connection = null): ?object
    {
        try {
            $query = $connection
                ? DB::connection($connection)->table('bank_account_config as bac')
                : DB::table('bank_account_config as bac');

            $account = $query
                ->leftJoin('colombian_banks as cb', 'bac.bank_code', '=', 'cb.bank_code')
                ->select([
                    'bac.id',
                    'bac.bank_code',
                    'bac.account_number',
                    'bac.account_type',
                    'bac.account_holder',
                    'bac.identification_type',
                    'bac.identification_number',
                    'bac.email',
                    'bac.phone',
                    'bac.is_active',
                    'bac.created_by',
                    'bac.created_at',
                    'bac.updated_at',
                    'cb.bank_name',
                ])
                ->where('bac.is_active', true)
                ->orderByDesc('bac.updated_at')
                ->orderByDesc('bac.created_at')
                ->first();

            if (!$account) {
                return null;
            }

            $account->source = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';

            return $account;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Consulta una cuenta de pago por su ID primario.
     * Incluye join con bancos para nombre del banco.
     * Retorna null si no existe o ID inválido.
     */
    private function resolvePaymentAccountById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('bank_account_config as bac')
            ->leftJoin('colombian_banks as cb', 'bac.bank_code', '=', 'cb.bank_code')
            ->select([
                'bac.id',
                'bac.bank_code',
                'bac.account_number',
                'bac.account_type',
                'bac.account_holder',
                'bac.identification_type',
                'bac.identification_number',
                'bac.email',
                'bac.phone',
                'bac.is_active',
                'bac.created_by',
                'bac.created_at',
                'bac.updated_at',
                'cb.bank_name',
            ])
            ->where('bac.id', $id)
            ->first();
    }

    /**
     * Transforma el registro de cuenta de pago al formato de respuesta JSON.
     * Agrega etiquetas legibles para tipo de cuenta y tipo de identificación.
     */
    private function transformPaymentAccount(?object $account): ?array
    {
        if (!$account) {
            return null;
        }

        return [
            ...(array) $account,
            'source' => $account->source ?? 'microservice',
            'account_type_label' => $this->accountTypeLabel($account->account_type ?? null),
            'identification_type_label' => $this->identificationTypeLabel($account->identification_type ?? null),
        ];
    }

    /**
     * Retorna la etiqueta legible para el tipo de cuenta bancaria.
     * Ejemplos: 'corriente' -> 'Cuenta corriente', 'ahorros' -> 'Cuenta de ahorros'.
     */
    private function accountTypeLabel(?string $accountType): string
    {
        return match (strtolower((string) $accountType)) {
            'corriente' => 'Cuenta corriente',
            'ahorros' => 'Cuenta de ahorros',
            default => 'Cuenta bancaria',
        };
    }

    /**
     * Retorna la etiqueta legible para el tipo de identificación.
     * Ejemplos: 'cc' -> 'Cédula', 'ce' -> 'Cédula de extranjería', 'nit' -> 'NIT'.
     */
    private function identificationTypeLabel(?string $identificationType): string
    {
        return match (strtolower((string) $identificationType)) {
            'cc' => 'Cédula',
            'ce' => 'Cédula de extranjería',
            'nit' => 'NIT',
            default => 'Documento',
        };
    }

    /**
     * Verifica si la conexión a la base de datos legacy está disponible.
     * Se usa para decidir si se puede hacer fallback a datos heredados.
     */
    private function canUseLegacyConnection(): bool
    {
        try {
            DB::connection(self::LEGACY_CONNECTION)->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Normaliza el estado de pago a los valores canónicos del microservicio.
     * Mapea variantes legacy (verified, paid) a 'approved',
     * y (failed, cancelled, canceled) a 'rejected'.
     * Vacío o desconocido se normaliza a 'pending'.
     */
    private function normalizeStatusForResponse(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            '', 'pending' => 'pending',
            'approved', 'verified', 'paid' => 'approved',
            'rejected', 'failed', 'cancelled', 'canceled' => 'rejected',
            default => 'pending',
        };
    }

    /**
     * Mapea el estado normalizado al valor que espera la base legacy.
     * 'approved' -> 'verified', 'rejected' -> 'rejected', resto -> 'pending'.
     * Se usa al escribir en la tabla legacy durante fallback.
     */
    private function mapStatusForLegacyWrite(string $status): string
    {
        return match ($this->normalizeStatusForResponse($status)) {
            'approved' => 'verified',
            'rejected' => 'rejected',
            default => 'pending',
        };
    }

    /**
     * Retorna los estados legacy que corresponden a un filtro normalizado.
     * Para 'approved': ['verified', 'approved']
     * Para 'rejected': ['rejected', 'failed', 'cancelled', 'canceled']
     * Para 'pending': ['pending', '']
     * Permite filtrar en legacy usando los valores históricos.
     */
    private function legacyStatusesForFilter(string $status): array
    {
        return match ($this->normalizeStatusForResponse($status)) {
            'approved' => ['verified', 'approved'],
            'rejected' => ['rejected', 'failed', 'cancelled', 'canceled'],
            default => ['pending', ''],
        };
    }
}
