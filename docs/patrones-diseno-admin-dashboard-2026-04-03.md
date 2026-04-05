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

Patron adicional aplicado: Containment / Guard Clause visual contra overflow horizontal

Problema que resuelve:
- El dashboard admin podia empujar el viewport en eje X por elementos decorativos absolutos y por hijos flex/grid sin contraccion explicita, generando desalineacion visual y scroll horizontal indebido.

Solucion aplicada:
- Se agrego contencion horizontal en los wrappers principales del layout admin.
- Se forzo `min-width: 0` en contenedores flex/grid criticos para permitir contraccion real del contenido.
- Se recorto el exceso visual del dashboard con `overflow-x: clip` sin afectar el scroll vertical.
- Se elimino la reserva vertical fija del root del dashboard para que el resumen termine junto al ultimo bloque visible y no deje un hueco grande al final.

Archivos exactos donde se aplico:
- `frontend/src/modules/admin/styles/admin.css`
- `frontend/src/modules/admin/pages/AdminDashboardPage.css`

Relacion con patrones de diseño:
- Se usa una variante de `Facade` a nivel visual en `frontend/src/modules/admin/styles/admin.css`, porque el layout centraliza y encapsula reglas de contencion para todas las vistas hijas del admin.
- Se aplica `Decorator` visual en `frontend/src/modules/admin/pages/AdminDashboardPage.css`, donde los halos y capas decorativas enriquecen la vista sin alterar la estructura funcional del dashboard.

Extension 2026-04-05: realineacion del resumen con el sistema visual admin

Problema que resuelve:
- El dashboard seguia viendose mas ornamental que el resto del admin: superficies con halos, blur, degradados y microinteracciones distintas al lenguaje compartido de productos, pedidos y configuracion.
- Esa diferencia hacia que el resumen se percibiera desalineado aunque la estructura de datos fuera correcta.

Solucion aplicada:
- Se rebajo el nivel de decoracion del dashboard para alinearlo con el sistema admin base.
- Se reemplazaron superficies translúcidas y degradadas por fondos planos, radios mas cercanos al resto del panel y sombras mas sobrias.
- Se suavizaron hovers, botones-link, pills, tablas y bloques de inventario/actividad para que compartan el mismo peso visual del resto de secciones.
- Se desactivo el halo decorativo del root del dashboard para evitar que el resumen parezca una pantalla aparte dentro del admin.

Archivo exacto donde se aplico:
- `frontend/src/modules/admin/pages/AdminDashboardPage.css`

Relacion con patrones de diseño:
- Se mantiene `Facade` visual desde `frontend/src/modules/admin/styles/admin.css` como base comun del admin.
- En esta extension se reduce el uso de `Decorator` en `frontend/src/modules/admin/pages/AdminDashboardPage.css` para priorizar consistencia sistémica sobre ornamentacion local.
