# Patrones aplicados para exportaciones admin reutilizables

<!-- indice:auto:start -->
## Índice rápido

- [Contexto del problema](#contexto-del-problema)
- [Patrón 1: Facade para la infraestructura común de exportación](#patrón-1-facade-para-la-infraestructura-común-de-exportación)
- [Patrón 2: Strategy para columnas y datasets por vista](#patrón-2-strategy-para-columnas-y-datasets-por-vista)
- [Patrón 3: Reuse Component para las acciones de exportación](#patrón-3-reuse-component-para-las-acciones-de-exportación)
- [Ajuste de gobernanza documental y de skill](#ajuste-de-gobernanza-documental-y-de-skill)
- [Resultado esperado](#resultado-esperado)
<!-- indice:auto:end -->

Fecha: 2026-05-24

## Contexto del problema

- Varias vistas del admin seguían exportando con helpers CSV locales, lo que duplicaba lógica y hacía imposible cambiar la plantilla de forma transversal.
- Inventario no tenía exportación y algunas vistas con imágenes no podían reflejarlas en PDF sin implementar otra solución ad hoc.
- También se necesitaba garantizar que todas las exportaciones tomaran el logo actual y la configuración vigente del sitio.

## Patrón 1: Facade para la infraestructura común de exportación

- Referencia: https://refactoring.guru/es/design-patterns/facade
- Problema que resuelve: ocultar la complejidad de `exceljs`, `jspdf`, `jspdf-autotable`, branding, assets e imágenes detrás de una única API compartida para el módulo admin.
- Aplicación exacta:
  - `frontend/src/modules/admin/composables/useAdminDataExport.js`
  - `frontend/src/composables/useAppShell.js`
- Implementación clave:
  - `useAdminDataExport` expone `exportData({ format, ...config })` como punto único de entrada.
  - La composición centraliza la cabecera, el pie, la captura de branding, la descarga binaria, el logo y las imágenes PDF.
  - La resolución de media prioriza `/uploads` same-origin y mantiene candidatos remotos de respaldo para no depender de un único origen entre desarrollo y servidor.
  - Cuando el navegador puede decodificar formatos como `webp` o `avif`, la fachada los rasteriza a `PNG` antes de insertarlos en `jspdf` o `exceljs`.
  - El mismo facade conserva proporción del logo, compacta el encabezado Excel usando una sola columna para la marca y construye un bloque superior PDF con tarjeta institucional blanca para evitar plantillas pesadas o logos deformados.
  - En `frontend/src/modules/admin/composables/useAdminDataExport.js`, la misma fachada ahora reserva altura explícita para el panel de contacto del PDF y evita que la fecha de generación se encime con correo o teléfono.
  - En `frontend/src/modules/admin/composables/useAdminDataExport.js`, el cálculo del anclaje del logo en Excel ahora se centra con métricas de la celda combinada, y el header PDF elimina la barra azul superior para mantener un bloque institucional más limpio.
  - El resto de páginas deja de preocuparse por blobs, CSV manuales o librerías específicas.

## Patrón 2: Strategy para columnas y datasets por vista

- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: cada dominio administrativo necesita columnas, alineaciones, formatos monetarios e imágenes distintas sin romper la infraestructura compartida.
- Aplicación exacta:
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
  - `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
  - `frontend/src/modules/admin/pages/AdminCustomersPage.vue`
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
  - `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`
  - `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue`
  - `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`
  - `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
- Implementación clave:
  - Cada vista define un `build...ExportColumns()` pequeño y declarativo.
  - Ese contrato describe qué columnas salen en PDF o Excel, cómo se formatean y si una columna necesita imagen solo en PDF.
  - El dataset visible de cada página se exporta sin reconstrucciones separadas por formato.

## Patrón 3: Reuse Component para las acciones de exportación

- Referencia: composición sobre duplicación dentro del catálogo de patrones reutilizables del proyecto.
- Problema que resuelve: evitar botones de exportación distintos por pantalla y asegurar que cualquier cambio de UX o loading se propague a todo el admin.
- Aplicación exacta:
  - `frontend/src/modules/admin/components/AdminExportActions.vue`
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
  - `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
  - `frontend/src/modules/admin/pages/AdminCustomersPage.vue`
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
  - `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`
  - `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue`
  - `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`
  - `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
- Implementación clave:
  - `AdminExportActions` encapsula estados de carga, deshabilitado y tono visual (`header` o `results`).
  - Las vistas ya no repiten markup de botones, iconos ni textos de exportación.

## Ajuste de gobernanza documental y de skill

- No corresponde a un patrón de software catalogado, sino a una regla operativa para preservar consistencia en futuras tareas.
- Aplicación exacta:
  - `.agents/skills/skill/SKILL.md`
  - `SKILL.md`
  - `frontend/docs/exportaciones-admin-reutilizables.md`
  - `docs/referencias/librerias-y-composer-uso.md`
- Regla agregada:
  - Toda exportación administrativa PDF/Excel debe apoyarse en infraestructura compartida reutilizable.
  - Si una vista ya muestra imágenes operativas, el PDF compartido debe soportarlas sin crear otra plantilla.
  - Cada cambio sobre esta infraestructura debe actualizar skill, guía frontend, patrón y registro de dependencias en la misma intervención.

## Resultado esperado

- El admin exporta a PDF y Excel desde una sola arquitectura reutilizable.
- Cambiar el botón o la plantilla compartida impacta todas las vistas integradas sin refactors repetidos.
- El branding actual del sitio, su logo y la configuración operativa quedan unificados en todos los documentos exportados.
- La misma infraestructura cubre diferencias de origen entre desarrollo y servidor y evita que imágenes modernas queden fuera del PDF por formato incompatible.