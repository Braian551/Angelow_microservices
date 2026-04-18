<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminPaymentController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    /**
     * Lista todas las transacciones con filtros para el admin.
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
                    'data' => collect($legacyRows)
                        ->map(fn ($payment) => $this->transformPaymentRow($payment, 'legacy'))
                        ->values(),
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

        return response()->json([
            'success' => true,
            'data'    => $paymentRows,
            'meta'    => $paginationMeta,
        ]);
    }

    private function buildPaymentsQuery(Request $request, ?string $connection = null)
    {
        $query = $connection
            ? DB::connection($connection)->table('payment_transactions as pt')
            : DB::table('payment_transactions as pt');

        $hasUsersTable = $this->hasTable('users', $connection);
        $hasUsersNameColumn = $hasUsersTable && $this->hasColumn('users', 'name', $connection);
        $hasUsersIdColumn = $hasUsersTable && $this->hasColumn('users', 'id', $connection);
        $joinedUsers = $hasUsersNameColumn && $hasUsersIdColumn;

        if ($joinedUsers) {
            $query->leftJoin('users as u', 'pt.user_id', '=', 'u.id');
        }

        $selectColumns = ['pt.*'];

        if ($joinedUsers) {
            $selectColumns[] = DB::raw("NULLIF(TRIM(u.name), '') as customer_name");
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

            $query->where(function ($q) use ($search, $connection, $joinedUsers) {
                $q->where('pt.reference_number', 'like', "%{$search}%")
                    ->orWhere('pt.user_id', 'like', "%{$search}%")
                    ->orWhere('pt.order_id', 'like', "%{$search}%");

                if ($joinedUsers) {
                    $q->orWhere('u.name', 'like', "%{$search}%");
                }

                if ($this->hasColumn('payment_transactions', 'billing_email', $connection)) {
                    $q->orWhere('pt.billing_email', 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }

    private function hasColumn(string $table, string $column, ?string $connection = null): bool
    {
        try {
            $dbConnection = $connection ?: config('database.default');

            return Schema::connection($dbConnection)->hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function hasTable(string $table, ?string $connection = null): bool
    {
        try {
            $dbConnection = $connection ?: config('database.default');

            return Schema::connection($dbConnection)->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

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

    private function toRelativePublicPath(string $absolutePath): string
    {
        $publicPath = str_replace('\\', '/', public_path());
        $normalized = str_replace('\\', '/', $absolutePath);

        return ltrim(str_replace($publicPath, '', $normalized), '/');
    }

    /**
     * Verifica/actualiza el estado de una transaccion.
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

    private function accountTypeLabel(?string $accountType): string
    {
        return match (strtolower((string) $accountType)) {
            'corriente' => 'Cuenta corriente',
            'ahorros' => 'Cuenta de ahorros',
            default => 'Cuenta bancaria',
        };
    }

    private function identificationTypeLabel(?string $identificationType): string
    {
        return match (strtolower((string) $identificationType)) {
            'cc' => 'Cédula',
            'ce' => 'Cédula de extranjería',
            'nit' => 'NIT',
            default => 'Documento',
        };
    }

    private function canUseLegacyConnection(): bool
    {
        try {
            DB::connection(self::LEGACY_CONNECTION)->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

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

    private function mapStatusForLegacyWrite(string $status): string
    {
        return match ($this->normalizeStatusForResponse($status)) {
            'approved' => 'verified',
            'rejected' => 'rejected',
            default => 'pending',
        };
    }

    private function legacyStatusesForFilter(string $status): array
    {
        return match ($this->normalizeStatusForResponse($status)) {
            'approved' => ['verified', 'approved'],
            'rejected' => ['rejected', 'failed', 'cancelled', 'canceled'],
            default => ['pending', ''],
        };
    }
}
