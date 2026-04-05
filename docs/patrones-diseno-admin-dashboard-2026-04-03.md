# Patron aplicado: admin dashboard SPA alineado con Angelow legacy

Fecha: 2026-04-03

Objetivo:
- Dejar `frontend/src/modules/admin/pages/AdminDashboardPage.vue` visualmente alineado con `angelow/admin/dashboardadmin.php`.
- Mantener todo el look and feel dentro del modulo del dashboard, sin tocar CSS global del admin ni contaminar otras pantallas.

Referencias legacy usadas:
- `angelow/admin/dashboardadmin.php`
- `angelow/css/dashboard.css`
- `angelow/css/dashboardadmin.css`
- `angelow/js/admin/dashboard/dashboard.js`

Cambios aplicados en SPA:
- Se creo un wrapper visual propio del dashboard con halo, hero card, acciones rapidas y breadcrumb al estilo Angelow admin.
- Las KPI cards y metric cards quedaron con overlays tintados, sombras, hover lift y jerarquia visual equivalente al legacy.
- El bloque de charts se ajusto al patron legacy:
  - ventas con combo line + bar
  - colores Angelow (`#0077b6` y `#48cae4`)
  - leyenda abajo
  - lista de estados con barras de progreso
- `Ordenes recientes` ahora usa su clase especifica `recent-orders-card` para heredar el tratamiento visual correcto.
- `Inventario en riesgo`, `Productos destacados` y `Actividad reciente` quedaron apilados como en la vista PHP original.
- Se agrego fallback de actividad reciente a partir de ordenes, clientes nuevos y productos con stock bajo, para no dejar vacio el bloque cuando no existe un endpoint agregado unico.
- Se incorporo consumo de `authHttp` para poblar `Nuevos clientes` desde `GET /admin/customers`.

Archivos tocados:
- `frontend/src/modules/admin/pages/AdminDashboardPage.vue`
- `frontend/src/modules/admin/pages/AdminDashboardPage.css`

Reglas de aislamiento respetadas:
- No se importo CSS legacy global en la SPA.
- Todo el estilo nuevo vive en `frontend/src/modules/admin/pages/AdminDashboardPage.css` cargado con `<style scoped src="./AdminDashboardPage.css"></style>`.
- No se alteraron estilos globales del storefront ni de otras vistas del admin.
