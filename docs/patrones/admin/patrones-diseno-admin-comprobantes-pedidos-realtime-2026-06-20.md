# Patrones de diseño: comprobantes y progreso de pedidos realtime

<!-- indice:auto:start -->
## Índice rápido

- [Contexto](#contexto)
- [Patrón 1: Adapter](#patrón-1-adapter)
- [Patrón 2: Observer](#patrón-2-observer)
- [Patrón 3: State](#patrón-3-state)
- [Resultado esperado](#resultado-esperado)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Contexto

El panel administrativo necesitaba mostrar comprobantes de pago servidos por `payment-service` sin que las rutas `/uploads` se resolvieran contra el servidor Vite. Además, la vista `Mis pedidos` del cliente debía reaccionar con más tolerancia a los eventos websocket de pedidos y mantener el progreso visual alineado con el detalle del pedido.

## Patrón 1: Adapter

- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: convertir rutas de comprobantes relativas o parciales en una URL pública servible por `payment-service`, ocultando a los componentes visuales la diferencia entre `/uploads`, `uploads/payment_proofs` y URLs absolutas.
- Archivos aplicados:
  - `frontend/src/modules/admin/utils/paymentProofs.js`
  - `frontend/src/modules/admin/components/AdminPaymentProofModal.vue`
  - `frontend/src/modules/admin/composables/useAdminPayments.js`
  - `frontend/src/modules/admin/composables/useAdminOrderDetail.js`

## Patrón 2: Observer

- Referencia: https://refactoring.guru/es/design-patterns/observer
- Problema que resuelve: la vista de pedidos del cliente observa eventos del websocket compartido y actualiza el pedido afectado sin recargar manualmente la página.
- Archivos aplicados:
  - `frontend/src/composables/useOrderRealtime.js`
  - `frontend/src/modules/account/pages/OrdersPage.vue`

## Patrón 3: State

- Referencia: https://refactoring.guru/es/design-patterns/state
- Problema que resuelve: el componente de progreso de `Mis pedidos` selecciona la secuencia correcta según estado de orden y estado de pago, incluyendo el flujo de reembolso cancelado, en proceso y reembolsado.
- Archivo aplicado:
  - `frontend/src/modules/account/pages/OrdersPage.vue`

## Resultado esperado

- El modal de comprobante abre y previsualiza archivos desde el origen real del servicio de pagos.
- El detalle administrativo de una orden reutiliza la misma resolución de comprobantes que la tabla de pagos.
- En `/mi-cuenta/pedidos`, los badges y la línea de progreso reaccionan a cambios websocket de estado de orden o pago y luego se sincronizan en segundo plano.

## Documentos relacionados

- [Pedidos en tiempo real](../dashboard/patrones-diseno-dashboard-pedidos-realtime-2026-06-16.md)
- [Admin anuncios y pagos](patrones-diseno-admin-anuncios-pagos-2026-04-18.md)
- [Matriz de requerimientos funcionales](../../referencias/matriz-requerimientos-funcionales-actualizada.md)
