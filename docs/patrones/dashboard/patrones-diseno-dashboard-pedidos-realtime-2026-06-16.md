# Patrones de Diseño: Pedidos en Tiempo Real

<!-- indice:auto:start -->
## Índice rápido

- [Contexto](#contexto)
- [Patrón 1: Observer](#patrón-1-observer)
- [Patrón 2: Adapter](#patrón-2-adapter)
- [Resultado esperado](#resultado-esperado)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Contexto

La vista `Mis pedidos` y el detalle de pedido necesitaban reflejar cambios de estado sin recargar la página. El backend ya publicaba eventos de stock por Redis y el `realtime-gateway` los exponía por websocket, así que el ajuste reutiliza ese canal operativo para publicar cambios de estado de órdenes y pagos.

## Patrón 1: Observer

- Referencia: https://refactoring.guru/es/design-patterns/observer
- Problema que resuelve: las pantallas de cuenta observan eventos del websocket y reaccionan cuando cambia el pedido abierto o listado.
- Archivos aplicados:
  - `frontend/src/composables/useOrderRealtime.js`
  - `frontend/src/modules/account/pages/OrdersPage.vue`
  - `frontend/src/modules/account/pages/OrderDetailPage.vue`
  - `services/order-service/app/Http/Controllers/OrderController.php`
- Implementación clave:
  - `OrderController` publica eventos `order.status.updated` y `order.payment_status.updated`.
  - Las vistas se suscriben al evento y actualizan el estado visible al instante.
  - Después del cambio inmediato, las vistas hacen una recarga silenciosa para sincronizar historial y campos derivados.

## Patrón 2: Adapter

- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: el gateway comparte canal con eventos de stock y pedidos, por lo que el frontend necesita una capa que convierta el payload genérico en un contrato usable por las vistas de cuenta.
- Archivo aplicado:
  - `frontend/src/composables/useOrderRealtime.js`
- Implementación clave:
  - El composable filtra eventos de pedidos.
  - Normaliza `order_id`, `field`, `new_value`, `status` y `payment_status`.
  - Oculta detalles del canal Redis/websocket a los componentes visuales.

## Resultado esperado

- En `/mi-cuenta/pedidos`, el badge y la línea de progreso cambian cuando administración actualiza el estado.
- En `/mi-cuenta/pedidos/:id`, el detalle refleja estado de pedido y pago sin recarga manual.
- El canal realtime sigue siendo compartido y no duplica conexiones websocket por vista.

## Documentos relacionados

- [Importación de datos](../../datos/importacion-datos.md)
- [Manual técnico](../../operaciones/manual-tecnico.md)
