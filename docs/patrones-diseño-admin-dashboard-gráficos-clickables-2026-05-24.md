# Patrones de diseño aplicados: dashboard admin con gráficos desacoplados y tarjetas navegables (2026-05-24)

Fecha: 2026-05-24

## Contexto
- El dashboard admin compartía un único rango temporal entre el gráfico de ventas y el gráfico circular de estados, por lo que cambiar un período alteraba ambos a la vez.
- El doughnut podía mostrar estados técnicos en inglés como `expired`.
- Las secciones `Productos destacados`, `Actividad reciente` y `Órdenes recientes` mostraban datos útiles, pero no siempre permitían navegar al recurso relacionado.
- Cuando un período no tenía ventas ni estados, ambas gráficas quedaban visualmente vacías sin explicar que el resultado correcto era ausencia de datos.

## Patrón 1: Strategy (rango independiente por gráfico)
- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: cada gráfico necesita decidir su propio período sin acoplarse al otro ni reutilizar un único estado global.
- Archivos aplicados:
  - frontend/src/modules/admin/pages/AdminDashboardPage.vue
- Implementación:
  - Se separaron `salesChartRange` y `statusChartRange`.
  - `loadSalesStats()` actualiza únicamente la serie mixta de ingresos/órdenes y sus métricas asociadas.
  - `loadStatusChartStats()` refresca solo el doughnut de estados para el rango seleccionado en ese bloque.

## Patrón 2: Adapter (traducción homogénea de estados técnicos)
- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: valores operativos como `expired` no deben mostrarse crudos en la IU del admin.
- Archivos aplicados:
  - frontend/src/utils/orderPresentation.js
  - frontend/src/modules/admin/pages/AdminDashboardPage.vue
- Implementación:
  - Se amplió la capa compartida `orderPresentation` para traducir `expired` a `Vencido`.
  - El dashboard reutiliza `getOrderStatusLabel()` para mantener consistencia con el resto del admin.

## Patrón 3: Command / Navigation Intent (tarjetas clicables con destino explícito)
- Referencia: https://refactoring.guru/es/design-patterns/command
- Problema que resuelve: las tarjetas del dashboard debían disparar una navegación concreta hacia el recurso representado sin mezclar el render con rutas hardcodeadas en plantilla.
- Archivos aplicados:
  - frontend/src/modules/admin/pages/AdminDashboardPage.vue
  - frontend/src/modules/admin/pages/AdminDashboardPage.css
- Implementación:
  - `openTopProduct()` y `openDashboardActivity()` encapsulan la intención de navegación.
  - `buildDashboardOrderDetailRoute()`, `buildDashboardCustomerRoute()` y `buildDashboardProductRoute()` resuelven el destino según el tipo de entidad.
  - Las tarjetas de productos destacados, actividad reciente y las filas de órdenes recientes pasan a ser controles interactivos accesibles y consistentes con el lenguaje visual del admin.

## Patrón 4: Null Object / Empty State (respuesta explícita cuando el reporte viene vacío)
- Referencia: https://refactoring.guru/es/design-patterns/null-object
- Problema que resuelve: cuando el endpoint de reportes devuelve `rows` o `by_status` vacíos, la UI no debe parecer rota ni dejar un lienzo en blanco sin contexto.
- Archivos aplicados:
  - frontend/src/modules/admin/pages/AdminDashboardPage.vue
  - frontend/src/modules/admin/pages/AdminDashboardPage.css
- Implementación:
  - Se incorporan `hasSalesChartData` y `hasStatusChartData` para decidir si cada gráfico debe renderizar canvas o un `AdminEmptyState`.
  - `renderSalesChart()` y `renderStatusChart()` limpian su instancia cuando el período no tiene datos y dejan visible el mensaje `Aún no hay datos`.
  - El cálculo de `Órdenes pendientes` se toma del propio reporte de ventas del rango activo para no depender de otro gráfico o de un estado residual.

## Resultado esperado
- Cada gráfico del dashboard responde solo a su propio selector de 7, 14 y 30 días.
- Los estados técnicos del gráfico circular se presentan en español.
- Cuando un período no tiene información, el dashboard lo comunica explícitamente con un estado vacío.
- Productos destacados, actividad reciente y órdenes recientes llevan al detalle o módulo correspondiente con un clic.