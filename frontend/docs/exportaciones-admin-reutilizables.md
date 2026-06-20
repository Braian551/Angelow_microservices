# Exportaciones admin reutilizables

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Piezas compartidas](#piezas-compartidas)
- [Flujo recomendado por vista](#flujo-recomendado-por-vista)
- [Contrato de columnas exportables](#contrato-de-columnas-exportables)
- [Branding, logo e imágenes](#branding-logo-e-imágenes)
- [Vistas integradas](#vistas-integradas)
- [Validación operativa](#validación-operativa)
<!-- indice:auto:end -->

## Objetivo

Centralizar las exportaciones administrativas de PDF y Excel para que todas las vistas usen la misma plantilla, el mismo branding y la misma lógica de descarga sin helpers aislados por pantalla.

## Piezas compartidas

- `frontend/src/modules/admin/components/AdminExportActions.vue`
  - Componente reutilizable para los botones de exportación del admin.
  - Soporta tono de cabecera (`header`) y de barra de resultados (`results`).
  - Si cambia este componente, cambian todas las acciones de exportación que lo consumen.
- `frontend/src/modules/admin/composables/useAdminDataExport.js`
  - Fachada compartida para generar archivos Excel (`exceljs`) y PDF (`jspdf` + `jspdf-autotable`).
  - Resuelve branding, logo, metadatos, tablas, anchos, pie de página e imágenes PDF desde una sola fuente.

## Flujo recomendado por vista

1. Reutilizar `AdminExportActions` en la cabecera o en `AdminResultsBar` según el patrón visual ya existente.
2. Crear en la página una función `build...ExportColumns()` con el contrato de columnas visible del dominio.
3. Invocar `exportData({ format, ...config })` desde un wrapper pequeño como `exportProducts(format)` o `exportReport(format)`.
4. Prohibido reintroducir helpers locales de CSV, blobs manuales o plantillas PDF por vista si el caso pertenece a este sistema compartido.

## Contrato de columnas exportables

Las columnas del exportador compartido se describen con objetos declarativos. Las propiedades más usadas son:

- `header`: título visible de la columna.
- `value`: valor textual principal usado por PDF y, si no se indica otra estrategia, también por Excel.
- `excelValue`: valor específico para Excel cuando conviene conservar un número o una fecha sin formateo visual.
- `excelType`: tipo de celda para Excel (`number`, `currency`, etc.).
- `includeInExcel` y `includeInPdf`: permiten ocultar columnas por formato.
- `pdfImage`: callback para resolver una imagen solo en PDF.
- `fallbackType`: tipo de fallback visual (`product`, `avatar`, `banner`, etc.).
- `pdfWidth`, `pdfImageSize`, `width`, `align`: control fino de tabla y presentación.

## Branding, logo e imágenes

- El branding se toma desde la fuente compartida de configuración del sitio, usando la misma base que consume `useAppShell`.
- El PDF y el Excel deben heredar el nombre actual del sitio, su logo vigente, información de contacto y colores configurados.
- El encabezado compartido debe mantener una jerarquía visual clara: logo proporcionado, bloque institucional blanco, datos de contacto ordenados y título del reporte separado del bloque institucional.
- En Excel, el logo debe ocupar una sola columna visual del bloque superior, centrado dentro de su celda combinada para evitar espacio sobrante a la izquierda o desalineación visual.
- En PDF, la tarjeta de contacto debe reservar altura suficiente para el detalle de contacto y la fecha de generación sin solapes entre líneas.
- En PDF, el bloque institucional superior no debe dibujar una barra azul horizontal por encima del logo; el acento visual debe quedar solo en bordes y contenido.
- Si una vista del admin ya muestra imágenes operativas, se deben incluir solo en PDF mediante `pdfImage`, sin abrir una plantilla exclusiva para esa pantalla.
- La resolución de assets debe priorizar `/uploads/...` same-origin para evitar CORS en desarrollo, pero conservar hosts remotos como fallback para despliegues donde uploads viva en otro origen.
- Si una imagen operativa llega en formatos como `webp` o `avif`, la infraestructura compartida la rasteriza a `PNG` antes de incrustarla en el PDF.
- Las imágenes insertadas en PDF deben respetar proporción tanto en el logo del encabezado como en miniaturas de filas para evitar deformaciones visibles.
- Las vistas sin imágenes deben seguir exportando con la misma cabecera y pie compartidos.

## Vistas integradas

Las vistas administrativas que ya consumen este contrato compartido son:

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

## Validación operativa

Cuando se modifique esta infraestructura o alguna vista integrada, ejecutar como mínimo:

```bash
docker compose up -d --build frontend
docker compose exec frontend sh -c "rm -rf /app/node_modules/.vite"
docker compose restart frontend
docker compose logs --tail=120 frontend
docker compose ps
docker compose exec frontend sh -c "npm run build"
```

Después del build, validar en navegador al menos una exportación PDF y una exportación Excel de una vista impactada, confirmando cabecera, branding, columnas y descarga correcta.
Si la validación incluye imágenes, no basta con el snackbar: confirmar que el PDF generado incrusta objetos de imagen reales y no solo rutas o celdas vacías.