# Patrón aplicado: formulario de producto como orquestador Vue

<!-- indice:auto:start -->
## Índice

- [Objetivo](#objetivo)
- [Archivos intervenidos](#archivos-intervenidos)
- [Patrones aplicados](#patrones-aplicados)
- [Decisiones de arquitectura](#decisiones-de-arquitectura)
- [Etapas adicionales](#etapas-adicionales)
- [Refactorización de lógica administrativa - sliders, configuración, categorías y colecciones](#refactorización-de-lógica-administrativa---sliders-configuración-categorías-y-colecciones)
- [Refactorización de lógica administrativa - tallas, métodos de envío, reglas de envío y códigos de descuento](#refactorización-de-lógica-administrativa---tallas-métodos-de-envío-reglas-de-envío-y-códigos-de-descuento)
- [Validaciones](#validaciones)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Objetivo

Separar responsabilidades en `frontend/src/modules/admin/pages/AdminProductFormPage.vue` sin cambiar rutas, contratos de API, payloads, validaciones, carga de imágenes, flujo de creación, edición, variantes, tallas, precios, stock, SKU ni navegación SPA.

## Archivos intervenidos

- `frontend/src/modules/admin/pages/AdminProductFormPage.vue`: queda como página orquestadora.
- `frontend/src/modules/admin/components/products/AdminProductGeneralTab.vue`: encapsula identificación, clasificación, precios, detalles y descripción.
- `frontend/src/modules/admin/components/products/AdminProductImagePanel.vue`: encapsula imagen principal y visibilidad.
- `frontend/src/modules/admin/components/products/AdminProductVariantsTab.vue`: encapsula listado, métricas e imágenes de variantes.
- `frontend/src/modules/admin/components/products/AdminProductVariantModal.vue`: encapsula tallas, precios, inventario, SKU y código de barras.
- `frontend/src/modules/admin/composables/useAdminProductForm.js`: centraliza estado reactivo, carga de catálogos, carga del producto, validaciones, guardado y feedback.
- `frontend/src/modules/admin/utils/productFormPayload.js`: conserva la construcción de payload y `FormData`.
- `frontend/src/modules/admin/utils/productSlug.js`: conserva la generación y normalización del slug.
- `frontend/src/modules/admin/utils/productSku.js`: conserva la generación y normalización de SKU.
- `frontend/src/modules/admin/views/AdminProductFormPage.css`: contiene los estilos que antes vivían en `<style scoped>`.
- `frontend/src/modules/admin/pages/AdminPaymentsPage.vue`: queda con raíz única `.admin-payments-page`, importa el CSS externo de la vista y conserva intacta la lógica de pagos.
- `frontend/src/modules/admin/views/AdminPaymentsPage.css`: contiene los estilos locales de pagos que antes vivían en `<style scoped>`, encapsulados bajo `.admin-payments-page`.
- `frontend/src/modules/admin/pages/AdminCustomersPage.vue`: conserva la lógica de clientes intacta, importa el CSS externo de la vista y agrega wrapper interno para el modal de detalle.
- `frontend/src/modules/admin/views/AdminCustomersPage.css`: contiene los estilos locales de clientes que antes vivían en `<style scoped>`, encapsulados bajo `.admin-customers-page`.
- `frontend/src/modules/admin/pages/AdminReportsPage.vue`: conserva la lógica de informes intacta, importa el CSS externo de la vista y agrega wrapper interno para el modal de detalle.

- `frontend/src/modules/admin/pages/AdminReviewsPage.vue`: conserva intacta la lógica de reseñas, filtros, gráficos, moderación, respuestas y exportaciones, importa el CSS externo de la vista y agrega wrapper interno para el modal de detalle.
- `frontend/src/modules/admin/views/AdminReviewsPage.css`: contiene los estilos locales de reseñas que antes vivían en `<style scoped>`, encapsulados bajo `.admin-reviews-page`.
- `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`: conserva intacta la lógica de preguntas, respuestas administrativas, filtros, estadísticas, gráficos, moderación y exportaciones, importa el CSS externo de la vista y agrega wrapper interno para el modal de detalle.
- `frontend/src/modules/admin/views/AdminQuestionsPage.css`: contiene los estilos locales de preguntas que antes vivían en `<style scoped>`, encapsulados bajo `.admin-questions-page`.
- `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`: conserva intacta la lógica de anuncios, formularios, imágenes, fechas, previews, estados y exportaciones, importa el CSS externo de la vista y agrega wrapper interno para los modales de detalle y edición.
- `frontend/src/modules/admin/views/AdminAnnouncementsPage.css`: contiene los estilos locales de anuncios que antes vivían en `<style scoped>`, encapsulados bajo `.admin-announcements-page`.
- `frontend/src/modules/admin/pages/AdminSlidersPage.vue`: conserva intacta la lógica de sliders, imágenes, orden, previews, enlaces, fechas funcionales, estados y formulario, importa el CSS externo de la vista y agrega wrapper interno para el modal de edición/creación.
- `frontend/src/modules/admin/views/AdminSlidersPage.css`: contiene los estilos locales de sliders que antes vivían en `<style scoped>`, encapsulados bajo `.admin-sliders-page`.
- `frontend/src/modules/admin/pages/AdminSettingsPage.vue`: conserva intacta la lógica de configuraciones, formularios, imágenes, previews, redes sociales, contacto, guardado, eventos globales y `FormData`, e importa el CSS externo de la vista.
- `frontend/src/modules/admin/views/AdminSettingsPage.css`: contiene los estilos locales de configuración general que antes vivían en `<style scoped>`, encapsulados bajo `.admin-settings-page`.
- `frontend/src/modules/admin/pages/AdminCategoriesPage.vue`: conserva intacta la lógica de categorías, imágenes, slugs, filtros, paginación, productos asociados, confirmaciones y `FormData`, e importa el CSS externo de la vista.
- `frontend/src/modules/admin/views/AdminCategoriesPage.css`: contiene los estilos locales responsivos de categorías que antes vivían en `<style scoped>`, encapsulados bajo `.admin-categories-page`.
- `frontend/src/modules/admin/pages/AdminCollectionsPage.vue`: conserva intacta la lógica de colecciones, imágenes, previews, slugs, fechas, filtros, paginación, productos asociados, confirmaciones y `FormData`, e importa el CSS externo de la vista.
- `frontend/src/modules/admin/views/AdminCollectionsPage.css`: contiene los estilos locales responsivos de colecciones que antes vivían en `<style scoped>`, encapsulados bajo `.admin-collections-page`.
- `frontend/src/modules/admin/pages/AdminSizesPage.vue`: conserva intacta la lógica de tallas, asociaciones, estados, orden, validaciones y confirmaciones, e importa el CSS externo de la vista.
- `frontend/src/modules/admin/views/AdminSizesPage.css`: contiene los estilos locales responsivos de tallas que antes vivían en `<style scoped>`, encapsulados bajo `.admin-sizes-page`.
- `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`: conserva intacta la lógica de métodos de envío, costos, tiempos, cobertura, estados, validaciones y confirmaciones, e importa el CSS externo de la vista.
- `frontend/src/modules/admin/views/AdminShippingMethodsPage.css`: contiene los estilos locales de métodos de envío que antes vivían en `<style scoped>`, encapsulados bajo `.admin-shipping-methods-page`.
- `.agents/skills/skill/SKILL.md`: agrega la sección obligatoria de arquitectura frontend Vue.

## Patrones aplicados

`Component`

- Archivos: `frontend/src/modules/admin/components/products/AdminProductGeneralTab.vue`, `frontend/src/modules/admin/components/products/AdminProductImagePanel.vue`, `frontend/src/modules/admin/components/products/AdminProductVariantsTab.vue`, `frontend/src/modules/admin/components/products/AdminProductVariantModal.vue`.
- Problema que resuelve: divide bloques visuales grandes del formulario en componentes hijos mantenibles, sin duplicar `AdminModal`, `AdminToggleSwitch`, `AdminInfoTooltip`, `AdminCard` ni `AdminPageHeader`.

`Facade`

- Archivo: `frontend/src/modules/admin/composables/useAdminProductForm.js`.
- Problema que resuelve: ofrece a la página una interfaz única para estado, acciones, validaciones, carga de datos y guardado, mientras oculta detalles de API, imágenes, tabs, modal y normalización.

`Builder`

- Archivo: `frontend/src/modules/admin/utils/productFormPayload.js`.
- Problema que resuelve: construye el objeto y el `FormData` del producto de forma aislada, preservando nombres de campos y estructura de variantes esperada por `catalog-service`.

`Strategy`

- Archivos: `frontend/src/modules/admin/utils/productSlug.js`, `frontend/src/modules/admin/utils/productSku.js`.
- Problema que resuelve: separa las reglas de generación de slug y SKU para que puedan evolucionar sin aumentar el tamaño de la página ni mezclar reglas puras con renderizado.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminPaymentsPage.vue`, `frontend/src/modules/admin/views/AdminPaymentsPage.css`.
- Problema que resuelve: mantiene la página de pagos como punto de coordinación de la vista mientras delega el detalle visual local al CSS externo encapsulado, sin tocar validaciones, comprobantes, filtros, estados, acciones, exportaciones, endpoints ni payloads.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminCustomersPage.vue`, `frontend/src/modules/admin/views/AdminCustomersPage.css`.
- Problema que resuelve: mantiene la página de clientes como orquestadora de perfiles, pedidos asociados, filtros, paginación, exportación y acciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar lógica ni contratos.



`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminReviewsPage.vue`, `frontend/src/modules/admin/views/AdminReviewsPage.css`.
- Problema que resuelve: mantiene la página de reseñas como orquestadora de carga, estadísticas, filtros, gráficos, paginación, moderación, respuestas administrativas y exportaciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento ni contratos.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`, `frontend/src/modules/admin/views/AdminQuestionsPage.css`.
- Problema que resuelve: mantiene la página de preguntas como orquestadora de carga, estadísticas, filtros, gráficos, paginación, respuestas administrativas, moderación y exportaciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento ni contratos.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`, `frontend/src/modules/admin/views/AdminAnnouncementsPage.css`.
- Problema que resuelve: mantiene la página de anuncios como orquestadora de carga, filtros, formularios, imágenes, fechas, previews, estados, confirmaciones y exportaciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento, `FormData`, nombres de campos ni contratos.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminSlidersPage.vue`, `frontend/src/modules/admin/views/AdminSlidersPage.css`.
- Problema que resuelve: mantiene la página de sliders como orquestadora de carga, creación, edición, eliminación, activación, ordenamiento, imágenes, previews y enlaces, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento, `FormData`, nombres de campos ni contratos.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminSettingsPage.vue`, `frontend/src/modules/admin/views/AdminSettingsPage.css`.
- Problema que resuelve: mantiene la página de configuración como orquestadora de carga, secciones, formularios, imágenes, previews, redes sociales, contacto, validaciones, guardado y eventos globales, mientras delega los estilos locales al CSS externo encapsulado sin cambiar configuraciones, contratos, `FormData`, nombres de campos ni comportamiento.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminCategoriesPage.vue`, `frontend/src/modules/admin/views/AdminCategoriesPage.css`.
- Problema que resuelve: mantiene la página de categorías como orquestadora de carga, filtros, paginación, creación, edición, eliminación, activación, imágenes, slugs, productos asociados y confirmaciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento, `FormData`, nombres de campos ni contratos.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminCollectionsPage.vue`, `frontend/src/modules/admin/views/AdminCollectionsPage.css`.
- Problema que resuelve: mantiene la página de colecciones como orquestadora de carga, filtros, paginación, creación, edición, eliminación, activación, imágenes, previews, slugs, fechas, productos asociados y confirmaciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento, `FormData`, nombres de campos ni contratos.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminSizesPage.vue`, `frontend/src/modules/admin/views/AdminSizesPage.css`.
- Problema que resuelve: mantiene la página de tallas como orquestadora de carga, filtros, creación, edición, eliminación, activación, asociaciones, orden y confirmaciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento, nombres de campos ni contratos.

`Facade`

- Archivos: `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`, `frontend/src/modules/admin/views/AdminShippingMethodsPage.css`.
- Problema que resuelve: mantiene la página de métodos de envío como orquestadora de carga, filtros, creación, edición, eliminación, activación, costos, tiempos, cobertura y confirmaciones, mientras delega los estilos locales al CSS externo encapsulado sin cambiar comportamiento, valores numéricos, nombres de campos ni contratos.

## Decisiones de arquitectura

- La página mantiene `RouterLink`, encabezado, tarjeta principal y coordinación de componentes, pero no contiene la lógica extensa del formulario.
- El CSS se movió a `frontend/src/modules/admin/views/AdminProductFormPage.css` y se importa desde la página para conservar la convención de estilos por vista.
- Corrección de regresión: el CSS externo quedó encapsulado bajo `.admin-product-form-page`, y el modal de variantes incluye la misma raíz dentro del contenido teletransportado por `AdminModal`, para conservar el aislamiento que antes aportaba `<style scoped>`.
- Etapa `AdminProductsPage.vue`: se movieron los estilos locales de productos a `frontend/src/modules/admin/views/AdminProductsPage.css`, encapsulados bajo `.admin-products-page`; los modales de vista rápida y zoom agregan wrapper interno con la misma raíz por el `Teleport` de `AdminModal`.
- Etapa `AdminInventoryPage.vue`: se movieron los estilos locales de inventario a `frontend/src/modules/admin/views/AdminInventoryPage.css`, encapsulados bajo `.admin-inventory-page`; los modales de detalle, ajuste y transferencia agregan wrapper interno con la misma raíz por el `Teleport` de `AdminModal`.
- Etapa `AdminOrdersPage.vue`: se movieron los estilos locales de órdenes a `frontend/src/modules/admin/views/AdminOrdersPage.css`, encapsulados bajo `.admin-orders-page`; los modales de detalle, cambio de estado, estado de pago y acciones masivas agregan wrapper interno con la misma raíz por el `Teleport` de `AdminModal`.
- Etapa `AdminPaymentsPage.vue`: se movieron los estilos locales de pagos a `frontend/src/modules/admin/views/AdminPaymentsPage.css`, encapsulados bajo `.admin-payments-page`; el modal de cuenta visible agrega wrapper interno `.admin-payments-page admin-payments-page--modal` por el `Teleport` de `AdminModal`.
- Etapa `AdminCustomersPage.vue`: se movieron los estilos locales de clientes a `frontend/src/modules/admin/views/AdminCustomersPage.css`, encapsulados bajo `.admin-customers-page`; el modal de detalle agrega wrapper interno `.admin-customers-page admin-customers-page--modal` por el `Teleport` de `AdminModal`.

- Etapa `AdminReviewsPage.vue`: se movieron los estilos locales de reseñas a `frontend/src/modules/admin/views/AdminReviewsPage.css`, encapsulados bajo `.admin-reviews-page`; el modal de detalle agrega wrapper interno `.admin-reviews-page admin-reviews-page--modal` por el `Teleport` de `AdminModal`.
- Etapa `AdminQuestionsPage.vue`: se movieron los estilos locales de preguntas a `frontend/src/modules/admin/views/AdminQuestionsPage.css`, encapsulados bajo `.admin-questions-page`; el modal de detalle agrega wrapper interno `.admin-questions-page admin-questions-page--modal` por el `Teleport` de `AdminModal`.
- Etapa `AdminAnnouncementsPage.vue`: se movieron los estilos locales de anuncios a `frontend/src/modules/admin/views/AdminAnnouncementsPage.css`, encapsulados bajo `.admin-announcements-page`; los modales de detalle y edición agregan wrapper interno `.admin-announcements-page admin-announcements-page--modal` por el `Teleport` de `AdminModal`.
- Etapa `AdminSlidersPage.vue`: se movieron los estilos locales de sliders a `frontend/src/modules/admin/views/AdminSlidersPage.css`, encapsulados bajo `.admin-sliders-page`; el modal de creación/edición agrega wrapper interno `.admin-sliders-page admin-sliders-page--modal` por el `Teleport` de `AdminModal`, y los previews de imagen/carrusel quedan dentro de esa raíz.
- Etapa `AdminSettingsPage.vue`: se movieron los estilos locales de configuración general a `frontend/src/modules/admin/views/AdminSettingsPage.css`, encapsulados bajo `.admin-settings-page`; la vista no usa `AdminModal` ni `Teleport`, y los previews de logo, favicon e imágenes quedan dentro de la raíz principal existente.
- Etapa `AdminCategoriesPage.vue`: se movieron los estilos locales responsivos de categorías a `frontend/src/modules/admin/views/AdminCategoriesPage.css`, encapsulados bajo `.admin-categories-page`; se conserva la clase global previa `admin-entity-page` en la raíz y se agrega `.admin-categories-page` como raíz exclusiva, mientras el modal de creación/edición agrega wrapper interno `.admin-categories-page admin-categories-page--modal` por el `Teleport` de `AdminModal`.
- Etapa `AdminCollectionsPage.vue`: se movieron los estilos locales responsivos de colecciones a `frontend/src/modules/admin/views/AdminCollectionsPage.css`, encapsulados bajo `.admin-collections-page`; se conserva la clase global previa `admin-entity-page` en la raíz y se agrega `.admin-collections-page` como raíz exclusiva, mientras el modal de creación/edición agrega wrapper interno `.admin-collections-page admin-collections-page--modal` por el `Teleport` de `AdminModal`.
- Etapa `AdminSizesPage.vue`: se movieron los estilos locales responsivos de tallas a `frontend/src/modules/admin/views/AdminSizesPage.css`, encapsulados bajo `.admin-sizes-page`; se conserva la clase compartida `admin-entity-page` en la raíz y el modal de creación/edición agrega el wrapper interno `.admin-sizes-page admin-sizes-page--modal` por el `Teleport` de `AdminModal`.
- Etapa `AdminShippingMethodsPage.vue`: se movieron los estilos locales de métodos de envío a `frontend/src/modules/admin/views/AdminShippingMethodsPage.css`, encapsulados bajo `.admin-shipping-methods-page`; se conserva la raíz específica existente y los modales de detalle y edición agregan el wrapper interno `.admin-shipping-methods-page admin-shipping-methods-page--modal` por el `Teleport` de `AdminModal`.
- No se cambiaron endpoints, payloads, `FormData`, rutas públicas ni nombres de campos enviados al backend.
- La separación se hizo solo para el formulario de producto como primera etapa segura; las demás vistas grandes quedan pendientes para una etapa posterior validada.

## Etapas adicionales

### AdminShippingRulesPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue` y `frontend/src/modules/admin/views/AdminShippingRulesPage.css`.
- Clase raíz: se conserva `.admin-shipping-rules-page` en la vista y se reutiliza `.admin-shipping-rules-page.admin-shipping-rules-page--modal` dentro de los contenidos teletransportados por `AdminModal`.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminCard`, `AdminFilterCard`, `AdminResultsBar`, `AdminPagination`, `AdminModal`, `AdminEmptyState`, `AdminStatsGrid`, `AdminTableShimmer`, `AdminToggleSwitch`, `admin-detail-grid`, `admin-editor-grid`, `admin-surface-card`, `status-badge` y el resto de clases compartidas ya presentes.
- Tratamiento de modales: el modal de detalle y el modal de creación/edición agregan wrapper interno con la raíz de la vista para conservar el aislamiento visual que antes aportaba `<style scoped>`.
- Encapsulado CSS: el bloque local se trasladó a `frontend/src/modules/admin/views/AdminShippingRulesPage.css`, se importa desde la vista y todos los selectores propios quedaron encapsulados bajo `.admin-shipping-rules-page`.
- Cero cambio funcional: no se modificaron carga, creación, edición, eliminación, validaciones, filtros, paginación, endpoints, contratos API, payloads ni nombres de campos.

### AdminDiscountCodesPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue` y `frontend/src/modules/admin/views/AdminDiscountCodesPage.css`.
- Clase raíz: se conserva `.admin-discount-codes-page` en la vista y se reutiliza `.admin-discount-codes-page.admin-discount-codes-page--modal` dentro de detalle, editor, envío masivo y campaña para usuarios específicos.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminCard`, `AdminFilterCard`, `AdminResultsBar`, `AdminPagination`, `AdminModal`, `AdminEmptyState`, `AdminStatsGrid`, `AdminTableShimmer`, `AdminToggleSwitch`, `AdminExportActions`, `admin-detail-grid`, `admin-editor-grid`, `admin-surface-card`, `status-badge` y las demás clases compartidas ya existentes.
- Tratamiento de modales: cada contenido renderizado por `AdminModal` ahora tiene wrapper interno con la raíz de la vista para evitar fugas de estilos en `Teleport`.
- Encapsulado CSS: el bloque local se trasladó a `frontend/src/modules/admin/views/AdminDiscountCodesPage.css`, se importa desde la vista y los selectores locales quedaron prefijados bajo `.admin-discount-codes-page`, incluyendo los casos dentro de `@media`.
- Cero cambio funcional: no se modificaron carga, creación, edición, eliminación, campañas, filtros, búsqueda, paginación, exportaciones, endpoints, contratos API, payloads ni nombres de campos.

## Refactorización de lógica administrativa - tallas, métodos de envío, reglas de envío y códigos de descuento

### AdminSizesPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminSizesPage.vue` y `frontend/src/modules/admin/composables/useAdminSizes.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminSizes.js`.
- Utils reutilizados o creados: se reutilizó `frontend/src/modules/admin/composables/useAdminPagination.js`; no fue necesario crear `sizePresentation.js`.
- Lógica extraída: carga de tallas, búsqueda, filtros, paginación, formulario, validaciones, creación, edición, eliminación, activación, refresco y confirmaciones.
- Lógica conservada en la página: template, imports de componentes, wiring declarativo de tabla, modal y tarjetas estadísticas.
- Organización mediante comentarios: imports visuales y bloque de orquestación en la página; estado principal, filtros y paginación, formulario, valores derivados, carga, CRUD, activación, watchers/ciclo de vida y API pública en el composable.
- Services preservados: `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~470 líneas concentradas en la vista; después ~199 líneas en la página y ~280 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismos campos `name`, `label`, `value`, `abbreviation`, `description`, `sort_order`, `active`; mismas validaciones numéricas, mismas confirmaciones y mismas restricciones por asociaciones.

### AdminShippingMethodsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue` y `frontend/src/modules/admin/composables/useAdminShippingMethods.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminShippingMethods.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/composables/useAdminPagination.js` y `frontend/src/modules/admin/composables/useAdminDataExport.js`; no fue necesario crear `shippingMethodPresentation.js`.
- Lógica extraída: carga de métodos, búsqueda, filtros, paginación, formulario, costos, tiempos de entrega, cobertura, creación, edición, eliminación, activación, detalle, exportación y refresco.
- Lógica conservada en la página: template, imports de componentes, helpers visuales de tabla y conexión declarativa de modales y exportaciones.
- Organización mediante comentarios: imports visuales y bloque de orquestación en la página; estado principal, filtros y paginación, formulario, costos y tiempos, cobertura, carga, CRUD, modales, exportación y API pública en el composable.
- Services preservados: `shippingHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~730 líneas concentradas en la vista; después ~375 líneas en la página y ~385 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismos campos `name`, `description`, `carrier`, `code`, `base_cost`, `estimated_days_min`, `estimated_days_max`, `coverage_cities`, `icon`, `sort_order`, `active`; mismo tratamiento de `null`, mismos montos COP y mismos endpoints.

### AdminShippingRulesPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue` y `frontend/src/modules/admin/composables/useAdminShippingRules.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminShippingRules.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/composables/useAdminPagination.js` y `frontend/src/modules/admin/composables/useAdminDataExport.js`; no fue necesario crear `shippingRulePresentation.js`.
- Lógica extraída: carga de reglas y métodos asociados, búsqueda, filtros, paginación, formulario, condiciones, rangos, cobertura geográfica, creación, edición, eliminación, activación, detalle, exportación y refresco.
- Lógica conservada en la página: template, imports de componentes y render declarativo de tabla, badges y modales.
- Organización mediante comentarios: imports visuales y bloque de orquestación en la página; estado principal, filtros y paginación, formulario, métodos asociados, condiciones y rangos, cobertura, carga, CRUD, modales y API pública en el composable.
- Services preservados: `shippingHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~620 líneas concentradas en la vista; después ~289 líneas en la página y ~338 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismos campos de método, prioridad, zonas, rangos de peso/precio/cantidad, `fixed_cost`, `additional_cost`, `free_shipping`, fechas y estado; misma semántica para `null`, cero, arrays vacíos y prioridad.

### AdminDiscountCodesPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue` y `frontend/src/modules/admin/composables/useAdminDiscountCodes.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminDiscountCodes.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/composables/useAdminPagination.js` y `frontend/src/modules/admin/composables/useAdminDataExport.js`; no fue necesario crear `discountCodePresentation.js`.
- Lógica extraída: carga de códigos, búsqueda, filtros, paginación, formulario, generación automática de código, validaciones, creación, edición, eliminación, elegibilidad, campañas masivas y específicas, exportación, estados, límites, fechas y refresco.
- Lógica conservada en la página: template, imports de componentes, navegación visual y conexión declarativa de modales, tabla, campañas y exportaciones.
- Organización mediante comentarios: imports visuales y bloque de orquestación en la página; estado principal, filtros y paginación, formulario, campañas, valores derivados, validaciones, carga, CRUD, exportación, modales y API pública en el composable.
- Services preservados: `discountHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~1221 líneas concentradas en la vista; después ~670 líneas en la página y ~740 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismos campos `code`, `type`, `value`, `max_uses`, `start_date`, `expires_at`, `active`, `is_single_use`; misma generación en mayúsculas, mismos arrays de IDs para campañas, mismos límites/fechas, mismas exportaciones y mismos endpoints.

## Validaciones

- `npm run build` en `frontend`: exitoso.
- `docker compose exec -T frontend npm run build`: exitoso después de la corrección de aislamiento CSS.
- `npm run build` después de extraer CSS de `AdminProductsPage.vue`: exitoso.
- `npm run build` después de extraer CSS de `AdminInventoryPage.vue`: exitoso.
- `npm run build` después de extraer CSS de `AdminOrdersPage.vue`: exitoso.
- Etapa `AdminPaymentsPage.vue`: `npm run build`, `docker compose up -d --build frontend`, `docker compose exec -T frontend npm run build`, logs de `frontend`, `docker compose ps` y `HTTP 200` en `/admin/pagos` exitosos.
- Etapa `AdminCustomersPage.vue`: `npm run build`, `docker compose up -d --build frontend`, `docker compose exec -T frontend npm run build`, logs de `frontend`, `docker compose ps` y `HTTP 200` en `/admin/clientes` exitosos.

- Etapa `AdminReviewsPage.vue`: `npm run build`, `docker compose up -d --build frontend`, `docker compose exec -T frontend npm run build`, logs de `frontend`, `docker compose ps` y `HTTP 200` en `/admin/resenas` exitosos.
- Etapa `AdminQuestionsPage.vue`: `npm run build`, `docker compose up -d --build frontend`, `docker compose exec -T frontend npm run build`, logs de `frontend`, `docker compose ps` y `HTTP 200` en `/admin/preguntas` exitosos.
- Etapa `AdminAnnouncementsPage.vue`: `npm run build`, `docker compose up -d --build frontend`, `docker compose exec -T frontend npm run build`, logs de `frontend`, `docker compose ps` y `HTTP 200` en `/admin/anuncios` exitosos.
- Etapa `AdminSlidersPage.vue`: CSS externo creado y encapsulado bajo `.admin-sliders-page`; no hubo cambios funcionales en imágenes, orden, previews, enlaces, estados, endpoints, `FormData` ni payloads. Validaciones operativas ejecutadas en la intervención correspondiente.
- Etapa `AdminSettingsPage.vue`: CSS externo creado y encapsulado bajo `.admin-settings-page`; no hubo cambios funcionales en configuraciones, imágenes, previews, redes sociales, contacto, eventos globales, endpoints, `FormData`, nombres de campos ni payloads. Validaciones operativas ejecutadas en la intervención correspondiente.
- Etapa `AdminCategoriesPage.vue`: CSS externo creado y encapsulado bajo `.admin-categories-page`; no hubo cambios funcionales en categorías, imágenes, slugs, jerarquías, filtros, paginación, productos asociados, confirmaciones, endpoints, `FormData`, nombres de campos ni payloads. Validaciones operativas ejecutadas en la intervención correspondiente.
- Etapa `AdminCollectionsPage.vue`: CSS externo creado y encapsulado bajo `.admin-collections-page`; no hubo cambios funcionales en colecciones, imágenes, previews, slugs, fechas, productos asociados, orden, filtros, paginación, confirmaciones, endpoints, `FormData`, nombres de campos ni payloads. Validaciones operativas ejecutadas en la intervención correspondiente.
- Etapa `AdminSizesPage.vue`: CSS externo creado y encapsulado bajo `.admin-sizes-page`; se conservó `admin-entity-page` y no hubo cambios funcionales en tallas, asociaciones, estados, orden, validaciones, endpoints, nombres de campos ni payloads. El build local intermedio fue exitoso.
- Etapa `AdminShippingMethodsPage.vue`: CSS externo creado y encapsulado bajo `.admin-shipping-methods-page`; no hubo cambios funcionales en métodos, costos, tiempos, cobertura, estados, validaciones, endpoints, nombres de campos ni payloads. Validaciones operativas ejecutadas al finalizar ambas etapas.
- Etapa `AdminShippingRulesPage.vue`: CSS externo creado y encapsulado bajo `.admin-shipping-rules-page`; no hubo cambios funcionales en reglas, recargos, filtros, validaciones, endpoints, nombres de campos ni payloads. El build local intermedio fue exitoso.
- Etapa `AdminDiscountCodesPage.vue`: CSS externo creado y encapsulado bajo `.admin-discount-codes-page`; no hubo cambios funcionales en códigos, campañas, filtros, exportaciones, validaciones, endpoints, nombres de campos ni payloads. El build local final fue exitoso.

- Pendiente por bloqueo de herramienta local: validación visual con consola del navegador integrado; el kernel falló por `windows sandbox failed: spawn setup refresh`.

## Documentos relacionados

- [Índice de patrones](../README.md)
- [Patrones de productos admin](patrones-diseno-admin-productos-2026-04-03.md)

### AdminBulkDiscountsPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue` y `frontend/src/modules/admin/views/AdminBulkDiscountsPage.css`.
- Clase raíz: se conserva `.admin-bulk-discounts-page` en la vista y se reutiliza `.admin-bulk-discounts-page.admin-bulk-discounts-page--modal` dentro de detalle y editor.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminCard`, `AdminFilterCard`, `AdminResultsBar`, `AdminPagination`, `AdminModal`, `AdminEmptyState`, `AdminStatsGrid`, `AdminTableShimmer`, `AdminToggleSwitch`, `AdminInfoTooltip`, `AdminExportActions`, `admin-detail-grid`, `admin-editor-grid`, `admin-surface-card` y `status-badge`.
- Tratamiento de modales: el modal de detalle y el modal de creación/edición agregan wrapper interno con la raíz de la vista para conservar el aislamiento visual del contenido teletransportado.
- Encapsulado CSS: el bloque local se trasladó a `frontend/src/modules/admin/views/AdminBulkDiscountsPage.css`, se importa desde la vista y los selectores propios quedaron encapsulados bajo `.admin-bulk-discounts-page`.
- Cero cambio funcional: no se modificaron reglas masivas, filtros, búsqueda, paginación, exportaciones, validaciones, endpoints, contratos API, payloads ni nombres de campos.

### AdminDiscountSpecificCampaignPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminDiscountSpecificCampaignPage.vue` y `frontend/src/modules/admin/views/AdminDiscountSpecificCampaignPage.css`.
- Clase raíz: la vista usa `.admin-discount-specific-campaign-page` como raíz principal.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminCard`, `AdminFilterCard`, `AdminResultsBar`, `AdminPagination`, `AdminEmptyState`, `AdminShimmer`, `AdminTableShimmer`, `AdminToggleSwitch`, `AdminInfoTooltip`, `admin-entity-name` y las clases compartidas de la tabla administrativa.
- Tratamiento de modales: la vista no utiliza `AdminModal` ni `Teleport` en esta etapa, por lo que no requirió wrappers `--modal`.
- Encapsulado CSS: el bloque local se trasladó a `frontend/src/modules/admin/views/AdminDiscountSpecificCampaignPage.css`, se importa desde la vista y los selectores locales quedaron prefijados bajo `.admin-discount-specific-campaign-page`, incluyendo `:deep(...)` y `@media`.
- Cero cambio funcional: no se modificaron carga, selección de destinatarios, código asociado, canales, envío, filtros, paginación, endpoints, contratos API, payloads ni nombres de campos.

- Etapa `AdminBulkDiscountsPage.vue`: CSS externo creado y encapsulado bajo `.admin-bulk-discounts-page`; no hubo cambios funcionales en reglas masivas, rangos, filtros, validaciones, endpoints, nombres de campos ni payloads. El build local intermedio fue exitoso.
- Etapa `AdminDiscountSpecificCampaignPage.vue`: CSS externo creado y encapsulado bajo `.admin-discount-specific-campaign-page`; no hubo cambios funcionales en campañas específicas, destinatarios, canales, filtros, endpoints, nombres de campos ni payloads. El build local final fue exitoso.
### AdminAdministratorsPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminAdministratorsPage.vue` y `frontend/src/modules/admin/views/AdminAdministratorsPage.css`.
- Clase raíz: se conserva la clase compartida `admin-entity-page` y se agrega `.admin-administrators-page` como raíz específica de la vista.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminCard`, `AdminPagination`, `AdminModal`, `AdminEmptyState`, `AdminTableShimmer`, `AdminToggleSwitch`, `AdminInfoTooltip`, `dashboard-table`, `status-badge`, `admin-entity-actions` y las clases compartidas del formulario administrativo.
- Tratamiento de modales: el modal de creación/edición agrega wrapper interno `.admin-administrators-page.admin-administrators-page--modal` para conservar el aislamiento del contenido teletransportado.
- Encapsulado CSS: el bloque local se trasladó a `frontend/src/modules/admin/views/AdminAdministratorsPage.css`, se importa desde la vista y los selectores propios quedaron encapsulados bajo `.admin-administrators-page`.
- Cero cambio funcional: no se modificaron carga, creación, edición, eliminación, roles, permisos, validaciones, confirmaciones, endpoints, contratos API, payloads ni nombres de campos.

### AdminInvoicesPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminInvoicesPage.vue` y `frontend/src/modules/admin/views/AdminInvoicesPage.css`.
- Clase raíz: la vista conserva `.admin-invoices-page` como raíz principal.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminStatsGrid`, `AdminFilterCard`, `AdminResultsBar`, `AdminCard`, `AdminPagination`, `AdminModal`, `AdminEmptyState`, `AdminTableShimmer`, `dashboard-table`, `status-badge` y las clases compartidas de filtros y acciones administrativas.
- Tratamiento de modales: el modal de detalle agrega wrapper interno `.admin-invoices-page.admin-invoices-page--modal` dentro del contenido renderizado por `AdminModal`.
- Encapsulado CSS: el bloque local se trasladó a `frontend/src/modules/admin/views/AdminInvoicesPage.css`, se importa desde la vista y los selectores propios quedaron encapsulados bajo `.admin-invoices-page`.
- Cero cambio funcional: no se modificaron carga, detalle, descarga, reenvío por correo, filtros, búsqueda, paginación, exportaciones, endpoints, contratos API, payloads ni nombres de campos.

### AdminOrderDetailPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue` y `frontend/src/modules/admin/views/AdminOrderDetailPage.css`.
- Clase raíz: la vista conserva `.admin-order-detail-page` como raíz principal.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminCard`, `AdminModal`, `AdminInfoTooltip`, `AdminTableImage`, `AdminTableShimmer`, `AdminPaymentProofModal`, `dashboard-table`, `status-badge`, `admin-detail-summary` y el resto de clases compartidas ya utilizadas por la vista.
- Tratamiento de modales: los modales de edición, cambio de estado y cambio de estado de pago agregan wrapper interno `.admin-order-detail-page.admin-order-detail-page--modal`; `AdminPaymentProofModal` no se modificó.
- Encapsulado CSS: el bloque local se trasladó a `frontend/src/modules/admin/views/AdminOrderDetailPage.css`, se importa desde la vista y los selectores propios quedaron encapsulados bajo `.admin-order-detail-page`, incluyendo reglas dentro de `@media`.
- Cero cambio funcional: no se modificaron carga del pedido, cliente, direcciones, productos, historial, comprobante, tracking, cambios de estado, pagos, endpoints, contratos API, payloads ni reglas de transición.

### AdminDashboardPage.vue

- Archivos creados y modificados: `frontend/src/modules/admin/pages/AdminDashboardPage.vue`, `frontend/src/modules/admin/views/AdminDashboardPage.css` y se removió `frontend/src/modules/admin/pages/AdminDashboardPage.css` después de actualizar el import.
- Clase raíz: la vista conserva `.admin-dashboard-page` como raíz principal.
- Clases compartidas conservadas: se mantuvieron `AdminPageHeader`, `AdminStatsGrid`, `AdminCard`, `AdminEmptyState`, `AdminTableShimmer`, `AdminTableImage`, `dashboard-table`, `status-badge`, `btn`, `btn-secondary` y las demás clases compartidas del dashboard.
- Tratamiento de modales: la vista no usa `AdminModal` ni `Teleport` en esta etapa, por lo que no requirió wrappers `--modal`.
- Encapsulado CSS: el archivo exclusivo del dashboard se movió a `frontend/src/modules/admin/views/AdminDashboardPage.css`, se importa desde la vista y sus selectores exclusivos quedaron encapsulados bajo `.admin-dashboard-page`.
- Cero cambio funcional: no se modificaron métricas, gráficas, datasets, filtros de rango, navegación clicable, endpoints, contratos API, payloads ni cálculos.


## Saneamiento UTF-8 del frontend

- Alcance auditado: barrido completo de `frontend/src` en archivos `.vue`, `.js`, `.css` y `.json` dentro de `src`, más revisión explícita de `docs/patrones/admin/patrones-diseno-admin-product-form-refactor-vue-2026-06-10.md`.
- Archivos realmente modificados por saneamiento: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue`, `frontend/src/modules/admin/views/AdminOrderDetailPage.css`, `frontend/src/modules/admin/views/AdminDashboardPage.css`, `frontend/src/components/layout/Header.css` y este documento de patrones.
- Tipos de mojibake encontrados: secuencias corruptas de codificación y combinaciones dañadas en textos visibles, tooltips, comentarios y documentación.
- Correcciones realizadas: se restauraron tildes, `ñ`, comillas angulares, viñetas textuales y comentarios dañados sin alterar lógica, selectores, valores CSS, rutas ni contratos.
- Confirmación de cero cambio funcional: el saneamiento solo corrigió texto corrupto y comentarios; no se modificaron estructuras Vue, JavaScript ejecutable, endpoints, payloads, imports funcionales ni estilos operativos.
- Resultado del barrido final: sin coincidencias injustificadas de secuencias corruptas dentro de `frontend/src` y este documento.
- Resultado de builds: `npm run build` local exitoso, `docker compose up -d --build frontend` exitoso y `docker compose exec -T frontend npm run build` exitoso después del saneamiento.

## Cierre de la etapa CSS del módulo administrativo

- Alcance auditado: revisión completa de `frontend/src/modules/admin/pages`, `frontend/src/modules/admin/views`, `frontend/src/modules/admin/styles/admin.css` y verificación de dependencias visuales en `frontend/src/modules/admin/components`.
- Resultado de `AdminForgotPasswordPage.vue`: no contiene bloque `<style>`, no usa `AdminModal` ni `Teleport`, mantiene la raíz `auth-page recovery-page`, pertenece al layout `auth` mediante la ruta `/admin/recuperar` y reutiliza de forma válida `frontend/src/modules/auth/views/ForgotPasswordView.css`; no requirió mover CSS a `admin/views`.
- Páginas que conservan estilos locales justificados: ninguna dentro de `frontend/src/modules/admin/pages`; `AdminForgotPasswordPage.vue` queda justificada como excepción porque reutiliza el CSS compartido del flujo auth y no mantiene estilos locales.
- Archivos CSS movidos en esta auditoría de cierre: ninguno; el barrido confirmó que no quedan archivos `.css` dentro de `frontend/src/modules/admin/pages`.
- Imports corregidos: ninguno; todos los imports CSS de páginas admin resolvieron correctamente y no se detectaron imports duplicados ni rutas rotas.
- Archivos huérfanos detectados: ninguno; cada archivo de `frontend/src/modules/admin/views` tiene una página correspondiente y está importado por su vista esperada.
- Resultado del encapsulado: los archivos CSS de `frontend/src/modules/admin/views` conservan su raíz específica por página, no quedaron bloques `<style scoped>` pendientes en páginas admin y no se detectaron selectores globales accidentales introducidos por la etapa previa.
- Resultado de `AdminModal` y `Teleport`: no se detectó `Teleport` en las páginas administrativas auditadas y las vistas que usan `AdminModal` ya incluyen wrappers internos `clase-pagina clase-pagina--modal`, por lo que no fue necesario intervenirlas en este cierre.
- Resultado UTF-8: el barrido de secuencias corruptas sobre el alcance auditado y este documento se mantuvo limpio, sin reintroducción de mojibake.
- Confirmación de cero cambio funcional: esta etapa solo auditó y documentó la separación de CSS; no se modificaron lógica, componentes, endpoints, payloads, services ni comportamiento visible de las páginas admin.
- Resultado de builds: `npm run build` local, reconstrucción de `frontend`, build dentro del contenedor y validaciones HTTP quedaron pendientes de esta misma etapa para ejecutarse después de cerrar la documentación.
## Refactorización de lógica administrativa — pedidos, pagos, inventario e informes

### AdminOrdersPage.vue

- Responsabilidad extraída: carga de pedidos, filtros, búsqueda, paginación, selección, modales, cambios de estado, cambios de pago, refresco y manejo de errores.
- Composable creado o reutilizado: `frontend/src/modules/admin/composables/useAdminOrders.js`.
- Utils creados o reutilizados: se reutilizó `frontend/src/modules/admin/utils/orderPresentation.js`; no se duplicaron utilidades.
- Services preservados: se mantuvo `orderHttp` desde `frontend/src/services/http` y no se modificó `orderApi.js`.
- Reducción aproximada de la página: la vista quedó en ~517 líneas y se trasladaron ~736 líneas de lógica reactiva/flujo al composable.
- Confirmación de contratos conservados: mismos eventos, mismos payloads, mismos estados y mismas rutas de detalle.
- Resultado del build individual: `npm run build` exitoso.

## Refactorización de lógica administrativa — clientes, reseñas, preguntas y anuncios

### AdminCustomersPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminCustomersPage.vue` y `frontend/src/modules/admin/composables/useAdminCustomers.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminCustomers.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/composables/useAdminCustomerProfiles.js`, `frontend/src/modules/admin/composables/useAdminPagination.js`, `frontend/src/modules/admin/composables/useAdminDataExport.js` y `resolveMediaUrl`/`handleMediaError`; no fue necesario crear `customerPresentation.js`.
- Responsabilidad extraída: carga de clientes, búsqueda, filtros, paginación, estados de carga, selección de cliente, apertura y cierre del detalle, bloqueo o desbloqueo, refresco, datos derivados, coordinación con perfiles y coordinación con exportaciones.
- Responsabilidad que permanece en la página: composición del template, imports de componentes, helper visual de avatar y conexión entre eventos del template y el composable.
- Services preservados: se mantuvieron `authHttp` y `orderHttp` desde `frontend/src/services/http`; no se modificó `authApi.js`.
- Líneas aproximadas antes y después: antes ~790 líneas concentradas en la vista; después ~288 líneas en la página y ~505 líneas en el composable principal.
- Resultado del build individual: `npm run build` exitoso.
- Confirmación de contratos preservados: mismos identificadores, mismos campos de cliente, mismos filtros, misma paginación, mismas exportaciones, mismos payloads y mismas reglas administrativas.

### AdminReviewsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminReviewsPage.vue` y `frontend/src/modules/admin/composables/useAdminReviews.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminReviews.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/composables/useAdminCustomerProfiles.js`, `frontend/src/modules/admin/composables/useAdminPagination.js`, `frontend/src/modules/admin/composables/useAdminDataExport.js` y `resolveMediaUrl`/`handleMediaError`; no fue necesario crear `reviewPresentation.js`.
- Responsabilidad extraída: carga de reseñas, búsqueda, filtros, paginación, estadísticas, selección, apertura y cierre de detalle, aprobación, cambio de estado, eliminación, respuesta administrativa, refresco y valores computados de presentación.
- Responsabilidad que permanece en la página: composición del template, imports de componentes, helper visual de avatar y conexión declarativa con el composable.
- Services preservados: se mantuvo `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~740 líneas concentradas en la vista; después ~342 líneas en la página y ~402 líneas en el composable principal.
- Resultado del build individual: `npm run build` exitoso.
- Confirmación de contratos preservados: mismas estrellas, mismos comentarios, mismos estados, mismas métricas, mismos payloads, mismas notificaciones y mismos endpoints.

### AdminQuestionsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminQuestionsPage.vue` y `frontend/src/modules/admin/composables/useAdminQuestions.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminQuestions.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/composables/useAdminCustomerProfiles.js`, `frontend/src/modules/admin/composables/useAdminPagination.js`, `frontend/src/modules/admin/composables/useAdminDataExport.js` y `resolveMediaUrl`/`handleMediaError`; no fue necesario crear `questionPresentation.js`.
- Responsabilidad extraída: carga de preguntas, búsqueda, filtros, paginación, selección, apertura y cierre de detalle, respuesta administrativa, moderación, eliminación, refresco y valores derivados.
- Responsabilidad que permanece en la página: composición del template, imports de componentes, helper visual de avatar y orquestación del render de detalle y formulario.
- Services preservados: se mantuvo `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~640 líneas concentradas en la vista; después ~301 líneas en la página y ~339 líneas en el composable principal.
- Resultado del build individual: `npm run build` exitoso.
- Confirmación de contratos preservados: mismos textos, misma relación pregunta-respuesta-producto-cliente, mismos contadores, mismos payloads, mismas notificaciones y mismos endpoints.

### AdminAnnouncementsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue` y `frontend/src/modules/admin/composables/useAdminAnnouncements.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminAnnouncements.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/utils/storeLinkOptions.js`, `frontend/src/modules/admin/composables/useAdminPagination.js`, `frontend/src/modules/admin/composables/useAdminDataExport.js` y `resolveMediaUrl`/`handleMediaError`; no fue necesario crear `announcementPresentation.js`.
- Responsabilidad extraída: carga de anuncios, búsqueda, filtros, paginación, formulario, selección, creación, edición, eliminación, activación, fechas, preview, modales, estados de carga, errores, refresco y valores computados.
- Responsabilidad que permanece en la página: composición del template, imports de componentes, preview visual con `TopAnnouncementBar` y `PromoBanner`, helper visual de imágenes y conexión entre eventos y composable.
- Services preservados: se mantuvieron `notificationHttp` y `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~1135 líneas concentradas en la vista; después ~523 líneas en la página y ~612 líneas en el composable principal.
- Resultado del build individual: `npm run build` exitoso.
- Confirmación de contratos preservados: mismos campos, mismos payloads, mismas fechas, mismas notificaciones, mismos endpoints, mismo preview y mismo flujo de publicación.

### AdminPaymentsPage.vue

- Responsabilidad extraída: carga de pagos, filtros, búsqueda, paginación, selección, apertura/cierre de comprobante, aprobación, rechazo, mensajes y refresco.
- Composable creado o reutilizado: `frontend/src/modules/admin/composables/useAdminPayments.js`.
- Utils creados o reutilizados: se reutilizaron `getPaymentMethodLabel`, `getPaymentStatusBadgeClass` y `getPaymentStatusLabel` desde `frontend/src/modules/admin/utils/orderPresentation.js`; no se creó `paymentPresentation.js`.
- Services preservados: se mantuvieron `paymentHttp`, `orderHttp` y las llamadas existentes de `frontend/src/services/paymentApi`.
- Reducción aproximada de la página: la vista quedó en ~370 líneas y se trasladaron ~510 líneas de lógica reactiva/flujo al composable.
- Confirmación de contratos conservados: mismos estados de pago, mismo motivo de rechazo, mismos montos, mismas referencias y mismo contrato de `AdminPaymentProofModal`.
- Resultado del build individual: `npm run build` exitoso.

### AdminInventoryPage.vue

- Responsabilidad extraída: carga de inventario, filtros, búsqueda, paginación, alertas, selección, modales, ajustes de stock, refresco y coordinación con tiempo real.
- Composable creado o reutilizado: `frontend/src/modules/admin/composables/useAdminInventory.js`.
- Utils creados o reutilizados: se reutilizaron `frontend/src/modules/admin/utils/inventoryPresentation.js`, `frontend/src/composables/useStockRealtime.js`, `resolveMediaUrl` y `validatePositiveInteger`; no se duplicaron reglas.
- Services preservados: se mantuvo `catalogHttp` y no se cambiaron contratos de API ni eventos de stock en tiempo real.
- Reducción aproximada de la página: la vista quedó en ~374 líneas y se trasladaron ~783 líneas de lógica reactiva/flujo al composable.
- Confirmación de contratos conservados: mismas cantidades, mismas validaciones numéricas, mismos payloads, mismos nombres de campo y mismas reglas de inventario.
- Resultado del build individual: `npm run build` exitoso.

### AdminReportsPage.vue

- Responsabilidad extraída: carga de reportes, rango de fechas, filtros, estados de carga, errores, datasets calculados, exportaciones, refresco, métricas derivadas y coordinación de Chart.js.
- Composable creado o reutilizado: `frontend/src/modules/admin/composables/useAdminReports.js`.
- Utils creados o reutilizados: se reutilizó `frontend/src/modules/admin/composables/useAdminDataExport.js`; no fue necesario crear `reportPresentation.js`.
- Services preservados: se mantuvieron `orderHttp`, `catalogHttp` y `authHttp` desde `frontend/src/services/http`.
- Reducción aproximada de la página: la vista quedó en ~520 líneas y se trasladaron ~851 líneas de lógica reactiva/flujo al composable.
- Confirmación de contratos conservados: mismas métricas, mismos datasets, mismas opciones visibles, mismos payloads de exportación y mismas rutas de informes.
- Resultado del build individual: `npm run build` exitoso.

## Refactorización de lógica administrativa - sliders, configuración, categorías y colecciones

### AdminSlidersPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminSlidersPage.vue` y `frontend/src/modules/admin/composables/useAdminSliders.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminSliders.js`.
- Utils reutilizados o creados: se reutilizó `frontend/src/modules/admin/utils/storeLinkOptions.js`; no fue necesario crear `sliderPresentation.js`.
- Lógica extraída: carga de sliders, catálogos de enlaces, formulario, validaciones, imagen y preview, creación, edición, eliminación, activación, reordenamiento drag and drop, refresco, errores y limpieza de recursos.
- Lógica conservada en la página: template, imports de componentes, integración visual de `HomeHeroSlider` y helpers visuales `resolveMediaUrl`/`handleMediaError`.
- Organización mediante comentarios: imports visuales y bloque único de orquestación en la página; estado general, formulario, previews, carga, CRUD, orden visual, ciclo de vida y API pública en el composable.
- Services preservados: `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~445 líneas concentradas en la vista; después ~265 líneas en la página y ~456 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismos endpoints, mismo `FormData`, mismos nombres de campos (`title`, `subtitle`, `link_url`, `sort_order`, `active`, `image_file`, `image_url`), misma paginación visual y mismo comportamiento de preview/orden.

### AdminSettingsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminSettingsPage.vue` y `frontend/src/modules/admin/composables/useAdminSettings.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminSettings.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/constants/siteSettingsEvents.js`, `resolveMediaUrl` y `handleMediaError`; no fue necesario crear `settingsPresentation.js`.
- Lógica extraída: carga de configuración, normalización de valores, secciones, validaciones en tiempo real, control de cambios pendientes, manejo de imágenes y previews, armado de `FormData`, guardado, refresco y emisión del evento global del sitio.
- Lógica conservada en la página: template, imports de componentes y conexión declarativa de pestañas, campos y galerías.
- Organización mediante comentarios: imports preservados y bloque de orquestación en la página; estado de configuración, constantes, computed, multimedia, carga, guardado, ciclo de vida y API pública en el composable.
- Services preservados: `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~500 líneas concentradas en la vista; después ~313 líneas en la página y ~387 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismas claves de settings, mismo orden de envío, mismo `FormData`, mismos booleanos/números, mismos flags `${key}_remove`, mismos eventos globales y mismos endpoints.

### AdminCategoriesPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminCategoriesPage.vue` y `frontend/src/modules/admin/composables/useAdminCategories.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminCategories.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/utils/productSlug.js`, `frontend/src/modules/admin/composables/useAdminPagination.js` y `resolveMediaUrl`; no fue necesario crear `categoryPresentation.js`.
- Lógica extraída: carga de categorías, búsqueda, filtros, paginación, formulario, slug automático/manual, imagen y preview, creación, edición, eliminación, activación, refresco y limpieza de blobs.
- Lógica conservada en la página: template, imports de componentes, wiring de `AdminPagination` y render declarativo de tabla y modal.
- Organización mediante comentarios: imports preservados y bloque de orquestación en la página; estado principal, filtros, formulario, helpers, carga, CRUD, ciclo de vida y API pública en el composable.
- Services preservados: `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~430 líneas concentradas en la vista; después ~275 líneas en la página y ~347 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismos payloads `nombre`, `slug`, `descripcion`, `activo`, `image_file`; mismas reglas de eliminación por `product_count`, mismos filtros y misma paginación.

### AdminCollectionsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminCollectionsPage.vue` y `frontend/src/modules/admin/composables/useAdminCollections.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminCollections.js`.
- Utils reutilizados o creados: se reutilizaron `frontend/src/modules/admin/utils/productSlug.js`, `frontend/src/modules/admin/composables/useAdminPagination.js` y `resolveMediaUrl`; no fue necesario crear `collectionPresentation.js`.
- Lógica extraída: carga de colecciones, búsqueda, filtros, paginación, formulario, slug automático/manual, fecha de lanzamiento, imagen y preview, creación, edición, eliminación, activación, refresco y limpieza de blobs.
- Lógica conservada en la página: template, imports de componentes y render declarativo de tabla, fecha y modal.
- Organización mediante comentarios: imports preservados y bloque de orquestación en la página; estado principal, filtros, formulario, slug, fechas, imagen, carga, CRUD, ciclo de vida y API pública en el composable.
- Services preservados: `catalogHttp` desde `frontend/src/services/http`.
- Líneas aproximadas antes y después: antes ~435 líneas concentradas en la vista; después ~276 líneas en la página y ~351 líneas en el composable.
- Build individual: `npm run build` exitoso.
- Contratos preservados: mismos payloads `nombre`, `slug`, `descripcion`, `activo`, `launch_date`, `image_file`; mismas confirmaciones, mismos filtros, misma paginación y mismos endpoints.
