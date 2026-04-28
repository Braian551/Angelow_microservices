# Patrones de diseño aplicados (Shimmer home/dashboard + scrollbar global + tabla facturas)

Fecha: 2026-04-21

## Contexto del problema
- En inicio y dashboard se mostraban imágenes fallback antes de que cargaran las imágenes reales (logo/slider/avatar), causando parpadeo visual y percepción de error.
- La tabla de facturas en admin tenía una columna "Fuente" que aportaba poco valor operativo y aumentaba el desborde horizontal.
- No existía un scrollbar global consistente para cliente y admin/tablas.

## Patrón 1: State (control explícito de estados de carga de imagen)
- Referencia: https://refactoring.guru/es/design-patterns/state
- Problema que resuelve: evitar mostrar fallback prematuro cuando la imagen real sí existe pero todavía no terminó de cargar.
- Aplicación:
  - frontend/src/modules/home/components/HomeHeroSlider.vue
  - frontend/src/components/layout/SiteHeader.vue
  - frontend/src/modules/admin/components/AdminSidebar.vue
- Implementación clave:
  - Estados separados para `loading`, `loaded` y `error` por imagen.
  - Render de shimmer mientras el estado es "cargando".
  - Transición a imagen real sólo cuando se confirma `load`.

## Patrón 2: Observer (reacción a cambios de configuración/ruta)
- Referencia: https://refactoring.guru/es/design-patterns/observer
- Problema que resuelve: mantener sincronizados logo y shell UI cuando cambian settings o ruta, sin refrescar layout completo.
- Aplicación:
  - frontend/src/components/layout/SiteHeader.vue
  - frontend/src/modules/admin/components/AdminSidebar.vue
  - frontend/src/App.vue
  - frontend/src/composables/useAppShell.js
- Implementación clave:
  - `watch` sobre paths de logo/usuario para resetear estado de carga de forma reactiva.
  - Exposición de `shellLoading` para coordinar shimmer del header con la carga real del shell.

## Patrón 3: Flyweight (tokens de estilo compartidos para scrollbars)
- Referencia: https://refactoring.guru/es/design-patterns/flyweight
- Problema que resuelve: evitar duplicación de estilos de scrollbar y mantener consistencia visual en cliente/admin.
- Aplicación:
  - frontend/src/styles/main.css
  - frontend/src/modules/admin/styles/admin.css
- Implementación clave:
  - Variables globales de scrollbar (track, thumb, hover, size).
  - Reutilización de los mismos tokens en scroll general y en `table-responsive`.

## Ajuste funcional complementario
- frontend/src/modules/admin/pages/AdminInvoicesPage.vue
  - Se eliminó la columna "Fuente" del listado de facturas para reducir desborde y simplificar lectura.

## Resultado esperado
- Inicio y dashboard muestran placeholders shimmer durante carga y dejan de exponer el fallback de forma prematura.
- Header público y sidebar admin se mantienen estables en cambios de configuración sin parpadeos bruscos.
- Scrollbar visual consistente en cliente y admin, incluyendo tablas con overflow horizontal.
- Tabla de facturas más compacta y legible en resoluciones medias y pequeñas.
