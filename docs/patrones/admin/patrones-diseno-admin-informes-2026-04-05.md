# Patrones de diseño aplicados en informes admin

<!-- indice:auto:start -->
## Índice rápido

- [Contexto](#contexto)
- [Patrones aplicados](#patrones-aplicados)
  - [1. Adapter](#1-adapter)
  - [2. Facade](#2-facade)
  - [3. Command](#3-command)
  - [4. Template Method](#4-template-method)
- [Resultado](#resultado)
- [Extensión 2026-05-10: consolidación real de ventas distribuidas + legacy](#extensión-2026-05-10-consolidación-real-de-ventas-distribuidas-legacy)
  - [5. Adapter](#5-adapter)
  - [6. Aggregator](#6-aggregator)
- [Extensión 2026-06-07: simplificación de acciones del encabezado](#extensión-2026-06-07-simplificación-de-acciones-del-encabezado)
  - [7. Command](#7-command)
- [Extensión 2026-06-20: coherencia de gráficas de informes](#extensión-2026-06-20-coherencia-de-gráficas-de-informes)
  - [8. Adapter](#8-adapter)
  - [9. Strategy](#9-strategy)
<!-- indice:auto:end -->

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

## Extensión 2026-05-10: consolidación real de ventas distribuidas + legacy

### 5. Adapter
- Problema que resuelve: el reporte de ventas tomaba solo una fuente a la vez y dejaba datos vacíos o parciales cuando las órdenes estaban repartidas entre la base distribuida y la base legacy; además, por defecto solo excluía `cancelled` y dejaba entrar variantes como `canceled` o `refunded`.
- Aplicado en archivos:
  - services/order-service/app/Http/Controllers/Admin/AdminOrderController.php
- Implementación:
  - `reportSales` ahora combina filas de órdenes del microservicio y legacy usando la misma estrategia de mezcla que el dashboard de órdenes recientes antes de calcular ingresos, períodos, estados y métodos de pago.
  - La exclusión por defecto de canceladas se alinea con el grupo administrativo completo (`cancelled`, `canceled`, `refunded`) para que las cifras y gráficas no queden incoherentes.

### 6. Aggregator
- Problema que resuelve: productos populares y clientes recurrentes podían quedar parciales cuando cada reporte elegía una sola fuente o cuando los perfiles de cliente dependían de tablas locales no disponibles en el servicio de órdenes.
- Aplicado en archivos:
  - services/order-service/app/Http/Controllers/Admin/AdminOrderController.php
- Implementación:
  - `reportProducts` ahora mezcla líneas de pedido del microservicio y legacy antes de agrupar por producto, evitando que el ranking dependa de un fallback de una sola fuente.
  - `reportCustomers` ahora arma la recurrencia sobre órdenes ya consolidadas e hidratadas, y usa un fallback adicional hacia auth-service con el bearer admin actual para completar nombre, correo y teléfono cuando el endpoint interno no resuelve todos los perfiles.
  - Si un `user_id` no existe ni en auth ni en legacy disponible, el sistema conserva el registro como cliente sin identidad resoluble en lugar de inventar datos.

## Extensión 2026-06-07: simplificación de acciones del encabezado

### 7. Command
- Problema que resuelve: el encabezado compartido de informes mostraba una acción de impresión innecesaria para ventas, productos populares y clientes recurrentes, añadiendo ruido visual y una ruta de interacción poco útil frente a las exportaciones reales del módulo.
- Aplicado en archivos:
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
- Implementación:
  - se elimina `printReport` y su botón asociado del `AdminPageHeader`, dejando como acciones primarias `Restablecer` y las exportaciones reutilizables de Excel/PDF;
  - la vista conserva el patrón Command porque las acciones visibles siguen encapsuladas en handlers claros (`resetFilters`, `exportReport`, `openDetailModal`) sin exponer lógica operativa en el template.

## Extensión 2026-06-20: coherencia de gráficas de informes

### 8. Adapter
- Problema que resuelve: la gráfica “Top clientes por valor” quedaba vacía cuando el filtro de clientes recurrentes exigía dos o más órdenes, aunque existieran clientes con compras reales en `top_customers`.
- Aplicado en archivos:
  - `frontend/src/modules/admin/composables/useAdminReports.js`
- Implementación:
  - `loadCustomersReport` separa `customerRows` para la tabla de recurrencia y `topCustomerRows` para la gráfica de valor, reutilizando el payload `top_customers` del backend y enriqueciendo ambos conjuntos con perfiles de auth cuando hay `user_id`.

### 9. Strategy
- Problema que resuelve: las gráficas de ventas podían verse comprimidas o dar lectura incorrecta cuando solo existía un período de datos.
- Aplicado en archivos:
  - `frontend/src/modules/admin/composables/useAdminReports.js`
- Implementación:
  - las escalas de ventas, órdenes y comparativa mensual parten de cero y formatean moneda de forma consistente, mientras la agrupación temporal sigue definida por el filtro activo (`día`, `semana`, `mes` o `año`).
