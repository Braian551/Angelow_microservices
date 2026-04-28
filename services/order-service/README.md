# Order Service

Microservicio Laravel encargado de la gestion de ordenes, estados de pago, historial y reservas de inventario en tiempo real.

## Responsabilidad

- Crear y consultar ordenes.
- Reservar stock temporal al crear una orden.
- Confirmar o liberar reserva segun validacion administrativa de pago.
- Expirar reservas con cola `orders` + scheduler.
- Mantener historial auditable de cambios de estado y pago.

## Endpoints

- `GET /api/health`
- `GET /api/orders`
- `POST /api/orders`
- `GET /api/orders/{id}`
- `PATCH /api/orders/{id}`
- `PATCH /api/orders/{id}/status`
- `PATCH /api/orders/{id}/payment-status`
- `PATCH /api/orders/{id}/cancel`
- `PATCH /api/orders/{id}/deactivate`
- `POST /api/orders/{id}/send-confirmation`

## Inventario y reservas (Redis + PostgreSQL)

- Claves Redis:
  - `stock:{size_variant_id}`
  - `reserved:{size_variant_id}`
  - `reservation:{order_id}`
  - `lock:stock:{size_variant_id}`
- Tabla persistente:
  - `stock_reservations` (reserved, confirmed, expired, cancelled)
- Eventos realtime por pub/sub Redis:
  - canal base `ws:orders:stock`
  - canal por orden `ws:orders:stock:order:{order_id}`

## Procesos de cola

- `ExpireStockReservationJob`: libera reservas vencidas y marca orden como `expired`.
- `ReconcileStockReservationsJob`: reconcilia expiraciones y contadores `reserved:*`.
- `order-worker`: consume cola `orders`.
- `order-scheduler`: ejecuta `schedule:work` para reconciliacion periodica.