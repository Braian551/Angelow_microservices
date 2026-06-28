# Administración de reembolsos

<!-- indice:auto:start -->
## Índice rápido

- [Contexto](#contexto)
- [Patrones aplicados](#patrones-aplicados)
- [Archivos intervenidos](#archivos-intervenidos)
- [Resultado funcional](#resultado-funcional)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Contexto

La solicitud de reembolso del cliente ya existía como entidad separada de la cancelación de orden. Faltaba una sección administrativa para revisar evidencia, motivo y transición operativa sin editar manualmente estados de pago desde órdenes.

## Patrones aplicados

- **State**: `frontend/src/modules/admin/composables/useAdminRefunds.js` y `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php` modelan el ciclo `requested -> processing -> completed` o `rejected`, sincronizando `payment_status` con reglas explícitas. El valor antiguo `approved` se adapta a `processing` para que aceptar una solicitud la deje inmediatamente en proceso.
- **Facade**: `frontend/src/modules/admin/pages/AdminRefundsPage.vue` consume el composable `useAdminRefunds` y reutiliza `frontend/src/modules/admin/components/AdminPaymentProofModal.vue` como visor de evidencia, sin mezclar acceso HTTP, normalización ni reglas de transición dentro del template.
- **Adapter**: `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php` adapta solicitudes `order_refund_requests` con datos de `orders`, tolerando diferencias de columnas por conexión antes de entregar un contrato único al frontend. En `frontend/src/modules/admin/composables/useAdminRefunds.js`, el mismo patrón adapta motivos y valores técnicos de BD (`producto_defectuoso`, estados y similares) a texto operativo en español antes de renderizar tabla o modal.
- **Command**: cada botón de acción en `AdminRefundsPage.vue` abre un modal con confirmación y ejecuta una operación concreta (`approve`, `reject`, `complete`) con bloqueo de doble envío. `approve` guarda `processing`, por lo que no existe un botón separado para marcar en proceso.
- **Observer**: `frontend/src/modules/admin/composables/useAdminNotifications.js`, `frontend/src/composables/useOrderRealtime.js`, `frontend/src/modules/admin/components/AdminSidebar.vue` y `frontend/src/modules/admin/components/AdminHeader.vue` observan eventos `order.refund.updated` y `order.payment_status.updated` para refrescar campana, badge y lectura automática al entrar en `/admin/reembolsos`.
- **Facade de notificación**: `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php` reutiliza `services/notification-service` para registrar la notificación del cliente y enviar correo con la plantilla global, sin duplicar HTML de email en el flujo administrativo.

## Archivos intervenidos

- `services/order-service/routes/api.php`
- `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php`
- `frontend/src/router/index.js`
- `frontend/src/composables/useOrderRealtime.js`
- `frontend/src/modules/account/pages/OrdersPage.vue`
- `frontend/src/modules/account/pages/OrderDetailPage.vue`
- `frontend/src/modules/admin/pages/AdminRefundsPage.vue`
- `frontend/src/modules/admin/composables/useAdminRefunds.js`
- `frontend/src/modules/admin/composables/useAdminNotifications.js`
- `frontend/src/modules/admin/views/AdminRefundsPage.css`
- `frontend/src/modules/admin/components/AdminPaymentProofModal.vue`
- `frontend/src/modules/admin/components/AdminSidebar.vue`
- `frontend/src/modules/admin/components/AdminHeader.vue`
- `docs/referencias/matriz-requerimientos-funcionales-actualizada.md`
- `docs/patrones/README.md`

## Resultado funcional

El administrador puede abrir `/admin/reembolsos`, filtrar solicitudes, revisar motivo/evidencia con un visor reutilizable, aceptar, rechazar o completar reembolsos. Al aceptar, la solicitud pasa inmediatamente a `processing` y el pago queda en `pending_refund`; al rechazar restaura `verified` y al completar usa `refunded`.

Cada cambio administrativo de reembolso publica eventos WebSocket de orden/reembolso, refresca las notificaciones del admin sin esperar el intervalo de polling y crea una notificación para el cliente con correo HTML mediante `notification-service`. En `Mis pedidos` y en el detalle del pedido, el progreso de reembolso reconoce `refund_requested`, `pending_refund` y `refunded`, además de `refund_request_status` devuelto por la API.

Las notificaciones, badges, motivos y detalles muestran texto operativo en español, incluyendo `refund_requested` como "Reembolso solicitado" y `producto_defectuoso` como "Producto defectuoso".

## Documentos relacionados

- `docs/referencias/matriz-requerimientos-funcionales-actualizada.md`
- `docs/patrones/dashboard/patrones-diseno-reembolsos-cliente-producto-2026-06-19.md`
