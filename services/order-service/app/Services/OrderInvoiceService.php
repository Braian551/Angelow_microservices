<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio para la generación, envío y gestión de facturas electrónicas.
 * Orquesta la lógica de negocio entre pedidos, clientes, PDF y notificaciones.
 */
class OrderInvoiceService
{
    /** Conexión a la base de datos heredada (legacy) */
    private const LEGACY_CONNECTION = 'legacy_mysql';
    /** Tipo de notificación por defecto para el microservicio de notificaciones */
    private const DEFAULT_NOTIFICATION_TYPE_ID = 1;

    /** Estados de orden que califican para generar factura */
    private const COMPLETED_ORDER_STATUSES = ['delivered', 'completed'];
    /** Estados de pago que califican para generar factura */
    private const PAID_PAYMENT_STATUSES = ['paid', 'verified', 'approved'];
    /** Caché en memoria de perfiles de usuario obtenidos desde auth-service */
    private array $authProfilesById = [];

    /**
     * Envía el correo de confirmación de checkout al cliente.
     * Construye el payload de la orden, resuelve el correo del destinatario y
     * envía el HTML de confirmación vía Mail.
     *
     * @param int $orderId Identificador de la orden.
     * @param array $context Datos contextuales (email, referencia de pago, etc.).
     * @param string|null $preferredConnection Conexión preferida (legacy o distribuida).
     * @return array Resultado de la operación con código y mensaje.
     */
    public function sendCheckoutConfirmationEmail(int $orderId, array $context = [], ?string $preferredConnection = null): array
    {
        // Construye el payload completo de la orden (datos + items + conexión)
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'No encontramos la orden para enviar el correo de confirmación.',
            ];
        }

        $order = $payload['order'];
        $items = $payload['items'];

        // Intenta obtener el email primero del contexto (checkout), luego de la orden
        $customerEmail = $this->resolveContextCustomerEmail($context)
            ?? $this->resolveCustomerEmail($order, $payload['connection']);
        if ($customerEmail === null) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La orden no tiene un correo válido para el envío de confirmación.',
            ];
        }

        $customerName = $this->resolveCustomerName($order, $payload['connection']);
        // Usa el número de orden visible o el ID como respaldo
        $orderLabel = trim((string) ($order['order_number'] ?? ''));
        if ($orderLabel === '') {
            $orderLabel = '#' . (string) ($order['id'] ?? $orderId);
        }

        $subject = 'Confirmación de pedido ' . $orderLabel;
        // Genera el HTML del correo de confirmación (ver buildCheckoutConfirmationHtml abajo)
        $html = $this->buildCheckoutConfirmationHtml($order, $items, $customerName, $context);

        try {
            // Envía el correo usando el driver de Laravel Mail
            Mail::html($html, function ($mail) use ($customerEmail, $customerName, $subject): void {
                $mail->to($customerEmail, $customerName)->subject($subject);
            });

            return [
                'ok' => true,
                'message' => 'Correo de confirmación enviado.',
                'order_id' => $orderId,
            ];
        } catch (Throwable $exception) {
            // Registra el error pero no relanza la excepción; retorna respuesta controlada
            Log::warning('No se pudo enviar correo de confirmación de checkout.', [
                'order_id' => $orderId,
                'email' => $customerEmail,
                'error' => $exception->getMessage(),
                'mailer' => (string) config('mail.default', 'log'),
            ]);

            return [
                'ok' => false,
                'code' => 500,
                'message' => 'No se pudo enviar el correo de confirmación.',
            ];
        }
    }

    /**
     * Asegura que una orden completada tenga su factura generada.
     * Verifica condiciones de entrega y pago; si ya existe factura, retorna sin duplicar.
     * Es el método principal invocado desde listeners o jobs de finalización de orden.
     *
     * @param int $orderId Identificador de la orden.
     * @param string|null $preferredConnection Conexión preferida.
     * @return array Resultado con indicador de éxito y mensaje.
     */
    public function ensureInvoiceForCompletedOrder(int $orderId, ?string $preferredConnection = null): array
    {
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'Orden no encontrada para generar factura.',
            ];
        }

        $order = $payload['order'];

        // Verifica que la orden esté entregada y el pago verificado
        if (!$this->isReadyForInvoice($order)) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'La orden aún no cumple condiciones de entrega y pago para facturar.',
            ];
        }

        // Si ya existe número de factura o fecha, se omite la generación
        if ($this->orderHasInvoice($order)) {
            return [
                'ok' => true,
                'already_generated' => true,
                'message' => 'La factura ya estaba generada previamente.',
                'invoice_number' => (string) ($order['invoice_number'] ?? ''),
            ];
        }

        // Genera la factura y la envía (forceSend = false porque es primera vez)
        return $this->generateAndSendInvoice($payload, false);
    }

    /**
     * Reenvía la factura de una orden por correo electrónico.
     * Requiere que la orden esté en estado entregado y pago verificado.
     * Útil para que el administrador o el cliente soliciten un reenvío.
     *
     * @param int $orderId Identificador de la orden.
     * @param string|null $preferredConnection Conexión preferida.
     * @return array Resultado del reenvío.
     */
    public function resendInvoiceEmail(int $orderId, ?string $preferredConnection = null): array
    {
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'Orden no encontrada para reenviar factura.',
            ];
        }

        $order = $payload['order'];

        if (!$this->isReadyForInvoice($order)) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La factura solo se envía cuando la orden está entregada y el pago verificado.',
            ];
        }

        // forceSend = true para reenviar incluso si ya se había enviado antes
        return $this->generateAndSendInvoice($payload, true);
    }

    /**
     * Genera y retorna el contenido binario del PDF de factura para descarga directa.
     * No persiste ni envía por correo; solo devuelve el PDF para que el controlador
     * lo sirva como respuesta HTTP.
     *
     * @param int $orderId Identificador de la orden.
     * @param string|null $preferredConnection Conexión preferida.
     * @return array Contenido del PDF y nombre de archivo sugerido.
     */
    public function buildInvoicePdfForDownload(int $orderId, ?string $preferredConnection = null): array
    {
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'No encontramos la orden solicitada.',
            ];
        }

        $order = $payload['order'];
        // Permite descargar si está lista para facturar O si ya tiene factura generada
        if (!$this->isReadyForInvoice($order) && !$this->orderHasInvoice($order)) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La orden todavía no tiene una factura disponible.',
            ];
        }

        try {
            $invoiceNumber = $this->resolveInvoiceNumber($order);
            // Genera el PDF en memoria sin persistir en disco
            $pdfContent = $this->generateInvoicePdfContent($order, $payload['items'], $invoiceNumber);

            return [
                'ok' => true,
                'content' => $pdfContent,
                'filename' => 'factura_' . $this->sanitizeFileSegment($invoiceNumber) . '.pdf',
            ];
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar PDF de factura para descarga.', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'code' => 500,
                'message' => 'No se pudo generar el PDF de la factura.',
            ];
        }
    }

    /**
     * Lista las facturas generadas desde ambas conexiones (distribuida y legada).
     * Aplica filtros de búsqueda, estado, fechas y fuente, y unifica resultados.
     *
     * @param array $filters Filtros opcionales (search, status, payment_status, from_date, to_date, source).
     * @param int $limit Máximo de registros a retornar (capped a 500).
     * @return Collection Lista de facturas normalizadas.
     */
    public function listGeneratedInvoices(array $filters = [], int $limit = 200): Collection
    {
        // Limita el límite a un rango seguro 1-500
        $safeLimit = max(1, min($limit, 500));
        $safeFilters = $this->normalizeInvoiceFilters($filters);

        // Consulta ambas conexiones en paralelo para luego unificar
        $distributedRows = $this->fetchInvoicesByConnection(null, $safeFilters, $safeLimit * 2);
        $legacyRows = $this->fetchInvoicesByConnection(self::LEGACY_CONNECTION, $safeFilters, $safeLimit * 2);

        // Unifica resultados de ambas conexiones eliminando duplicados por invoice_number, order_number o source+id
        return $distributedRows
            ->concat($legacyRows)
            ->unique(function (array $row): string {
                $invoiceNumber = strtolower(trim((string) ($row['invoice_number'] ?? '')));
                if ($invoiceNumber !== '') {
                    return 'invoice:' . $invoiceNumber;
                }

                $orderNumber = strtolower(trim((string) ($row['order_number'] ?? '')));
                if ($orderNumber !== '') {
                    return 'order:' . $orderNumber;
                }

                return 'source:' . strtolower((string) ($row['order_source'] ?? 'microservice')) . ':id:' . (string) ($row['id'] ?? '0');
            })
            // Ordena por fecha de factura descendente (más reciente primero)
            ->sortByDesc(function (array $row): int {
                $rawDate = (string) ($row['invoice_date'] ?? $row['created_at'] ?? '');
                $timestamp = strtotime($rawDate);
                return $timestamp ?: 0;
            })
            ->values()
            ->take($safeLimit)
            ->values();
    }

    /**
     * Genera el PDF de la factura, persiste los metadatos en la base de datos,
     * envía el correo al cliente con el PDF adjunto y registra una notificación.
     * Es el núcleo del flujo de facturación invocado por los métodos públicos.
     *
     * @param array $payload Payload completo con order, items y connection.
     * @param bool $forceSend Si es true, falla si no hay email; si es false, continúa sin correo.
     * @return array Resultado completo de la operación.
     */
    private function generateAndSendInvoice(array $payload, bool $forceSend): array
    {
        $order = $payload['order'];
        $items = $payload['items'];
        $connection = $payload['connection'];
        $orderId = (int) ($order['id'] ?? 0);

        $customerEmail = $this->resolveCustomerEmail($order, $connection);
        // Si forceSend y no hay email, se detiene con error
        if ($forceSend && $customerEmail === null) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La orden no tiene correo válido para enviar la factura.',
            ];
        }

        $customerName = $this->resolveCustomerName($order, $connection);
        $invoiceNumber = $this->resolveInvoiceNumber($order);

        try {
            // Paso 1: Genera el contenido PDF de la factura (ver generateInvoicePdfContent)
            $pdfContent = $this->generateInvoicePdfContent($order, $items, $invoiceNumber);
            // Paso 2: Guarda número de factura y fecha en la orden (persistInvoiceMetadata)
            $this->persistInvoiceMetadata($connection, $orderId, $invoiceNumber);

            $emailSent = false;
            $emailError = null;

            // Paso 3: Envía el correo solo si hay un email válido
            if ($customerEmail !== null) {
                try {
                    $emailHtml = $this->buildInvoiceEmailHtml($order, $customerName, $invoiceNumber);

                    // Envía el PDF como adjunto dentro del HTML del correo
                    Mail::html($emailHtml, function ($mail) use ($customerEmail, $customerName, $invoiceNumber, $pdfContent): void {
                        $mail
                            ->to($customerEmail, $customerName)
                            ->subject('Factura electrónica ' . $invoiceNumber)
                            ->attachData(
                                $pdfContent,
                                'factura_' . $this->sanitizeFileSegment($invoiceNumber) . '.pdf',
                                ['mime' => 'application/pdf']
                            );
                    });

                    $emailSent = true;
                } catch (Throwable $exception) {
                    $emailError = $exception;
                    Log::warning('No se pudo enviar correo de factura al cliente.', [
                        'order_id' => $orderId,
                        'invoice_number' => $invoiceNumber,
                        'email' => $customerEmail,
                        'error' => $exception->getMessage(),
                        'mailer' => (string) config('mail.default', 'log'),
                    ]);
                }
            }

            // Paso 4: Registra notificación en notification-service (ver sendInvoiceNotification)
            $notificationSent = $this->sendInvoiceNotification($order, $invoiceNumber, $forceSend);

            // Si es un reenvío forzado y el email falló, se retorna error
            if ($forceSend && !$emailSent) {
                return [
                    'ok' => false,
                    'code' => 500,
                    'message' => $emailError
                        ? 'No fue posible reenviar la factura por correo en este momento.'
                        : 'La orden no tiene correo válido para reenviar la factura.',
                ];
            }

            if ($forceSend) {
                return [
                    'ok' => true,
                    'message' => 'Factura reenviada correctamente.',
                    'invoice_number' => $invoiceNumber,
                    'order_id' => $orderId,
                    'email_sent' => true,
                    'notification_sent' => $notificationSent,
                ];
            }

            if ($emailSent) {
                $message = 'Factura generada y enviada correctamente.';
            } elseif ($customerEmail === null) {
                $message = 'Factura generada. No se envió correo porque el cliente no tiene un email válido.';
            } else {
                $message = 'Factura generada. No se pudo enviar el correo en este momento.';
            }

            return [
                'ok' => true,
                'message' => $message,
                'invoice_number' => $invoiceNumber,
                'order_id' => $orderId,
                'email_sent' => $emailSent,
                'notification_sent' => $notificationSent,
            ];
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar/enviar la factura de la orden.', [
                'order_id' => $orderId,
                'invoice_number' => $invoiceNumber,
                'error' => $exception->getMessage(),
                'mailer' => (string) config('mail.default', 'log'),
            ]);

            return [
                'ok' => false,
                'code' => 500,
                'message' => 'No fue posible enviar la factura al cliente.',
            ];
        }
    }

    /**
     * Construye el payload completo de una orden incluyendo datos normalizados,
     * items y la conexión activa. Es la preparación que usan todos los flujos.
     *
     * @param int $orderId ID de la orden.
     * @param string|null $preferredConnection Conexión preferida (null = distribuida).
     * @return array|null Arreglo con 'order', 'items' y 'connection', o null si no existe.
     */
    private function buildOrderPayload(int $orderId, ?string $preferredConnection = null): ?array
    {
        // Resuelve la fuente de la orden probando conexiones (ver resolveOrderSource)
        ['order' => $order, 'connection' => $connection] = $this->resolveOrderSource($orderId, $preferredConnection);

        if (!$order) {
            return null;
        }

        // Normaliza los campos de la orden (ver normalizeOrder)
        $normalizedOrder = $this->normalizeOrder((array) $order, $connection);
        // Obtiene los items asociados desde la misma conexión
        $items = $this->fetchOrderItemsByConnection($connection, $orderId)
            ->map(static function ($item) {
                return [
                    'id' => (int) ($item->id ?? 0),
                    'product_name' => trim((string) ($item->product_name ?? 'Producto')), 
                    'variant_name' => trim((string) ($item->variant_name ?? '')),
                    'price' => (float) ($item->price ?? 0),
                    'quantity' => (int) ($item->quantity ?? 0),
                    'total' => (float) ($item->total ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'order' => $normalizedOrder,
            'items' => $items,
            'connection' => $connection,
        ];
    }

    /**
     * Obtiene las facturas desde una conexión específica aplicando filtros.
     * Resuelve dinámicamente los nombres de columna según el esquema real.
     *
     * @param string|null $connection Conexión de base de datos (null = por defecto).
     * @param array $filters Filtros normalizados (search, status, payment_status, fechas, source).
     * @param int $limit Límite de registros.
     * @return Collection Registros de facturas normalizados.
     */
    private function fetchInvoicesByConnection(?string $connection, array $filters, int $limit): Collection
    {
        if (!$this->hasTable('orders', $connection)) {
            return collect();
        }

        // Resuelve nombres de columna según el esquema real de cada conexión
        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';
        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $connection);
        $customerNameColumn = $this->firstExistingColumn('orders', ['user_name', 'customer_name', 'billing_name'], $connection);
        $customerEmailColumn = $this->firstExistingColumn('orders', ['user_email', 'customer_email', 'billing_email'], $connection);
        $customerPhoneColumn = $this->firstExistingColumn('orders', ['user_phone', 'customer_phone', 'billing_phone', 'phone'], $connection);
        $invoiceNumberColumn = $this->firstExistingColumn('orders', ['invoice_number'], $connection);
        $invoiceDateColumn = $this->firstExistingColumn('orders', ['invoice_date'], $connection);
        // Determina operador LIKE según el driver (ILIKE para PostgreSQL)
        $likeOperator = $this->likeOperator($connection);

        // Si no hay columnas de factura, no hay facturas que listar
        if (!$invoiceNumberColumn && !$invoiceDateColumn) {
            return collect();
        }

        try {
            $query = $this->query($connection)->table('orders')->select('orders.*');

            // Aliasea columnas con nombres diferentes al estándar para normalización
            if ($statusColumn !== 'status') {
                $query->addSelect("orders.{$statusColumn} as status");
            }

            if ($paymentStatusColumn && $paymentStatusColumn !== 'payment_status') {
                $query->addSelect("orders.{$paymentStatusColumn} as payment_status");
            }

            if ($customerNameColumn && $customerNameColumn !== 'user_name') {
                $query->addSelect("orders.{$customerNameColumn} as user_name");
            }

            if ($customerEmailColumn && $customerEmailColumn !== 'user_email') {
                $query->addSelect("orders.{$customerEmailColumn} as user_email");
            }

            if ($customerPhoneColumn && $customerPhoneColumn !== 'user_phone') {
                $query->addSelect("orders.{$customerPhoneColumn} as user_phone");
            }

            if ($invoiceNumberColumn && $invoiceNumberColumn !== 'invoice_number') {
                $query->addSelect("orders.{$invoiceNumberColumn} as invoice_number");
            }

            if ($invoiceDateColumn && $invoiceDateColumn !== 'invoice_date') {
                $query->addSelect("orders.{$invoiceDateColumn} as invoice_date");
            }

            // Filtra registros que tengan invoice_number o invoice_date (facturas existentes)
            $query->where(function ($invoiceQuery) use ($invoiceNumberColumn, $invoiceDateColumn): void {
                if ($invoiceNumberColumn) {
                    $invoiceQuery
                        ->whereNotNull("orders.{$invoiceNumberColumn}")
                        ->where("orders.{$invoiceNumberColumn}", '!=', '');
                }

                if ($invoiceDateColumn) {
                    $invoiceQuery->orWhereNotNull("orders.{$invoiceDateColumn}");
                }
            });

            // Aplica filtro de búsqueda textual (número de orden, factura, cliente)
            if ($filters['search'] !== '') {
                $term = $filters['search'];
                $query->where(function ($searchQuery) use ($term, $customerNameColumn, $customerEmailColumn, $invoiceNumberColumn, $likeOperator): void {
                    $searchQuery->where('orders.order_number', $likeOperator, "%{$term}%");

                    if ($invoiceNumberColumn) {
                        $searchQuery->orWhere("orders.{$invoiceNumberColumn}", $likeOperator, "%{$term}%");
                    }

                    if ($customerNameColumn) {
                        $searchQuery->orWhere("orders.{$customerNameColumn}", $likeOperator, "%{$term}%");
                    }

                    if ($customerEmailColumn) {
                        $searchQuery->orWhere("orders.{$customerEmailColumn}", $likeOperator, "%{$term}%");
                    }
                });
            }

            // Filtro por estado de la orden
            if ($filters['status'] !== '') {
                $query->where("orders.{$statusColumn}", $filters['status']);
            }

            // Filtro por estado de pago
            if ($filters['payment_status'] !== '' && $paymentStatusColumn) {
                $query->where("orders.{$paymentStatusColumn}", $filters['payment_status']);
            }

            // Filtro por fecha desde
            if ($filters['from_date'] !== '') {
                $column = $invoiceDateColumn ? "orders.{$invoiceDateColumn}" : 'orders.created_at';
                $query->where($column, '>=', $filters['from_date'] . ' 00:00:00');
            }

            // Filtro por fecha hasta
            if ($filters['to_date'] !== '') {
                $column = $invoiceDateColumn ? "orders.{$invoiceDateColumn}" : 'orders.created_at';
                $query->where($column, '<=', $filters['to_date'] . ' 23:59:59');
            }

            // Si se filtró por fuente, verifica que coincida con la conexión actual
            if ($filters['source'] !== '') {
                $expectedSource = $filters['source'];
                $currentSource = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';
                if ($expectedSource !== $currentSource) {
                    return collect();
                }
            }

            if ($invoiceDateColumn) {
                $query->orderByDesc("orders.{$invoiceDateColumn}");
            }

            $rows = $query
                ->orderByDesc('orders.created_at')
                ->limit($limit)
                ->get()
                ->map(function ($row) use ($connection) {
                    $normalized = $this->normalizeOrder((array) $row, $connection);
                    // Anota la fuente de la orden para trazabilidad en el listado
                    $normalized['order_source'] = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';
                    return $normalized;
                })
                ->values();

            return $this->hydrateRowsWithCustomerData($rows, $connection);
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Enriquece las filas de facturas con datos del cliente (nombre, email, teléfono)
     * consultando desde la tabla users local o desde auth-service como respaldo.
     *
     * @param Collection $rows Filas de facturas normalizadas.
     * @param string|null $connection Conexión activa.
     * @return Collection Filas enriquecidas.
     */
    private function hydrateRowsWithCustomerData(Collection $rows, ?string $connection): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        // Extrae los IDs de usuario únicos de las filas
        $userIds = $rows
            ->map(static fn (array $row): string => trim((string) ($row['user_id'] ?? '')))
            ->filter(static fn (string $userId): bool => $userId !== '')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return $rows;
        }

        // Paso 1: Carga usuarios desde la conexión principal
        $usersById = $this->loadUsersByIds($connection, $userIds->all());

        // Paso 2: Si no es legacy, busca los faltantes en la conexión legacy
        if ($connection !== self::LEGACY_CONNECTION) {
            $missingIds = $userIds
                ->reject(static fn (string $userId): bool => $usersById->has($userId))
                ->values();

            if ($missingIds->isNotEmpty()) {
                $legacyUsers = $this->loadUsersByIds(self::LEGACY_CONNECTION, $missingIds->all());
                $usersById = $usersById->merge($legacyUsers);
            }
        }

        // Paso 3: Los que aún falten, se consultan desde auth-service vía API HTTP
        $missingIdsAfterLegacy = $userIds
            ->reject(static fn (string $userId): bool => $usersById->has($userId))
            ->values();

        if ($missingIdsAfterLegacy->isNotEmpty()) {
            $authUsers = $this->loadUsersFromAuthService($missingIdsAfterLegacy->all());
            $usersById = $usersById->merge($authUsers);
        }

        return $rows
            ->map(static function (array $row) use ($usersById): array {
                $userId = trim((string) ($row['user_id'] ?? ''));
                if ($userId === '' || !$usersById->has($userId)) {
                    return $row;
                }

                $user = $usersById->get($userId);

                if (trim((string) ($row['customer_name'] ?? '')) === '') {
                    $row['customer_name'] = trim((string) ($user->name ?? ''));
                }

                if (trim((string) ($row['customer_email'] ?? '')) === '') {
                    $row['customer_email'] = trim((string) ($user->email ?? ''));
                }

                if (trim((string) ($row['customer_phone'] ?? '')) === '') {
                    $row['customer_phone'] = trim((string) ($user->phone ?? ''));
                }

                return $row;
            })
            ->values();
    }

    /**
     * Carga usuarios desde la tabla 'users' de una conexión específica.
     * Utilizado por hydrateRowsWithCustomerData para enriquecer filas de factura.
     *
     * @param string|null $connection Conexión de base de datos.
     * @param array $userIds Lista de IDs de usuario a consultar.
     * @return Collection Colección keyed por ID de usuario.
     */
    private function loadUsersByIds(?string $connection, array $userIds): Collection
    {
        if (empty($userIds) || !$this->hasTable('users', $connection)) {
            return collect();
        }

        try {
            return $this->query($connection)
                ->table('users')
                ->select('id', 'name', 'email', 'phone')
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy(static fn ($user): string => (string) $user->id);
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Carga perfiles de usuario desde el microservicio auth-service vía HTTP.
     * Usado como respaldo cuando el usuario no se encuentra en la BD local.
     * Fuente: services/auth-service (API interna /internal/users/profiles).
     *
     * @param array $userIds Lista de IDs de usuario.
     * @return Collection Colección de objetos con id, name, email, phone.
     */
    private function loadUsersFromAuthService(array $userIds): Collection
    {
        if ($userIds === [] || app()->environment('testing')) {
            return collect();
        }

        $endpoint = $this->resolveAuthProfilesEndpoint();
        if ($endpoint === null) {
            return collect();
        }

        try {
            // Construye la solicitud HTTP con timeout corto (4s) para no bloquear
            $request = Http::acceptJson()->timeout(4);
            // Usa token interno si está configurado para autenticación entre servicios
            $token = trim((string) config('services.auth.internal_token', ''));

            if ($token !== '') {
                $request = $request->withHeaders(['X-Internal-Token' => $token]);
            }

            // Consulta perfiles por lista de IDs separada por comas
            $response = $request->get($endpoint, [
                'ids' => implode(',', $userIds),
            ]);

            if (!$response->successful()) {
                return collect();
            }

            $profiles = $response->json('data');
            if (!is_array($profiles)) {
                return collect();
            }

            // Normaliza los perfiles a objetos estándar y filtra los que tengan ID vacío
            return collect($profiles)
                ->filter(static fn ($profile): bool => is_array($profile))
                ->map(static function (array $profile): ?object {
                    $id = trim((string) ($profile['id'] ?? ''));
                    if ($id === '') {
                        return null;
                    }

                    $email = trim((string) ($profile['email'] ?? ''));

                    return (object) [
                        'id' => $id,
                        'name' => trim((string) ($profile['name'] ?? '')),
                        // Valida email antes de asignarlo
                        'email' => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                        'phone' => trim((string) ($profile['phone'] ?? '')),
                    ];
                })
                ->filter() // Elimina entradas null
                ->keyBy(static fn ($user): string => (string) $user->id);
        } catch (Throwable $exception) {
            Log::warning('No se pudieron consultar perfiles en auth-service para facturas admin.', [
                'users_count' => count($userIds),
                'error' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Resuelve en qué conexión (distribuida o legacy) se encuentra la orden.
     * Si se especifica preferredConnection, prueba esa primero.
     *
     * @param int $orderId ID de la orden.
     * @param string|null $preferredConnection Conexión preferida.
     * @return array Arreglo con 'order' (objeto) y 'connection' (string|null).
     */
    private function resolveOrderSource(int $orderId, ?string $preferredConnection = null): array
    {
        // Define el orden de búsqueda: preferida primero, luego la otra
        $connections = $preferredConnection === self::LEGACY_CONNECTION
            ? [self::LEGACY_CONNECTION, null]
            : [null, self::LEGACY_CONNECTION];

        foreach ($connections as $connection) {
            $order = $this->findOrderByConnection($connection, $orderId);
            if ($order) {
                return ['order' => $order, 'connection' => $connection];
            }
        }

        return ['order' => null, 'connection' => null];
    }

    /**
     * Busca una orden por ID en una conexión específica.
     * Internamente verifica que la tabla 'orders' exista en esa conexión.
     *
     * @param string|null $connection Conexión de base de datos.
     * @param int $orderId ID de la orden.
     * @return object|null Objeto de la orden o null.
     */
    private function findOrderByConnection(?string $connection, int $orderId): ?object
    {
        if (!$this->hasTable('orders', $connection)) {
            return null;
        }

        try {
            return $this->query($connection)
                ->table('orders')
                ->where('id', $orderId)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Obtiene los items (productos) de una orden desde una conexión específica.
     *
     * @param string|null $connection Conexión de base de datos.
     * @param int $orderId ID de la orden.
     * @return Collection Colección de objetos item.
     */
    private function fetchOrderItemsByConnection(?string $connection, int $orderId): Collection
    {
        if (!$this->hasTable('order_items', $connection)) {
            return collect();
        }

        try {
            return $this->query($connection)
                ->table('order_items')
                ->where('order_id', $orderId)
                ->orderBy('id')
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Normaliza los campos de una fila de orden a un formato estándar.
     * Mapea nombres de columna variables (user_name, customer_name, billing_name, etc.)
     * a un esquema unificado para el resto del servicio.
     *
     * @param array $row Fila cruda desde la base de datos.
     * @param string|null $connection Conexión de origen (determina order_source).
     * @return array Arreglo normalizado con campos estandarizados.
     */
    private function normalizeOrder(array $row, ?string $connection): array
    {
        $status = (string) ($row['status'] ?? $row['order_status'] ?? 'pending');
        $paymentStatus = (string) ($row['payment_status'] ?? 'pending');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'order_number' => trim((string) ($row['order_number'] ?? '')),
            'invoice_number' => trim((string) ($row['invoice_number'] ?? '')),
            'user_id' => trim((string) ($row['user_id'] ?? '')),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => trim((string) ($row['payment_method'] ?? '')),
            'subtotal' => (float) ($row['subtotal'] ?? 0),
            'shipping_cost' => (float) ($row['shipping_cost'] ?? 0),
            'discount_amount' => (float) ($row['discount_amount'] ?? 0),
            'total' => (float) ($row['total'] ?? 0),
            'shipping_address' => trim((string) ($row['shipping_address'] ?? '')),
            'shipping_city' => trim((string) ($row['shipping_city'] ?? '')),
            'billing_address' => trim((string) ($row['billing_address'] ?? '')),
            'notes' => trim((string) ($row['notes'] ?? '')),
            'created_at' => $row['created_at'] ?? null,
            'invoice_date' => $row['invoice_date'] ?? null,
            'customer_name' => trim((string) ($row['user_name'] ?? $row['customer_name'] ?? $row['billing_name'] ?? '')),
            'customer_email' => trim((string) ($row['user_email'] ?? $row['customer_email'] ?? $row['billing_email'] ?? '')),
            'customer_phone' => trim((string) ($row['user_phone'] ?? $row['customer_phone'] ?? $row['billing_phone'] ?? $row['phone'] ?? '')),
            'order_source' => $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice',
        ];
    }

    /**
     * Resuelve el correo electrónico del cliente a partir de la orden.
     * Primero prueba customer_email y billing_email, luego consulta la tabla users
     * o auth-service como respaldo.
     *
     * @param array $order Orden normalizada.
     * @param string|null $connection Conexión activa.
     * @return string|null Email válido o null.
     */
    private function resolveCustomerEmail(array $order, ?string $connection): ?string
    {
        // Prioriza emails directos de la orden
        $candidates = [
            trim((string) ($order['customer_email'] ?? '')),
            trim((string) ($order['billing_email'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        // Si no hay email en la orden, busca el usuario asociado
        $user = $this->findUserById($connection, trim((string) ($order['user_id'] ?? '')));
        // Si no se encuentra en la conexión actual, prueba legacy
        if (!$user && $connection !== self::LEGACY_CONNECTION) {
            $user = $this->findUserById(self::LEGACY_CONNECTION, trim((string) ($order['user_id'] ?? '')));
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Resuelve el email del cliente desde el contexto de checkout.
     * Útil cuando el correo se recibe en la solicitud (primer pago, sin orden persistida aún).
     *
     * @param array $context Datos contextuales del checkout.
     * @return string|null Email válido o null.
     */
    private function resolveContextCustomerEmail(array $context): ?string
    {
        $email = trim((string) ($context['customer_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Resuelve el nombre del cliente para mostrar en correos y PDFs.
     * Prioriza customer_name de la orden, luego la tabla users, luego 'Cliente'.
     *
     * @param array $order Orden normalizada.
     * @param string|null $connection Conexión activa.
     * @return string Nombre del cliente.
     */
    private function resolveCustomerName(array $order, ?string $connection): string
    {
        $name = trim((string) ($order['customer_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        // Consulta el usuario asociado, con fallback a legacy
        $user = $this->findUserById($connection, trim((string) ($order['user_id'] ?? '')));
        if (!$user && $connection !== self::LEGACY_CONNECTION) {
            $user = $this->findUserById(self::LEGACY_CONNECTION, trim((string) ($order['user_id'] ?? '')));
        }

        $resolvedName = trim((string) ($user->name ?? ''));
        if ($resolvedName !== '') {
            return $resolvedName;
        }

        return 'Cliente';
    }

    /**
     * Busca un usuario por ID en la tabla local 'users' con fallback a auth-service.
     *
     * @param string|null $connection Conexión de base de datos.
     * @param string $userId ID del usuario.
     * @return object|null Objeto usuario o null.
     */
    private function findUserById(?string $connection, string $userId): ?object
    {
        $normalizedUserId = trim($userId);
        if ($normalizedUserId === '') {
            return null;
        }

        $user = null;
        try {
            if ($this->hasTable('users', $connection)) {
                $user = $this->query($connection)
                    ->table('users')
                    ->select('id', 'name', 'email', 'phone')
                    ->where('id', $normalizedUserId)
                    ->first();
            }
        } catch (Throwable) {
            $user = null;
        }

        if ($user) {
            return $user;
        }

        // Fallback: consulta auth-service vía HTTP
        return $this->findAuthUserById($normalizedUserId);
    }

    /**
     * Busca un usuario en auth-service usando caché en memoria ($authProfilesById).
     * Evita llamadas HTTP repetitivas para el mismo ID.
     *
     * @param string $userId ID del usuario.
     * @return object|null Objeto perfil o null.
     */
    private function findAuthUserById(string $userId): ?object
    {
        if ($userId === '' || app()->environment('testing')) {
            return null;
        }

        // Retorna desde caché si ya se consultó antes
        if (array_key_exists($userId, $this->authProfilesById)) {
            return $this->authProfilesById[$userId];
        }

        $profiles = $this->loadUsersFromAuthService([$userId]);
        $profile = $profiles->get($userId);

        // Almacena en caché incluso si es null para evitar reconsultas
        $this->authProfilesById[$userId] = $profile instanceof \stdClass ? $profile : null;

        return $this->authProfilesById[$userId];
    }

    /**
     * Resuelve la URL del endpoint de perfiles de auth-service.
     * Soporta URLs con o sin '/api' al final.
     * Fuente: services/auth-service (config/services.auth.base_url).
     *
     * @return string|null URL completa del endpoint o null si no está configurado.
     */
    private function resolveAuthProfilesEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.auth.base_url', 'http://auth-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        // Normaliza la URL para que siempre termine en /api/internal/users/profiles
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/internal/users/profiles';
        }

        return $baseUrl . '/api/internal/users/profiles';
    }

    /**
     * Verifica si una orden cumple las condiciones para generar factura:
     * estado completado/entregado y pago verificado/aprobado.
     *
     * @param array $order Orden normalizada.
     * @return bool True si está lista para facturar.
     */
    private function isReadyForInvoice(array $order): bool
    {
        $status = Str::lower(trim((string) ($order['status'] ?? 'pending')));
        $paymentStatus = Str::lower(trim((string) ($order['payment_status'] ?? 'pending')));

        return in_array($status, self::COMPLETED_ORDER_STATUSES, true)
            && in_array($paymentStatus, self::PAID_PAYMENT_STATUSES, true);
    }

    /**
     * Verifica si la orden ya tiene una factura asociada (por número o fecha).
     *
     * @param array $order Orden normalizada.
     * @return bool True si ya existe invoice_number o invoice_date.
     */
    private function orderHasInvoice(array $order): bool
    {
        $invoiceNumber = trim((string) ($order['invoice_number'] ?? ''));
        $invoiceDate = trim((string) ($order['invoice_date'] ?? ''));

        return $invoiceNumber !== '' || $invoiceDate !== '';
    }

    /**
     * Resuelve o genera el número de factura para una orden.
     * Si ya existe invoice_number, lo retorna; si no, construye FAC-{order_number}.
     *
     * @param array $order Orden normalizada.
     * @return string Número de factura.
     */
    private function resolveInvoiceNumber(array $order): string
    {
        $currentInvoice = trim((string) ($order['invoice_number'] ?? ''));
        if ($currentInvoice !== '') {
            return $this->fitInvoiceNumberToColumn($currentInvoice, (int) ($order['id'] ?? 0));
        }

        // Genera número basado en order_number o ID
        $orderLabel = trim((string) ($order['order_number'] ?? ''));
        if ($orderLabel === '') {
            $orderLabel = (string) ($order['id'] ?? '0');
        }

        $suffix = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', $orderLabel) ?: (string) ($order['id'] ?? '0'));
        $candidate = 'FAC-' . $suffix;

        return $this->fitInvoiceNumberToColumn($candidate, (int) ($order['id'] ?? 0));
    }

    /**
     * Ajusta el número de factura al límite de la columna varchar(20).
     * Si excede, recorta la base y agrega un hash corto para mantener trazabilidad.
     *
     * @param string $invoiceNumber Número de factura propuesto.
     * @param int $orderId ID de la orden para el hash.
     * @return string Número ajustado a <= 20 caracteres.
     */
    private function fitInvoiceNumberToColumn(string $invoiceNumber, int $orderId): string
    {
        $normalized = strtoupper(trim($invoiceNumber));
        if ($normalized === '') {
            $normalized = 'FAC-' . (string) ($orderId > 0 ? $orderId : time());
        }

        if (strlen($normalized) <= 20) {
            return $normalized;
        }

        // Ajusta al límite varchar(20) sin perder trazabilidad básica de la referencia.
        $safeBase = substr(preg_replace('/[^A-Z0-9-]/', '', $normalized), 0, 13);
        $hash = strtoupper(substr(sha1($normalized . '|' . (string) $orderId), 0, 6));

        return $safeBase . '-' . $hash;
    }

    /**
     * Persiste los metadatos de facturación en la orden (invoice_number, invoice_date, updated_at).
     * Resuelve dinámicamente los nombres de columna según el esquema real.
     *
     * @param string|null $connection Conexión de base de datos.
     * @param int $orderId ID de la orden.
     * @param string $invoiceNumber Número de factura generado.
     */
    private function persistInvoiceMetadata(?string $connection, int $orderId, string $invoiceNumber): void
    {
        if ($orderId <= 0 || !$this->hasTable('orders', $connection)) {
            return;
        }

        $updates = [];

        // Actualiza updated_at si la columna existe
        $updatedAtColumn = $this->firstExistingColumn('orders', ['updated_at'], $connection);
        if ($updatedAtColumn) {
            $updates[$updatedAtColumn] = now();
        }

        // Guarda el número de factura
        $invoiceNumberColumn = $this->firstExistingColumn('orders', ['invoice_number'], $connection);
        if ($invoiceNumberColumn) {
            $updates[$invoiceNumberColumn] = $invoiceNumber;
        }

        // Guarda la fecha de emisión de factura
        $invoiceDateColumn = $this->firstExistingColumn('orders', ['invoice_date'], $connection);
        if ($invoiceDateColumn) {
            $updates[$invoiceDateColumn] = now();
        }

        if ($updates === []) {
            return;
        }

        try {
            $this->query($connection)
                ->table('orders')
                ->where('id', $orderId)
                ->update($updates);
        } catch (Throwable $exception) {
            Log::warning('No se pudieron persistir metadatos de factura.', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Envía una notificación push/email al usuario a través del microservicio notification-service.
     * El mensaje varía si es un reenvío (resent=true) o generación inicial.
     * Fuente: services/notification-service (API /api/notifications).
     *
     * @param array $order Orden normalizada.
     * @param string $invoiceNumber Número de factura.
     * @param bool $resent Indica si es un reenvío.
     * @return bool True si la notificación se registró exitosamente.
     */
    private function sendInvoiceNotification(array $order, string $invoiceNumber, bool $resent): bool
    {
        $userId = trim((string) ($order['user_id'] ?? ''));
        if ($userId === '') {
            return false;
        }

        $endpoint = $this->resolveNotificationEndpoint();
        if ($endpoint === null) {
            return false;
        }

        $orderId = (int) ($order['id'] ?? 0);
        $orderLabel = trim((string) ($order['order_number'] ?? ''));
        if ($orderLabel === '') {
            $orderLabel = $orderId > 0 ? '#' . $orderId : 'N/A';
        }

        // Mensaje contextual según sea primera vez o reenvío
        $title = $resent ? 'Factura reenviada' : 'Tu factura está disponible';
        $message = $resent
            ? "Reenviamos la factura {$invoiceNumber} de tu pedido {$orderLabel}."
            : "Ya puedes consultar la factura {$invoiceNumber} de tu pedido {$orderLabel}.";

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->post($endpoint, [
                    'user_id' => $userId,
                    'type_id' => (int) config('services.notifications.order_type_id', self::DEFAULT_NOTIFICATION_TYPE_ID),
                    'title' => $title,
                    'message' => $message,
                    'related_entity_type' => 'order',
                    'related_entity_id' => $orderId > 0 ? $orderId : null,
                ]);

            if (!$response->successful()) {
                Log::warning('No se pudo registrar notificación de factura en notification-service.', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'invoice_number' => $invoiceNumber,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (Throwable $exception) {
            Log::warning('Fallo enviando notificación de factura.', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'invoice_number' => $invoiceNumber,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Resuelve la URL del endpoint de notificaciones de notification-service.
     * Soporta URLs con o sin '/api' al final.
     * Fuente: services/notification-service (config/services.notifications.base_url).
     *
     * @return string|null URL completa del endpoint o null si no está configurado.
     */
    private function resolveNotificationEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.notifications.base_url', 'http://notification-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        // Normaliza la URL para que termine en /api/notifications
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/notifications';
        }

        return $baseUrl . '/api/notifications';
    }

    /**
     * Construye el HTML del correo de confirmación de checkout.
     * Incluye datos del cliente, resumen de productos, datos de pago reportados
     * y dirección de envío. Utiliza e() para escapar output seguro.
     *
     * @param array $order Orden normalizada.
     * @param array $items Items de la orden.
     * @param string $customerName Nombre del cliente.
     * @param array $context Datos contextuales (banco, referencia, comprobante).
     * @return string HTML completo del correo.
     */
    private function buildCheckoutConfirmationHtml(array $order, array $items, string $customerName, array $context): string
    {
        $safeName = e($customerName);
        $safeOrder = e($order['order_number'] ?: ('#' . (string) $order['id']));
        $safePaymentReference = e(trim((string) ($context['payment_reference'] ?? 'Sin referencia')));
        $safeBankName = e(trim((string) ($context['bank_name'] ?? 'Sin banco seleccionado')));
        $safeProofName = e(trim((string) ($context['payment_proof_name'] ?? 'Sin archivo reportado')));
        $safeOrderDate = e($this->formatBogotaDate($order['created_at'] ?? null));
        $safeStoreUrl = e($this->resolveStoreUrl() . '/mi-cuenta/pedidos');
        $safeShippingAddress = e($order['shipping_address'] !== '' ? $order['shipping_address'] : 'Dirección no disponible');
        $safeShippingCity = e($order['shipping_city'] !== '' ? $order['shipping_city'] : 'Ciudad no disponible');

        $summaryRows = $this->buildSummaryRows($order);
        $itemRows = $this->buildEmailItemRows($items);

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de pedido</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px 0; background: #f5f7fb; color: #1f2937; }
        .container { max-width: 720px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f7abf; color: #ffffff; padding: 22px 24px; }
        .header h1 { margin: 0 0 6px; font-size: 24px; }
        .content { padding: 24px; }
        .chip { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #e7f3fb; color: #0f7abf; font-weight: 700; margin: 12px 0; }
        .section-title { font-size: 17px; font-weight: 700; color: #0f7abf; margin: 24px 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fafc; color: #0f172a; padding: 10px; border-bottom: 1px solid #e5edf6; }
        td { padding: 10px; border-bottom: 1px solid #eef2f7; font-size: 14px; }
        .summary { background: #f8fafc; border: 1px solid #e5edf6; border-radius: 10px; padding: 14px; }
        .summary-row { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 6px; font-size: 14px; }
        .summary-row strong { color: #0f172a; }
        .summary-total { margin-top: 8px; padding-top: 8px; border-top: 1px solid #dbe5f0; font-size: 16px; color: #0f7abf; font-weight: 700; }
        .payment-box, .shipping-box { border: 1px solid #dbe5f0; border-radius: 10px; padding: 14px; background: #ffffff; }
        .payment-box p, .shipping-box p { margin: 0 0 8px; font-size: 14px; }
        .cta { margin-top: 18px; }
        .cta a { display: inline-block; background: #0f7abf; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; }
        .footer { padding: 14px 24px; border-top: 1px solid #e5edf6; font-size: 12px; color: #64748b; background: #f8fbff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Pedido confirmado!</h1>
            <p style="margin:0; opacity:.92;">Recibimos tu compra y la estamos preparando.</p>
        </div>
        <div class="content">
            <p>Hola <strong>' . $safeName . '</strong>,</p>
            <p>Tu pedido ya quedó registrado en Angelow.</p>
            <span class="chip">Pedido ' . $safeOrder . ' · ' . $safeOrderDate . '</span>

            <div class="section-title">Resumen del pedido</div>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align:center;">Cant.</th>
                        <th style="text-align:right;">Precio</th>
                        <th style="text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>' . $itemRows . '</tbody>
            </table>

            <div class="section-title">Totales</div>
            <div class="summary">' . $summaryRows . '</div>

            <div class="section-title">Datos de pago reportados</div>
            <div class="payment-box">
                <p><strong>Banco:</strong> ' . $safeBankName . '</p>
                <p><strong>Referencia:</strong> ' . $safePaymentReference . '</p>
                <p><strong>Comprobante:</strong> ' . $safeProofName . '</p>
                <p><strong>Estado inicial:</strong> Pendiente de verificación</p>
            </div>

            <div class="section-title">Dirección de entrega</div>
            <div class="shipping-box">
                <p><strong>Dirección:</strong> ' . $safeShippingAddress . '</p>
                <p><strong>Ciudad/Zona:</strong> ' . $safeShippingCity . '</p>
            </div>

            <div class="cta">
                <a href="' . $safeStoreUrl . '">Ver mis pedidos</a>
            </div>
        </div>
        <div class="footer">
            Este correo fue generado automáticamente por Angelow. Te avisaremos cuando el pago sea verificado.
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Construye el HTML del correo de notificación de factura electrónica.
     * Se envía como cuerpo del mensaje que lleva el PDF adjunto.
     *
     * @param array $order Orden normalizada.
     * @param string $customerName Nombre del cliente.
     * @param string $invoiceNumber Número de factura.
     * @return string HTML del correo.
     */
    private function buildInvoiceEmailHtml(array $order, string $customerName, string $invoiceNumber): string
    {
        $safeName = e($customerName);
        $safeInvoiceNumber = e($invoiceNumber);
        $safeOrderLabel = e($order['order_number'] !== '' ? $order['order_number'] : ('#' . (string) $order['id']));
        $safeIssueDate = e($this->formatBogotaDate(now()));
        $safeStoreUrl = e($this->resolveStoreUrl() . '/mi-cuenta/pedidos');

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Angelow</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px 0; background: #f5f7fb; color: #1f2937; }
        .container { max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f7abf; color: #ffffff; padding: 22px 24px; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 24px; }
        .chip { display: inline-block; margin: 10px 0 16px; padding: 6px 12px; border-radius: 999px; background: #e7f3fb; color: #0f7abf; font-weight: 700; }
        .cta a { display: inline-block; background: #0f7abf; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; }
        .footer { padding: 14px 24px; border-top: 1px solid #e5edf6; font-size: 12px; color: #64748b; background: #f8fbff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tu factura electrónica ya está lista</h1>
        </div>
        <div class="content">
            <p>Hola <strong>' . $safeName . '</strong>,</p>
            <p>Adjuntamos la factura de tu compra en Angelow.</p>
            <span class="chip">Factura ' . $safeInvoiceNumber . ' · Pedido ' . $safeOrderLabel . '</span>
            <p><strong>Fecha de emisión:</strong> ' . $safeIssueDate . ' (hora Colombia)</p>
            <p>Conserva este correo y el PDF adjunto para tus registros.</p>
            <p class="cta"><a href="' . $safeStoreUrl . '">Ir a mis pedidos</a></p>
        </div>
        <div class="footer">
            Si tienes dudas, responde por nuestros canales de soporte desde tu cuenta Angelow.
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Construye las filas HTML de la tabla de productos para el correo de confirmación.
     *
     * @param array $items Items de la orden.
     * @return string HTML de filas <tr>.
     */
    private function buildEmailItemRows(array $items): string
    {
        if (empty($items)) {
            return '<tr><td colspan="4" style="text-align:center; color:#64748b;">Sin productos disponibles en esta orden.</td></tr>';
        }

        $rows = '';

        foreach ($items as $item) {
            $productName = e((string) ($item['product_name'] ?? 'Producto'));
            $variant = trim((string) ($item['variant_name'] ?? ''));
            $variantHtml = $variant !== '' ? '<br><small style="color:#64748b;">' . e($variant) . '</small>' : '';
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = '$ ' . number_format((float) ($item['price'] ?? 0), 0, ',', '.');
            $total = '$ ' . number_format((float) ($item['total'] ?? 0), 0, ',', '.');

            $rows .= '<tr>'
                . '<td><strong>' . $productName . '</strong>' . $variantHtml . '</td>'
                . '<td style="text-align:center;">' . $quantity . '</td>'
                . '<td style="text-align:right;">' . $price . '</td>'
                . '<td style="text-align:right;"><strong>' . $total . '</strong></td>'
                . '</tr>';
        }

        return $rows;
    }

    /**
     * Construye las filas HTML del resumen de totales para el correo de confirmación.
     *
     * @param array $order Orden normalizada con subtotal, shipping_cost, discount_amount, total.
     * @return string HTML de las filas de resumen.
     */
    private function buildSummaryRows(array $order): string
    {
        $subtotal = (float) ($order['subtotal'] ?? 0);
        $shipping = (float) ($order['shipping_cost'] ?? 0);
        $discount = (float) ($order['discount_amount'] ?? 0);
        $total = (float) ($order['total'] ?? ($subtotal + $shipping - $discount));

        $rows = '<div class="summary-row"><span>Subtotal</span><strong>$ ' . number_format($subtotal, 0, ',', '.') . '</strong></div>';

        // Solo muestra descuento si es mayor a cero
        if ($discount > 0) {
            $rows .= '<div class="summary-row"><span>Descuento</span><strong>-$ ' . number_format($discount, 0, ',', '.') . '</strong></div>';
        }

        $rows .= '<div class="summary-row"><span>Envío</span><strong>$ ' . number_format($shipping, 0, ',', '.') . '</strong></div>';
        $rows .= '<div class="summary-row summary-total"><span>Total</span><strong>$ ' . number_format($total, 0, ',', '.') . '</strong></div>';

        return $rows;
    }

    /**
     * Genera el contenido binario del PDF de factura usando Dompdf.
     * Construye el HTML completo con datos del cliente, orden, productos y totales,
     * luego renderiza y retorna el PDF en memoria.
     *
     * @param array $order Orden normalizada.
     * @param array $items Items de la orden.
     * @param string $invoiceNumber Número de factura.
     * @return string Contenido binario del PDF.
     */
    private function generateInvoicePdfContent(array $order, array $items, string $invoiceNumber): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);

        $safeInvoiceNumber = e($invoiceNumber);
        $safeOrderLabel = e($order['order_number'] !== '' ? $order['order_number'] : ('#' . (string) $order['id']));
        $safeIssueDate = e($this->formatBogotaDate(now()));
        $safeOrderDate = e($this->formatBogotaDate($order['created_at'] ?? null));
        $safeCustomerName = e($this->resolveCustomerName($order, $order['order_source'] === 'legacy' ? self::LEGACY_CONNECTION : null));
        $safeCustomerEmail = e($this->resolveCustomerEmail($order, $order['order_source'] === 'legacy' ? self::LEGACY_CONNECTION : null) ?? 'No disponible');
        $safeCustomerPhone = e(trim((string) ($order['customer_phone'] ?? '')) ?: 'No disponible');
        $safeShippingAddress = e($order['shipping_address'] !== '' ? $order['shipping_address'] : 'Dirección no disponible');
        $safeShippingCity = e($order['shipping_city'] !== '' ? $order['shipping_city'] : 'Ciudad no disponible');
        $safePaymentMethod = e($this->translatePaymentMethod($order['payment_method'] ?? null));
        $safePaymentStatus = e($this->translateOperationalValue($order['payment_status'] ?? null, 'payment_status'));
        $safeOrderStatus = e($this->translateOperationalValue($order['status'] ?? null, 'status'));

        $itemRows = $this->buildPdfItemRows($items);
        $summaryRows = $this->buildPdfSummaryRows($order);

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 0; }
        .container { padding: 20px; }
        .header { border-bottom: 2px solid #0f7abf; padding-bottom: 12px; margin-bottom: 18px; }
        .header h1 { margin: 0; color: #0f7abf; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #4b5563; }
        .meta { margin-top: 10px; font-size: 11px; color: #334155; }
        .section-title { margin: 18px 0 8px; font-size: 14px; color: #0f7abf; font-weight: bold; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
        .line { margin-bottom: 5px; }
        .label { font-weight: bold; color: #0f172a; display: inline-block; min-width: 130px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f1f5f9; color: #0f172a; text-align: left; padding: 8px; border: 1px solid #e2e8f0; }
        td { padding: 8px; border: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals { margin-top: 10px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
        .totals-row { display: table; width: 100%; margin-bottom: 4px; }
        .totals-row span { display: table-cell; }
        .totals-row .value { text-align: right; font-weight: bold; }
        .totals-row.total { border-top: 1px solid #cbd5e1; padding-top: 6px; margin-top: 6px; font-size: 15px; color: #0f7abf; }
        .footer { margin-top: 16px; font-size: 10px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Factura electrónica Angelow</h1>
            <p>Documento generado automáticamente por finalización de pedido.</p>
            <div class="meta">
                <strong>Factura:</strong> ' . $safeInvoiceNumber . ' ·
                <strong>Pedido:</strong> ' . $safeOrderLabel . ' ·
                <strong>Emisión:</strong> ' . $safeIssueDate . '
            </div>
        </div>

        <div class="section-title">Datos del cliente</div>
        <div class="card">
            <div class="line"><span class="label">Nombre:</span> ' . $safeCustomerName . '</div>
            <div class="line"><span class="label">Correo:</span> ' . $safeCustomerEmail . '</div>
            <div class="line"><span class="label">Teléfono:</span> ' . $safeCustomerPhone . '</div>
        </div>

        <div class="section-title">Datos de la orden</div>
        <div class="card">
            <div class="line"><span class="label">Fecha de orden:</span> ' . $safeOrderDate . '</div>
            <div class="line"><span class="label">Estado orden:</span> ' . $safeOrderStatus . '</div>
            <div class="line"><span class="label">Estado pago:</span> ' . $safePaymentStatus . '</div>
            <div class="line"><span class="label">Método de pago:</span> ' . $safePaymentMethod . '</div>
            <div class="line"><span class="label">Dirección:</span> ' . $safeShippingAddress . '</div>
            <div class="line"><span class="label">Ciudad/Zona:</span> ' . $safeShippingCity . '</div>
        </div>

        <div class="section-title">Productos</div>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="center">Cant.</th>
                    <th class="right">Precio</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>' . $itemRows . '</tbody>
        </table>

        <div class="totals">' . $summaryRows . '</div>

        <div class="footer">
            Factura generada el ' . $safeIssueDate . ' (hora Colombia) · Angelow
        </div>
    </div>
</body>
</html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Construye las filas HTML de la tabla de productos para el PDF de factura.
     *
     * @param array $items Items de la orden.
     * @return string HTML de filas <tr>.
     */
    private function buildPdfItemRows(array $items): string
    {
        if (empty($items)) {
            return '<tr><td colspan="4" class="center">Sin productos registrados.</td></tr>';
        }

        $rows = '';

        foreach ($items as $item) {
            $productName = e((string) ($item['product_name'] ?? 'Producto'));
            $variant = trim((string) ($item['variant_name'] ?? ''));
            $variantHtml = $variant !== '' ? '<br><small style="color:#64748b;">' . e($variant) . '</small>' : '';

            $rows .= '<tr>'
                . '<td>' . $productName . $variantHtml . '</td>'
                . '<td class="center">' . (int) ($item['quantity'] ?? 0) . '</td>'
                . '<td class="right">$ ' . number_format((float) ($item['price'] ?? 0), 0, ',', '.') . '</td>'
                . '<td class="right">$ ' . number_format((float) ($item['total'] ?? 0), 0, ',', '.') . '</td>'
                . '</tr>';
        }

        return $rows;
    }

    /**
     * Construye las filas HTML del resumen de totales para el PDF de factura.
     *
     * @param array $order Orden normalizada.
     * @return string HTML de las filas de totales.
     */
    private function buildPdfSummaryRows(array $order): string
    {
        $subtotal = (float) ($order['subtotal'] ?? 0);
        $shipping = (float) ($order['shipping_cost'] ?? 0);
        $discount = (float) ($order['discount_amount'] ?? 0);
        $total = (float) ($order['total'] ?? ($subtotal + $shipping - $discount));

        $rows = '<div class="totals-row"><span>Subtotal</span><span class="value">$ ' . number_format($subtotal, 0, ',', '.') . '</span></div>';

        if ($discount > 0) {
            $rows .= '<div class="totals-row"><span>Descuento</span><span class="value">-$ ' . number_format($discount, 0, ',', '.') . '</span></div>';
        }

        $rows .= '<div class="totals-row"><span>Envío</span><span class="value">$ ' . number_format($shipping, 0, ',', '.') . '</span></div>';
        $rows .= '<div class="totals-row total"><span>Total factura</span><span class="value">$ ' . number_format($total, 0, ',', '.') . '</span></div>';

        return $rows;
    }

    /**
     * Traduce el método de pago interno a una cadena legible en español.
     * Usa un mapa fijo para los valores conocidos y genera título para los desconocidos.
     *
     * @param string|null $value Valor interno del método de pago.
     * @return string Traducción al español.
     */
    private function translatePaymentMethod(?string $value): string
    {
        $map = [
            'transfer' => 'Transferencia',
            'bank_transfer' => 'Transferencia bancaria',
            'transferencia' => 'Transferencia',
            'transferencia_bancaria' => 'Transferencia bancaria',
            'cash' => 'Efectivo',
            'credit_card' => 'Tarjeta de crédito',
            'debit_card' => 'Tarjeta de débito',
            'pse' => 'PSE',
            'nequi' => 'Nequi',
        ];

        $normalized = Str::lower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return 'Transferencia';
        }

        if (array_key_exists($normalized, $map)) {
            return $map[$normalized];
        }

        return Str::title(str_replace(['_', '-'], ' ', $normalized));
    }

    /**
     * Traduce valores operativos (estado de orden o estado de pago) a español legible.
     * Usa mapas separados según el contexto ('status' o 'payment_status').
     *
     * @param string|null $value Valor interno a traducir.
     * @param string $context Contexto: 'status' para orden, 'payment_status' para pago.
     * @return string Traducción al español.
     */
    private function translateOperationalValue(?string $value, string $context): string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return 'Pendiente';
        }

        $statusMap = [
            'pending' => 'Pendiente',
            'processing' => 'En proceso',
            'in_process' => 'En proceso',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            'canceled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'failed' => 'Fallido',
        ];

        $paymentMap = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'verified' => 'Verificado',
            'approved' => 'Aprobado',
            'failed' => 'Fallido',
            'rejected' => 'Rechazado',
            'cancelled' => 'Cancelado',
            'canceled' => 'Cancelado',
            'refunded' => 'Reembolsado',
        ];

        // Selecciona el mapa según el contexto solicitado
        $map = $context === 'payment_status' ? $paymentMap : $statusMap;

        if (array_key_exists($normalized, $map)) {
            return $map[$normalized];
        }

        return Str::title(str_replace(['_', '-'], ' ', $normalized));
    }

    /**
     * Formatea una fecha en zona horaria Bogotá para mostrar en correos y PDFs.
     *
     * @param mixed $value Fecha en cualquier formato soportado por Carbon.
     * @return string Fecha formateada (d/m/Y H:i) o 'No disponible'.
     */
    private function formatBogotaDate(mixed $value): string
    {
        $date = $this->parseDateAsBogota($value);
        if (!$date) {
            return 'No disponible';
        }

        return $date->format('d/m/Y H:i');
    }

    /**
     * Convierte una fecha a la zona horaria America/Bogota.
     * Soporta Carbon, strings con offset, strings UTC y valores vacíos.
     *
     * @param mixed $value Fecha en diversos formatos.
     * @return Carbon|null Instancia Carbon en zona Bogotá o null.
     */
    private function parseDateAsBogota(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->timezone('America/Bogota');
        }

        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            // Detecta si la fecha incluye offset UTC (ej: +00:00, Z) para parsear correctamente
            $hasOffset = preg_match('/([+-]\d{2}:?\d{2}|Z)$/i', $raw) === 1;
            $date = $hasOffset ? Carbon::parse($raw) : Carbon::parse($raw, 'UTC');
            return $date->timezone('America/Bogota');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resuelve la URL base del frontend store desde la configuración.
     * Fuente: services/frontend (config/services.frontend.store_url).
     *
     * @return string URL base del store.
     */
    private function resolveStoreUrl(): string
    {
        $url = trim((string) config('services.frontend.store_url', 'http://localhost:5173'));
        if ($url === '') {
            return 'http://localhost:5173';
        }

        return rtrim($url, '/');
    }

    /**
     * Normaliza los filtros de listado de facturas, limpiando y estableciendo valores por defecto.
     *
     * @param array $filters Filtros crudos desde la solicitud.
     * @return array Filtros normalizados con valores string vacío por defecto.
     */
    private function normalizeInvoiceFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => Str::lower(trim((string) ($filters['status'] ?? ''))),
            'payment_status' => Str::lower(trim((string) ($filters['payment_status'] ?? ''))),
            'from_date' => trim((string) ($filters['from_date'] ?? '')),
            'to_date' => trim((string) ($filters['to_date'] ?? '')),
            'source' => Str::lower(trim((string) ($filters['source'] ?? ''))),
        ];
    }

    /**
     * Sanitiza una cadena para usarla como segmento de nombre de archivo.
     * Reemplaza caracteres no alfanuméricos con guiones bajos.
     *
     * @param string $value Cadena a sanitizar.
     * @return string Cadena segura para nombre de archivo.
     */
    private function sanitizeFileSegment(string $value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9_-]/', '_', $value) ?: 'factura';
        return trim($cleaned, '_');
    }

    /**
     * Determina el operador LIKE correcto según el driver de base de datos.
     * PostgreSQL requiere ILIKE para búsqueda case-insensitive.
     *
     * @param string|null $connection Conexión de base de datos.
     * @return string 'ILIKE' para PostgreSQL, 'LIKE' para los demás.
     */
    private function likeOperator(?string $connection = null): string
    {
        try {
            $driver = ($connection ? DB::connection($connection) : DB::connection())->getDriverName();
            return $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
        } catch (Throwable) {
            return 'LIKE';
        }
    }

    /**
     * Retorna una instancia de conexión de base de datos.
     * Si $connection es null, usa la conexión por defecto.
     *
     * @param string|null $connection Nombre de conexión o null.
     * @return \Illuminate\Database\Connection
     */
    private function query(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }

    /**
     * Verifica si una tabla existe en una conexión específica.
     * Atrapa excepciones de conexión para evitar errores en cascada.
     *
     * @param string $table Nombre de la tabla.
     * @param string|null $connection Conexión de base de datos.
     * @return bool True si la tabla existe.
     */
    private function hasTable(string $table, ?string $connection): bool
    {
        try {
            return Schema::connection($this->resolveConnectionName($connection))->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Encuentra la primera columna existente de una lista de candidatos en una tabla.
     * Útil para soportar esquemas con nombres de columna variables entre conexiones.
     *
     * @param string $table Nombre de la tabla.
     * @param array $candidates Lista de nombres de columna a probar.
     * @param string|null $connection Conexión de base de datos.
     * @return string|null Nombre de la primera columna encontrada o null.
     */
    private function firstExistingColumn(string $table, array $candidates, ?string $connection): ?string
    {
        $connectionName = $this->resolveConnectionName($connection);

        try {
            foreach ($candidates as $column) {
                if (Schema::connection($connectionName)->hasColumn($table, $column)) {
                    return $column;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Resuelve el nombre de conexión, usando la configuración por defecto si es null.
     *
     * @param string|null $connection Nombre de conexión.
     * @return string Nombre de conexión resuelto.
     */
    private function resolveConnectionName(?string $connection): string
    {
        return $connection ?: config('database.default');
    }
}
