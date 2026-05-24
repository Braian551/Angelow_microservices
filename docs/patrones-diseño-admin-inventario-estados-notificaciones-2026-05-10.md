# Patrones de diseño admin: inventario, notificaciones y estados

Fecha: 2026-05-10

## Problema

El panel admin estaba leyendo stock bruto de base de datos mientras tienda y checkout ya operaban con stock efectivo apoyado en Redis. Eso provocaba tres inconsistencias visibles:

1. Productos con variantes agotadas no aparecían como riesgo en inventario admin.
2. Dashboard y campana admin no alertaban correctamente productos agotados o con stock crítico.
3. Órdenes admin exponían demasiados estados técnicos (`created`, `pending_payment`, `refunded`) en lugar de una capa operativa más estable.

## Patrón 1: Single Source of Truth para stock efectivo admin

Referencia: Strategy pragmática sobre una única fuente de lectura operativa.

### Qué resuelve

Evita que admin, dashboard y detalle de producto usen cantidades diferentes según lean BD o Redis. El stock visible del admin pasa a derivarse del mismo criterio efectivo que ya usa la experiencia de compra.

### Archivos aplicados

- `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
- `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
- `frontend/src/modules/admin/pages/AdminDashboardPage.vue`

### Aplicación

- `AdminCatalogController` incorpora `resolveRealtimeAvailableStock()` para priorizar `stock:<variant>` y compensar con `reserved:<variant>` cuando solo exista reserva.
- `inventory()` y `showProduct()` ya no entregan stock bruto como valor principal del admin; entregan stock efectivo.
- `AdminCatalogController::inventory()` ahora también resuelve el color real desde `colors`, adjunta `low_stock_threshold` e `inventory_status` y evita que una variante aparezca como `Sin color` cuando el esquema guarda `color_id`.
- `AdminCatalogController::inventory()` dejó de depender de `psv.updated_at`, columna ausente en el esquema actual, y ahora usa una fecha operativa derivada de `stock_history` para no romper el endpoint admin ni perder orden cronológico en alertas.
- `syncRealtimeStockSnapshot()` realinea Redis cuando el admin ajusta o transfiere stock manualmente.
- `AdminInventoryPage.vue` resume alertas a partir de las variantes afectadas, usa el umbral configurable y mantiene visible qué color/talla disparó la alerta aunque la tabla siga agrupada por producto.
- `AdminDashboardPage.vue` deja de calcular riesgo desde `/admin/products`, lo hace desde `/admin/inventory` y cuenta alertas por variante en lugar de resumirlas por producto.

## Patrón 2: Observer liviano para alertas de inventario

Referencia: Observer.

### Qué resuelve

Permite que la campana admin y el sidebar reaccionen a cambios de inventario sin duplicar lógica en cada vista.

### Archivos aplicados

- `frontend/src/modules/admin/composables/useAdminNotifications.js`
- `frontend/src/modules/admin/components/AdminSidebar.vue`
- `frontend/src/modules/admin/pages/AdminDashboardPage.vue`

### Aplicación

- `useAdminNotifications.js` agrega un snapshot de inventario y genera eventos cuando una variante entra en `low` o `out`, respetando el umbral operativo configurado en admin.
- El módulo `inventory` reutiliza la misma infraestructura de rutas, contadores y lectura por navegación ya existente para órdenes, pagos y facturas.
- `AdminSidebar.vue` muestra badge de inventario pendiente.
- `AdminHeader.vue` ahora resuelve rutas con query params para que la campana abra el ajuste exacto de la variante afectada sin romper la navegación SPA.
- `AdminDashboardPage.vue` reutiliza esos mismos criterios para listar alertas, actividad reciente y acceso directo al ajuste desde el resumen.

## Patrón 5: Deep Link interno para reparar la variante exacta

Referencia: Command combinado con Deep Link interno de interfaz.

### Qué resuelve

Evita que las alertas de inventario obliguen al admin a buscar manualmente el producto y la variante afectada. Cada evento relevante lleva el contexto mínimo para abrir el detalle correcto y dejar lista la acción de reposición.

### Archivos aplicados

- `frontend/src/modules/admin/utils/inventoryPresentation.js`
- `frontend/src/modules/admin/composables/useAdminNotifications.js`
- `frontend/src/modules/admin/components/AdminHeader.vue`
- `frontend/src/modules/admin/pages/AdminDashboardPage.vue`
- `frontend/src/modules/admin/pages/AdminInventoryPage.vue`

### Aplicación

- `inventoryPresentation.js` centraliza la etiqueta de variante, el umbral reutilizable y la construcción de rutas dirigidas a la variante exacta.
- `useAdminNotifications.js` deja de enviar al admin a una vista genérica y construye un destino con `productId` y `variantId` por alerta.
- `AdminHeader.vue` resuelve rutas con query params sin perder el comportamiento SPA ni el marcado de lectura.
- `AdminDashboardPage.vue` vuelve clicables las alertas de inventario para abrir el ajuste desde el resumen.
- `AdminInventoryPage.vue` interpreta esos query params, abre el detalle del producto y lanza el modal de ajuste o transferencia sobre la variante objetivo.

## Patrón 3: State + Scheduler para agotados persistentes y recordatorios

Referencia: State combinado con Scheduler.

### Qué resuelve

Evita perder el seguimiento de una variante agotada cuando el problema ya no depende solo de la lectura instantánea del dashboard. El sistema ahora conserva cuándo se agotó una variante, cuándo se notificó por primera vez y cuándo corresponde reenviar recordatorios si sigue sin reposición.

### Archivos aplicados

- `services/catalog-service/database/migrations/2026_05_10_000001_create_inventory_alerts_table.php`
- `services/catalog-service/app/Models/InventoryAlert.php`
- `services/catalog-service/app/Services/InventoryAlertService.php`
- `services/catalog-service/resources/views/emails/inventory-alert.blade.php`
- `services/catalog-service/routes/console.php`
- `services/catalog-service/app/Http/Controllers/InternalCatalogController.php`
- `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
- `docker-compose.yml`

### Aplicación

- `inventory_alerts` persiste el estado `out` o `resolved` por variante, junto con `out_of_stock_since`, `last_initial_notification_at` y `last_reminder_at`.
- `InventoryAlertService.php` centraliza la transición de estado después de confirmaciones de compra, ajustes y transferencias manuales de stock.
- Cuando una variante llega a `0`, el servicio puede enviar un correo inicial y reutiliza una sola plantilla Blade (`resources/views/emails/inventory-alert.blade.php`) tanto para alerta inmediata como para recordatorio, evitando HTML duplicado.
- `routes/console.php` expone el comando `inventory:dispatch-alert-reminders` y programa su ejecución diaria con `Schedule`, de modo que el recordatorio dependa del tiempo real transcurrido y no del refresco manual del admin.
- `docker-compose.yml` incorpora `catalog-scheduler` para que la programación del catálogo se ejecute de forma autónoma dentro del stack.

## Patrón 4: Adapter para simplificar estados de órdenes en admin

Referencia: Adapter.

### Qué resuelve

Reduce la fuga de estados técnicos hacia la UI administrativa y agrupa varias representaciones backend en una capa operativa consistente.

### Archivos aplicados

- `frontend/src/modules/admin/utils/orderPresentation.js`
- `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
- `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue`
- `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php`
- `services/order-service/app/Http/Controllers/OrderController.php`

### Aplicación

- `orderPresentation.js` centraliza `ADMIN_ORDER_FILTER_STATUSES`, `ADMIN_EDITABLE_ORDER_STATUSES` y `normalizeAdminOrderStatus()`.
- En admin, `created` y `pending_payment` se adaptan a `pending`; `refunded` se adapta a `cancelled` para no reintroducir un estado operativo innecesario en la UI.
- `AdminOrdersPage.vue` y `AdminOrderDetailPage.vue` consumen esa capa centralizada para filtros, badges y modales.
- `AdminOrderController.php` agrupa filtros backend para que `pending` siga incluyendo `created` y `pending_payment`, y `cancelled` incluya `refunded`.
- `OrderController.php` deja de aceptar estados arbitrarios en `updateStatus()` usando una whitelist explícita de estados conocidos.

## Resultado

- Inventario admin, dashboard y notificaciones leen el mismo stock efectivo.
- El endpoint admin de inventario deja de caer por una columna inexistente y vuelve a reflejar variantes agotadas en dashboard y campana.
- Dashboard, inventario y campana vuelven a indicar color, talla y umbral de la variante afectada.
- Las alertas de stock bajo o sin stock ya abren el modal exacto donde el admin puede reponer la variante correspondiente.
- El agotado de una variante ya puede persistirse y disparar correos inmediatos y recordatorios sin duplicar plantillas HTML.
- Los productos con variantes agotadas ya no quedan ocultos por sumas agregadas.
- La UI admin expone menos estados, pero conserva la lógica necesaria de pedidos y reservas.