# Reembolsos de cliente y política por producto

## Índice

- [Patrones aplicados](#patrones-aplicados)
- [Archivos intervenidos](#archivos-intervenidos)
- [Problema resuelto](#problema-resuelto)

## Patrones aplicados

- **State**: se separa el estado de solicitud de reembolso (`requested`) del estado operativo de la orden, evitando tratar un pedido completado como cancelado.
- **Facade**: `frontend/src/services/orderApi.js` concentra la operación HTTP de solicitud de reembolso para que la vista no conozca detalles de endpoint ni multipart.
- **Adapter**: `services/order-service/app/Http/Controllers/OrderController.php` consulta la política de reembolso del catálogo interno y la adapta al contrato visible de Mis pedidos (`refund_available`, `refund_deadline_at`, `refund_request_status`).

## Archivos intervenidos

- `frontend/src/modules/account/pages/OrdersPage.vue`
- `frontend/src/services/orderApi.js`
- `frontend/src/utils/orderPresentation.js`
- `frontend/src/modules/admin/pages/AdminProductFormPage.vue`
- `frontend/src/modules/admin/components/products/AdminProductGeneralTab.vue`
- `frontend/src/modules/admin/composables/useAdminProductForm.js`
- `frontend/src/modules/admin/utils/productFormPayload.js`
- `frontend/src/modules/admin/views/AdminProductFormPage.css`
- `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
- `services/catalog-service/app/Http/Controllers/InternalCatalogController.php`
- `services/catalog-service/database/migrations/2026_06_19_000001_add_refund_policy_to_products_table.php`
- `services/order-service/app/Http/Controllers/OrderController.php`
- `services/order-service/routes/api.php`
- `services/order-service/database/migrations/2026_06_19_000001_create_order_refund_requests_table.php`

## Problema resuelto

El cliente ya no cancela compras desde Mis pedidos. El administrador conserva la gestión normal de estados, mientras que el cliente puede solicitar reembolso únicamente si la orden está completada y algún producto tiene política de reembolso vigente por días configurados.
