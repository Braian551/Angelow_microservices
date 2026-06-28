<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Controlador principal del módulo de pagos.
 * Gestiona transacciones de pago, validación de comprobantes,
 * listado de bancos colombianos y consulta de cuenta de pago.
 * Soporta doble origen de datos (microservicio y legacy) durante la migración.
 */
class PaymentController extends Controller
{
    /**
     * Nombre de la conexión a la base de datos legacy para fallback durante migración.
     */
    private const LEGACY_CONNECTION = 'legacy_mysql';

    /**
     * Endpoints externos de la API de bancos de Colombia (intentos en orden de prioridad).
     * Se prueba cada uno secuencialmente hasta obtener respuesta exitosa.
     */
    private const COLOMBIA_BANKS_ENDPOINTS = [
        'https://api-colombia.com/api/v1/Bank',
        'https://api-colombia.com/api/v1/bank',
        'https://api-colombia.com/api/v1/banks',
    ];

    /**
     * Lista las transacciones de pago recientes con filtro opcional por estado.
     * Retorna hasta 100 registros ordenados por fecha de creación descendente.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('payment_transactions')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json([
            'data' => $query->limit(100)->get(),
        ]);
    }

    /**
     * Crea una nueva transacción de pago en el sistema.
     * Valida los datos de entrada, procesa el comprobante de pago si se adjunta
     * y persiste el registro en la tabla payment_transactions.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'payment_proof_name' => ['nullable', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['nullable', 'string', 'max:30'],
        ]);

        $paymentProofPath = $this->resolvePaymentProofInput($request, $data['user_id'] ?? null);

        $payload = [
            'order_id' => $data['order_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'amount' => $data['amount'],
            'reference_number' => $data['reference_number'] ?? null,
            'payment_proof' => $paymentProofPath,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('payment_transactions', 'bank_code')) {
            $payload['bank_code'] = $data['bank_code'] ?? null;
        }

        if (Schema::hasColumn('payment_transactions', 'payment_method')) {
            $payload['payment_method'] = $data['payment_method'] ?? null;
        }

        $id = DB::table('payment_transactions')->insertGetId($payload);

        return response()->json([
            'message' => 'Transaccion creada',
            'id' => $id,
        ], 201);
    }

    /**
     * Resuelve la ruta del comprobante de pago desde el request.
     * Si viene como archivo, lo almacena; si viene como string, lo usa directamente.
     * Retorna la ruta relativa del archivo o null si no hay comprobante.
     */
    private function resolvePaymentProofInput(Request $request, ?string $userId): ?string
    {
        if ($request->hasFile('payment_proof')) {
            $request->validate([
                'payment_proof' => ['file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
            ]);

            return $this->storePaymentProof($request->file('payment_proof'), $userId);
        }

        $request->validate([
            'payment_proof' => ['nullable', 'string', 'max:255'],
        ]);

        $proofPath = trim((string) $request->input('payment_proof', ''));

        return $proofPath !== '' ? $proofPath : null;
    }

    /**
     * Almacena el archivo del comprobante de pago en el directorio público.
     * Genera un nombre seguro basado en userId y timestamp para evitar colisiones.
     * Valida que la extensión sea aceptada (jpg, jpeg, png, pdf, webp) y limita a 5MB.
     */
    private function storePaymentProof(UploadedFile $file, ?string $userId): string
    {
        $directory = public_path('uploads/payment_proofs');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $safeUserId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($userId ?: 'guest')) ?: 'guest';
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $fileName = sprintf('proof_%s_%s.%s', $safeUserId, time(), $extension);

        $file->move($directory, $fileName);

        return 'uploads/payment_proofs/' . $fileName;
    }

    /**
     * Verifica y actualiza el estado de una transacción de pago.
     * Permite aprobar (approved), rechazar (rejected) o dejar en pendiente (pending).
     * Registra notas del administrador y usuario que realiza la verificación.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
            'admin_notes' => ['nullable', 'string'],
            'verified_by' => ['nullable', 'string', 'max:20'],
        ]);

        $updated = DB::table('payment_transactions')
            ->where('id', $id)
            ->update([
                'status' => $data['status'],
                'admin_notes' => $data['admin_notes'] ?? null,
                'verified_by' => $data['verified_by'] ?? null,
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Transaccion no encontrada'], 404);
        }

        return response()->json([
            'message' => 'Transaccion actualizada',
        ]);
    }

    /**
     * Retorna el listado de bancos colombianos activos para el checkout.
     * Estrategia de carga: 1) intenta tabla local, 2) hidrata desde API externa,
     * 3) fallback a legacy, 4) usa catálogo predefinido como último recurso.
     * Este flujo evita dejar al usuario sin opciones de pago.
     */
    public function banks(): JsonResponse
    {
        $banks = $this->resolveActiveBanks();

        if ($banks->isEmpty()) {
            $this->hydrateBanksCatalogFromApi();
            $banks = $this->resolveActiveBanks();
        }

        if ($banks->isEmpty() && $this->canUseLegacyConnection()) {
            try {
                $banks = $this->resolveActiveBanks(self::LEGACY_CONNECTION);
            } catch (Throwable) {
                // Si falla la conexión legacy, se conserva la respuesta vacía sin romper el endpoint.
            }
        }

        if ($banks->isEmpty()) {
            $this->seedFallbackBankCatalog();
            $banks = $this->resolveActiveBanks();
        }

        return response()->json(['data' => $banks]);
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
     * Hidrata el catálogo de bancos desde la API externa de bancos de Colombia.
     * Intenta los endpoints configurados secuencialmente hasta tener éxito.
     * Inserta o actualiza los bancos en la tabla local usando upsert.
     */
    private function hydrateBanksCatalogFromApi(): void
    {
        if (!Schema::hasTable('colombian_banks')) {
            return;
        }

        foreach (self::COLOMBIA_BANKS_ENDPOINTS as $endpoint) {
            try {
                $response = Http::timeout(6)->acceptJson()->get($endpoint);

                if (!$response->successful()) {
                    continue;
                }

                $banks = $this->normalizeBanksApiPayload($response->json());

                if (count($banks) === 0) {
                    continue;
                }

                DB::table('colombian_banks')->upsert($banks, ['bank_code'], ['bank_name', 'is_active', 'trial551']);

                return;
            } catch (Throwable) {
                // Continúa con el siguiente endpoint sin romper el flujo.
            }
        }
    }

    /**
     * Normaliza el payload de la API externa al formato interno del sistema.
     * Maneja variaciones en los nombres de campos (bank_name, name, nombre)
     * y genera códigos sintéticos si la API no los provee.
     * Limpia caracteres no válidos del código bancario.
     */
    private function normalizeBanksApiPayload(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $items = $payload;
        if (array_key_exists('data', $payload) && is_array($payload['data'])) {
            $items = $payload['data'];
        }

        $rows = [];
        $syntheticCode = 700;

        foreach ($items as $item) {
            $bank = is_object($item) ? (array) $item : (is_array($item) ? $item : null);

            if (!$bank) {
                continue;
            }

            $name = trim((string) ($bank['bank_name'] ?? $bank['name'] ?? $bank['nombre'] ?? ''));
            if ($name === '') {
                continue;
            }

            $rawCode = trim((string) ($bank['bank_code'] ?? $bank['code'] ?? $bank['codigo'] ?? ''));
            $cleanCode = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $rawCode));

            if ($cleanCode === '' || strlen($cleanCode) > 10) {
                $cleanCode = (string) $syntheticCode;
                $syntheticCode++;
            }

            if (preg_match('/^\d+$/', $cleanCode) === 1) {
                $cleanCode = str_pad($cleanCode, 3, '0', STR_PAD_LEFT);
            }

            $rows[$cleanCode] = [
                'bank_code' => $cleanCode,
                'bank_name' => $name,
                'is_active' => true,
                'trial551' => 'T',
            ];
        }

        return array_values($rows);
    }

    /**
     * Inserta el catálogo de fallback de bancos colombianos.
     * Se usa cuando no hay datos locales ni respuesta de API externa.
     * Incluye bancos tradicionales y billeteras digitales (Nequi, Daviplata, Movii).
     */
    private function seedFallbackBankCatalog(): void
    {
        if (!Schema::hasTable('colombian_banks')) {
            return;
        }

        DB::table('colombian_banks')->upsert($this->fallbackBankCatalog(), ['bank_code'], ['bank_name', 'is_active', 'trial551']);
    }

    /**
     * Retorna el catálogo de fallback de bancos colombianos.
     * Se usa cuando no hay datos locales ni respuesta de API externa.
     * Incluye bancos tradicionales y billeteras digitales (Nequi, Daviplata, Movii).
     */
    private function fallbackBankCatalog(): array
    {
        return [
            ['bank_code' => '001', 'bank_name' => 'Banco de Bogotá', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '002', 'bank_name' => 'Banco Popular', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '006', 'bank_name' => 'Banco Santander', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '007', 'bank_name' => 'BBVA Colombia', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '009', 'bank_name' => 'Citibank', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '012', 'bank_name' => 'Banco GNB Sudameris', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '013', 'bank_name' => 'Banco AV Villas', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '014', 'bank_name' => 'Banco de Occidente', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '019', 'bank_name' => 'Bancoomeva', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '023', 'bank_name' => 'Banco Itaú', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '031', 'bank_name' => 'Bancolombia', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '032', 'bank_name' => 'Banco Caja Social', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '040', 'bank_name' => 'Banco Agrario de Colombia', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '051', 'bank_name' => 'Bancamía', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '052', 'bank_name' => 'Banco WWB', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '053', 'bank_name' => 'Banco Falabella', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '054', 'bank_name' => 'Banco Pichincha', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '058', 'bank_name' => 'Banco ProCredit', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '059', 'bank_name' => 'Banco Mundo Mujer', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '060', 'bank_name' => 'Banco Finandina', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '061', 'bank_name' => 'Bancoomeva S.A.', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '062', 'bank_name' => 'Banco Davivienda', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '063', 'bank_name' => 'Banco Cooperativo Coopcentral', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '065', 'bank_name' => 'Banco Santander', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '101', 'bank_name' => 'Nequi', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '102', 'bank_name' => 'Daviplata', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '103', 'bank_name' => 'Movii', 'is_active' => true, 'trial551' => 'T'],
        ];
    }

    /**
     * Retorna la cuenta de pago activa para el checkout.
     * Consulta primero la tabla local del microservicio;
     * si no hay datos, intenta desde legacy como respaldo.
     * Resuelve el nombre del banco asociado.
     */
    public function paymentAccount(): JsonResponse
    {
        $account = null;

        if (Schema::hasTable('bank_account_config')) {
            $account = $this->resolveActivePaymentAccount();
        }

        if (!$account && $this->canUseLegacyConnection()) {
            $account = $this->resolveActivePaymentAccount(self::LEGACY_CONNECTION);
        }

        return response()->json([
            'data' => $this->transformPaymentAccount($account),
        ]);
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
}
