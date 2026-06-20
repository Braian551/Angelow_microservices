# Reservas de stock en e-commerce

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Hallazgos](#hallazgos)
- [Decisión para Angelow](#decisión-para-angelow)
- [Fuentes](#fuentes)
<!-- indice:auto:end -->

## Objetivo

Documentar cómo plataformas de e-commerce tratan reservas temporales de inventario para evitar sobreventa, stock bloqueado y estados de pedido ambiguos.

## Hallazgos

- WooCommerce usa una configuración de retención de stock para pedidos impagos: al cumplirse el límite, el pedido pendiente se cancela y el stock retenido vuelve a disponibilidad.
- Adobe Commerce maneja reservas como saldos/compensaciones de inventario: la reserva se mantiene hasta que el pedido se factura/envía, se cancela o recibe un movimiento compensatorio.
- Shopify revisa inventario durante checkout y maneja disponibilidad/compromisos de orden, pero no conviene depender de un estado visual “vencido” para el pedido; las reservas o apartados deben cerrar con liberación/cancelación clara.
- Commerce Layer crea reservas de stock asociadas a órdenes en estado pendiente y las elimina o descuenta cuando la orden se aprueba, manteniendo separada la reserva del estado final del pedido.

## Decisión para Angelow

El vencimiento de una reserva es una causa operativa, no un estado final de pedido. Cuando vence `ORDER_STOCK_RESERVATION_TTL`, `order-service` debe liberar la reserva, dejar la orden como `cancelled`, registrar historial con motivo `reservation_ttl_expired`, publicar websocket accionable y notificar al cliente que puede crear un nuevo pedido si los productos siguen disponibles.

## Fuentes

- WooCommerce, configuración de inventario: `Hold Stock (minutes)` cancela pedidos pendientes impagos y libera stock al cumplirse el límite. <https://woocommerce.com/document/configuring-woocommerce-settings/products>
- WooCommerce, estados de órdenes: el flujo base parte de `Pending payment` y deriva a estados operativos como `Failed`, `Cancelled`, `Processing` o `Completed`. <https://woocommerce.com/document/managing-orders/order-statuses>
- Adobe Commerce, Source algorithms and reservations: las reservas retienen cantidades hasta que la orden se factura/envía, se cancela o se compensa. <https://experienceleague.adobe.com/en/docs/commerce-admin/inventory/basics/selection-reservations>
- Commerce Layer, Stock reservations API: las reservas bloquean stock asociado a líneas de orden pendiente y se eliminan o descuentan al aprobar. <https://docs.commercelayer.io/core/api-reference/stock_reservations>
