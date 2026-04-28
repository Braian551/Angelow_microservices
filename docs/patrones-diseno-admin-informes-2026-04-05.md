# Patrones de diseño aplicados en informes admin

Fecha: 2026-04-05

## Contexto
Se migró la vista de informes de ventas, productos populares y clientes recurrentes al panel admin SPA, manteniendo la lógica operativa del sistema anterior pero consumiendo datos desde servicios distribuidos y usando componentes reutilizables del dashboard.

## Patrones aplicados

### 1. Adapter
- Problema que resuelve: los datos de informes llegan desde fuentes distintas y con contratos diferentes. Ventas y recurrencia salen de órdenes, el total de clientes y perfiles salen de auth, y la metadata visual de productos sale de catálogo.
- Aplicado en archivos:
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
  - `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php`
  - `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
- Implementación:
  - en frontend se normalizan y fusionan filas de productos/clientes antes de renderizar tablas, gráficas y modales;
  - en backend se devuelven claves homogéneas para ventas, productos y clientes a partir de tablas reales (`orders`, `order_items`).

### 2. Facade
- Problema que resuelve: evitar que cada sección de informes reimplemente estructura visual, estados vacíos, loaders, encabezados, modales y tarjetas.
- Aplicado en archivos:
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
  - `frontend/src/modules/admin/styles/admin.css`
- Implementación:
  - la vista compone `AdminPageHeader`, `AdminCard`, `AdminStatsGrid`, `AdminTableShimmer`, `AdminEmptyState` y `AdminModal` como una fachada visual estable para las tres secciones.

### 3. Command
- Problema que resuelve: centralizar acciones de usuario que disparan comportamientos claros y repetibles como aplicar filtros, exportar, imprimir y abrir detalle.
- Aplicado en archivos:
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
- Implementación:
  - acciones como `loadCurrentReport`, `resetFilters`, `exportReport`, `printReport` y `openDetailModal` encapsulan la intención del usuario y desacoplan UI de lógica operativa.

### 4. Template Method
- Problema que resuelve: cada tipo de informe comparte el flujo general de cargar datos, validar filtros, transformar resultados y renderizar gráficas, pero cambia en la fuente y el detalle del procesamiento.
- Aplicado en archivos:
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
  - `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php`
- Implementación:
  - se usa un flujo común por sección (`loadCurrentReport`) y helpers de backend (`buildAnalyticsOrdersQuery`, `buildAnalyticsOrderItemsQuery`) para conservar pasos comunes con variaciones controladas.

## Resultado
- Informes admin ahora tienen una sola experiencia SPA consistente con el dashboard actual.
- Ventas excluye canceladas por defecto y respeta filtros reales.
- Productos populares usa agregados de `order_items` y enriquece categoría/imagen desde catálogo.
- Clientes recurrentes usa órdenes reales para recurrencia y auth para total/perfil.