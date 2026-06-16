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
- [Cierre de la refactorización de lógica del módulo administrativo](#cierre-de-la-refactorización-de-lógica-del-módulo-administrativo)
- [Estabilización posterior a la refactorización administrativa](#estabilización-posterior-a-la-refactorización-administrativa)
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
## Refactorización de lógica administrativa — descuentos masivos, campaña específica, administradores y facturas

### Diagnóstico inicial de entorno

- Bloqueo recibido: la refactorización quedó detenida porque `npm` y `npm.cmd` no estaban disponibles en el `PATH` de la sesión PowerShell inicial.
- Resultado del diagnóstico seguro: `Get-Command node`, `Get-Command npm`, `Get-Command npm.cmd`, `where.exe node` y `where.exe npm` no resolvieron en el sandbox; además el acceso a `C:\Program Files\nodejs` requería ejecución fuera del sandbox.
- Ruta real de `node.exe`: `C:\Program Files\nodejs\node.exe`.
- Ruta real de `npm.cmd`: `C:\Program Files\nodejs\npm.cmd`.
- Versiones verificadas: Node `v22.21.0` y npm `10.9.4`.
- Comando exacto usado para todos los builds locales de esta etapa: `& "C:\Program Files\nodejs\npm.cmd" run build`.
- Resultado del build pendiente de la Etapa A: exitoso después de validar la ruta real de Node/NPM y sanear la etapa.

### AdminBulkDiscountsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue` y `frontend/src/modules/admin/composables/useAdminBulkDiscounts.js`.
- Composable creado o completado: `frontend/src/modules/admin/composables/useAdminBulkDiscounts.js`.
- Utils reutilizados o creados: `frontend/src/modules/admin/composables/useAdminPagination.js` y `frontend/src/modules/admin/composables/useAdminDataExport.js`; no fue necesario crear `bulkDiscountPresentation.js`.
- Lógica extraída: carga de descuentos masivos, filtros, búsqueda, paginación, formulario, validaciones, detalle, CRUD, modales, exportación y refresco.
- Lógica conservada: template, imports de componentes, wiring declarativo y estructura visual de la vista.
- Bloques comentados: dependencias, estado principal, filtros y paginación, gestión del formulario, rangos y niveles, tipo y valor, asociaciones, fechas y prioridad, carga, acciones CRUD, modales, exportación, watchers y ciclo de vida, API pública.
- Services preservados: `discountHttp` desde `frontend/src/services/http`; no se modificó `frontend/src/services/discountApi.js`.
- Líneas aproximadas antes y después: página antes ~478 y después ~272; composable ~352.
- Build individual: exitoso con `& "C:\Program Files\nodejs\npm.cmd" run build`.
- Contratos preservados: mismos endpoints, mismos payloads, mismos nombres de campo, misma semántica de rangos y misma exportación.
- Smoke tests o limitaciones: validación segura limitada a build y revisión de referencias; no se ejecutaron altas, ediciones ni bajas reales.

### AdminDiscountSpecificCampaignPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminDiscountSpecificCampaignPage.vue` y `frontend/src/modules/admin/composables/useAdminDiscountSpecificCampaign.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminDiscountSpecificCampaign.js`.
- Utils reutilizados o creados: `frontend/src/modules/admin/composables/useAdminPagination.js`; no fue necesario crear `discountCampaignPresentation.js`.
- Lógica extraída: carga de códigos, carga de destinatarios, búsqueda, paginación, selección individual, selección masiva, validaciones, canales, envío, resumen del resultado y navegación de retorno.
- Lógica conservada: template, componentes visuales y conexión declarativa del flujo.
- Bloques comentados: dependencias, estado principal, formulario de campaña, códigos disponibles, destinatarios, filtros y paginación, canales y contenido, helpers, carga, envío, confirmaciones y navegación, watchers y ciclo de vida, API pública.
- Services preservados: `discountHttp` desde `frontend/src/services/http`; no se modificaron `frontend/src/services/discountApi.js` ni services de notificaciones.
- Líneas aproximadas antes y después: página antes ~533 y después ~302; composable ~323.
- Build individual: exitoso con `& "C:\Program Files\nodejs\npm.cmd" run build`.
- Contratos preservados: mismos `user_ids`, mismo `discount_code_id`, mismos canales, mismos endpoints y mismo payload de envío específico.
- Smoke tests o limitaciones: no se envió una campaña real para evitar notificaciones; la validación segura se limitó a build y revisión estática.

### AdminAdministratorsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminAdministratorsPage.vue` y `frontend/src/modules/admin/composables/useAdminAdministrators.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminAdministrators.js`.
- Utils reutilizados o creados: `frontend/src/modules/admin/composables/useAdminPagination.js`, `frontend/src/composables/useSession.js`, `frontend/src/utils/media.js` y `frontend/src/services/authApi.js`; no fue necesario crear `administratorPresentation.js`.
- Lógica extraída: carga de administradores, paginación, formulario, validaciones, guardado, eliminación, control del administrador actual y actualización de foto de perfil.
- Lógica conservada: template, componentes visuales, render de avatar y bindings del modal.
- Bloques comentados: dependencias, estado principal, filtros y paginación, formulario, contraseña y administrador actual, validaciones, helpers, carga, gestión del formulario y modal, acciones CRUD, estado de foto de perfil, ciclo de vida, API pública.
- Services preservados: `authHttp` desde `frontend/src/services/http` y `updateProfile` desde `frontend/src/services/authApi.js`.
- Líneas aproximadas antes y después: página antes ~341 y después ~179; composable ~304.
- Build individual: exitoso con `& "C:\Program Files\nodejs\npm.cmd" run build`.
- Contratos preservados: mismas autorrestricciones de eliminación, mismo tratamiento de contraseña vacía en edición, mismos endpoints y mismos campos.
- Smoke tests o limitaciones: no se eliminaron administradores reales ni se subieron fotos reales; validación segura limitada a build y revisión del flujo.

### AdminInvoicesPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminInvoicesPage.vue` y `frontend/src/modules/admin/composables/useAdminInvoices.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminInvoices.js`.
- Utils reutilizados o creados: `frontend/src/modules/admin/utils/orderPresentation.js`; no fue necesario crear `invoicePresentation.js`.
- Lógica extraída: carga de facturas, filtros, sincronización con query params, paginación, detalle, descarga de PDF, reenvío, modales, formateo y refresco.
- Lógica conservada: template, imports de componentes, `RouterLink` y bindings del detalle.
- Bloques comentados: dependencias, estado principal, filtros y paginación, selección y detalle, datos fiscales y totales, carga, descarga e impresión, correo y reenvío, modales, watchers y ciclo de vida, API pública.
- Services preservados: `orderHttp` desde `frontend/src/services/http` y `downloadAdminInvoice`/`getAdminInvoices`/`resendAdminInvoice` desde `frontend/src/services/invoiceApi.js`.
- Líneas aproximadas antes y después: página antes ~607 y después ~287; composable ~423.
- Build individual: exitoso con `& "C:\Program Files\nodejs\npm.cmd" run build`.
- Contratos preservados: mismos números de factura, mismas fuentes `source`, mismos nombres de archivo, mismos endpoints y mismos parámetros.
- Smoke tests o limitaciones: no se reenviaron facturas reales por correo; la validación segura quedó limitada a build, revisión estática y verificación HTTP posterior de la SPA.

## Cierre de la refactorización de lógica del módulo administrativo

### AdminProductsPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminProductsPage.vue` y `frontend/src/modules/admin/composables/useAdminProducts.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminProducts.js`.
- Utils reutilizados: `frontend/src/modules/admin/composables/useAdminPagination.js`, `frontend/src/modules/admin/composables/useAdminDataExport.js` y `frontend/src/utils/media.js`.
- Lógica extraída: carga de productos, búsqueda, filtros, paginación, selección, detalle rápido, activación/desactivación, refresco, exportación, zoom y estados de carga/error.
- Lógica conservada en la página: template, `RouterLink`, componentes visuales, wiring declarativo de modales y eventos exclusivamente visuales.
- Comentarios estructurales: agregados en la página y en el composable para estado principal, filtros y paginación, selección, carga, acciones administrativas, exportación, modales, ciclo de vida y API pública.
- Líneas aproximadas antes y después: antes ~895 líneas en la vista; después ~371 líneas en la página y ~694 líneas en el composable.
- Build de etapa: el build local solicitado no pudo ejecutarse porque en esta sesión no existe una instalación local usable de `node`/`npm`; se validó con `docker compose exec -T frontend npm run build`, exitoso.
- Smoke tests y limitaciones: validación segura por build, revisión de imports y entrega SPA en `/admin/productos`; no se activaron ni desactivaron productos reales.
- Contratos preservados: mismos endpoints, payloads, filtros, paginación, exportaciones, rutas de creación/edición y reglas de stock.

### AdminOrderDetailPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue` y `frontend/src/modules/admin/composables/useAdminOrderDetail.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminOrderDetail.js`.
- Utils reutilizados: `frontend/src/modules/admin/utils/orderPresentation.js`, `frontend/src/modules/checkout/utils/checkoutHelpers.js` y `frontend/src/utils/media.js`.
- Lógica extraída: obtención de ID/ruta, carga del pedido, carga de comprobante, carga de direcciones, historial, derivados de envío, formularios de edición, cambio de estado, cambio de estado de pago, modales y refresco.
- Lógica conservada en la página: template, imports de componentes, wrappers internos de `AdminModal` y `AdminPaymentProofModal`, navegación visual y bindings declarativos.
- Comentarios estructurales: agregados en la página y en el composable para ruta e identificación, estado principal, datos derivados, carga, edición, estados del pedido, estados del pago, comprobante, historial, navegación, ciclo de vida y API pública.
- Líneas aproximadas antes y después: antes ~1371 líneas en la vista; después ~649 líneas en la página y ~942 líneas en el composable.
- Build de etapa: el build local solicitado no pudo ejecutarse por ausencia de `node`/`npm` local; se validó con `docker compose exec -T frontend npm run build`, exitoso.
- Smoke tests y limitaciones: validación segura por build y entrega SPA en `/admin/ordenes/1`; no se modificaron estados reales, no se aprobaron/rechazaron comprobantes y no se editó un pedido real.
- Contratos preservados: mismos estados de pedido y pago, mismas transiciones, mismos payloads, misma semántica de `null`, misma integración con facturas y mismo tratamiento de pedido inexistente.

### AdminDashboardPage.vue

- Archivos modificados: `frontend/src/modules/admin/pages/AdminDashboardPage.vue` y `frontend/src/modules/admin/composables/useAdminDashboard.js`.
- Composable creado: `frontend/src/modules/admin/composables/useAdminDashboard.js`.
- Utils reutilizados: `frontend/src/modules/admin/utils/inventoryPresentation.js`, `frontend/src/utils/orderPresentation.js` y `frontend/src/composables/useAppShell.js`.
- Lógica extraída: carga de métricas, órdenes recientes, clientes, alertas de inventario, top productos, actividad, rangos, refresco, navegación y datasets derivados.
- Lógica conservada en la página: `Chart.register`, refs de canvas, destrucción/render de gráficas y configuración visual estática de Chart.js.
- Comentarios estructurales: agregados en la página y en el composable para estado principal, rango y filtros, métricas, datasets, carga, navegación, integración con gráficas, watchers, ciclo de vida y API pública.
- Líneas aproximadas antes y después: antes ~1131 líneas en la vista; después ~477 líneas en la página y ~664 líneas en el composable.
- Build de etapa: el build local solicitado no pudo ejecutarse por ausencia de `node`/`npm` local; se validó con `docker compose exec -T frontend npm run build`, exitoso.
- Smoke tests y limitaciones: validación segura por build y entrega SPA en `/admin`; no se ejecutaron acciones administrativas desde el dashboard.
- Contratos preservados: mismas métricas, fórmulas, datasets, rangos, rutas de navegación y comportamiento de clic en gráficas/tarjetas.

### Auditoría final del módulo administrativo

- Total de páginas admin auditadas: 25.
- Páginas con composable propio: 24. `AdminAdministratorsPage.vue`, `AdminAnnouncementsPage.vue`, `AdminBulkDiscountsPage.vue`, `AdminCategoriesPage.vue`, `AdminCollectionsPage.vue`, `AdminCustomersPage.vue`, `AdminDashboardPage.vue`, `AdminDiscountCodesPage.vue`, `AdminDiscountSpecificCampaignPage.vue`, `AdminInventoryPage.vue`, `AdminInvoicesPage.vue`, `AdminOrderDetailPage.vue`, `AdminOrdersPage.vue`, `AdminPaymentsPage.vue`, `AdminProductFormPage.vue`, `AdminProductsPage.vue`, `AdminQuestionsPage.vue`, `AdminReportsPage.vue`, `AdminReviewsPage.vue`, `AdminSettingsPage.vue`, `AdminShippingMethodsPage.vue`, `AdminShippingRulesPage.vue`, `AdminSizesPage.vue` y `AdminSlidersPage.vue`.
- Páginas que reutilizan un composable o flujo existente: `AdminForgotPasswordPage.vue`, que reutiliza el flujo del módulo auth y por eso no requiere un composable administrativo propio.
- Páginas sin composable y justificación: ninguna fuera de `AdminForgotPasswordPage.vue`.
- Páginas con lógica extensa pendiente: ninguna grande sin justificación arquitectónica.
- Confirmaciones explícitas: `AdminProductFormPage.vue` usa `useAdminProductForm.js`; `AdminForgotPasswordPage.vue` reutiliza el flujo auth; `AdminProductsPage.vue`, `AdminOrderDetailPage.vue` y `AdminDashboardPage.vue` usan `useAdminProducts.js`, `useAdminOrderDetail.js` y `useAdminDashboard.js`.
- Composables huérfanos: no se encontraron. `useAdminPagination.js`, `useAdminDataExport.js`, `useAdminCustomerProfiles.js` y `useAdminNotifications.js` siguen siendo compartidos y están referenciados por páginas o componentes activos.
- Imports rotos o duplicados: no se detectaron roturas; el build del frontend siguió pasando después de las tres etapas y en la validación final.
- CSS dentro de `pages`: no se encontraron bloques `<style>` en `frontend/src/modules/admin/pages`.
- Resultado UTF-8: barrido sin coincidencias de secuencias típicas de mojibake o caracteres de reemplazo en `pages`, `composables`, `utils`, `views` y este documento.
- Estado de comentarios estructurales: las tres nuevas extracciones quedaron organizadas con bloques comentados en español, y las páginas orquestadoras conservan comentarios mínimos de navegación.

### Composables administrativos mayores de 500 líneas

- `frontend/src/modules/admin/composables/useAdminDiscountCodes.js` (~740): concentra carga, filtros, formulario, modal, exportación y reglas de campaña/código; se conserva para no fragmentar un flujo cohesionado. Recomendación futura: extraer helpers puros de ventanas de vigencia y validaciones repetidas.
- `frontend/src/modules/admin/composables/useAdminAnnouncements.js` (~612): concentra CRUD, fechas, previews, imagen, exportación y estados; se conserva por cohesión del formulario. Recomendación futura: separar helpers puros de scheduling y normalización.
- `frontend/src/modules/admin/composables/useAdminInventory.js` (~783): concentra inventario, alertas, ajustes, transferencia, realtime y exportación; se conserva por la fuerte coordinación entre stock y modales. Recomendación futura: aislar helpers puros de cálculo y normalización de filas.
- `frontend/src/modules/admin/composables/useAdminReports.js` (~851): concentra filtros, datasets, exportaciones y varios tabs de informe; se conserva porque comparte mucho estado transversal. Recomendación futura: separar utilidades puras por dominio (`sales`, `products`, `customers`).
- `frontend/src/modules/admin/composables/useAdminCustomers.js` (~505): concentra perfiles, filtros, detalle, pedidos asociados y exportación; se conserva por cohesión de la vista. Recomendación futura: mover helpers de perfil/presentación a utilidades puras.
- `frontend/src/modules/admin/composables/useAdminPayments.js` (~510): concentra filtros, comprobantes, validaciones, revisión y refresco; se conserva para no romper el flujo de aprobación. Recomendación futura: extraer helpers puros de presentación de estados y referencias.
- `frontend/src/modules/admin/composables/useAdminOrders.js` (~736): concentra listado, selección masiva, detalle resumido, estados y exportación; se conserva por coordinación transversal de la pantalla. Recomendación futura: extraer helpers de bulk actions y normalización.
- `frontend/src/modules/admin/composables/useAdminProductForm.js` (~856): concentra formulario completo, multimedia, validaciones, variantes y guardado; se conserva porque sigue siendo el núcleo coherente del flujo de producto. Recomendación futura: revisar únicamente helpers puros de multimedia y variantes, sin romper el formulario.
- `frontend/src/modules/admin/composables/useAdminProducts.js` (~694): concentra catálogo, filtros, quick view, exportación y acciones; se conserva porque la vista rápida comparte mucho estado derivado. Recomendación futura: mover helpers puramente deterministas de normalización de imágenes/variantes a utilidades.
- `frontend/src/modules/admin/composables/useAdminOrderDetail.js` (~942): concentra pedido, direcciones, comprobante, historial y formularios de estado/pago; se conserva porque todo el detalle gira sobre una sola orden. Recomendación futura: extraer únicamente funciones puras de historial y matching de direcciones.
- `frontend/src/modules/admin/composables/useAdminDashboard.js` (~664): concentra métricas, navegación, rangos, datasets y actividad; se conserva porque el dashboard sigue siendo una sola orquestación de datos. Recomendación futura: mover helpers puros de agregación/normalización de tarjetas y actividad.
- `frontend/src/modules/admin/composables/useAdminNotifications.js` (~843): composable compartido de cabecera/sidebar, no huérfano; se conserva por el polling y la lógica transversal del módulo. Recomendación futura: separar utilidades puras de formateo y snapshots.
- `frontend/src/modules/admin/composables/useAdminDataExport.js` (~1004): infraestructura compartida de Excel/PDF para todo admin, no huérfana y fuera del alcance de esta etapa. Recomendación futura: dividir por adaptadores de Excel/PDF y helpers de branding sin cambiar contratos.

### Validaciones operativas finales

- Build local solicitado: no fue posible ejecutarlo en esta sesión porque `C:\Program Files\nodejs\npm.cmd` no existe aquí y tampoco hay `node`/`npm` en PATH; la carpeta `C:\laragon\bin\nodejs` está vacía.
- Docker rebuild: `docker compose up -d --build frontend`, exitoso.
- Build en contenedor: `docker compose exec -T frontend npm run build`, exitoso.
- Estado de contenedores: `docker compose ps` confirmó `frontend` en `Up` y exponiendo `0.0.0.0:5173->5173`.
- Logs de frontend: arranque correcto de Vite en `http://localhost:5173/`; warnings reportados solamente de vulnerabilidades npm y actualización mayor de npm.
- Rutas HTTP 200 verificadas: `/admin`, `/admin/productos`, `/admin/productos/nuevo`, `/admin/ordenes`, `/admin/ordenes/1` y `/admin/informes` respondieron `HTTP/1.1 200 OK`.
- Alcance de la verificación HTTP: la respuesta 200 valida la entrega de la SPA, no la existencia de un pedido seguro ni la validez funcional del registro `/admin/ordenes/1`.
- Warnings conocidos observados: `images/pattern.png` no resuelto en build, chunks mayores de 500 kB, vulnerabilidades npm, aviso de actualización mayor de npm y fallo del Browser integrado con `spawn setup refresh`.
- Restricciones preservadas: no se usó Git, no se tocó base de datos, no se modificó backend, no se modificaron services, no se alteraron payloads, no se cambió CSS fuera de `views`, no se tocaron dependencias y no se trabajó una cuarta página.
- Confirmación de cierre: esta etapa deja cerrada la extracción principal de lógica del módulo administrativo y no quedan páginas administrativas grandes sin justificación explícita.

## Estabilización posterior a la refactorización administrativa

### Diagnóstico del entorno y scripts disponibles

- Ruta de trabajo confirmada: `C:\laragon\www\Angelow_microservices`.
- Skill obligatoria leída y aplicada: `C:\laragon\www\Angelow_microservices\.agents\skills\skill\SKILL.md`.
- Diagnóstico local de Node/npm ejecutado con: `Get-Command node -ErrorAction SilentlyContinue`, `Get-Command npm -ErrorAction SilentlyContinue`, `Get-Command npm.cmd -ErrorAction SilentlyContinue`, `where.exe node`, `where.exe npm`, `Test-Path "C:\Program Files\nodejs\node.exe"` y `Test-Path "C:\Program Files\nodejs\npm.cmd"`.
- Resultado del diagnóstico: no hay `node`, `npm` ni `npm.cmd` disponibles en PATH dentro de esta sesión; `where.exe node` y `where.exe npm` no resolvieron binarios; la verificación de `C:\Program Files\nodejs\*.exe/cmd` quedó denegada por permisos del entorno, así que la etapa se cerró usando Docker.
- Scripts reales inspeccionados en `frontend/package.json`: `dev`, `build` y `preview`.
- Scripts no disponibles en esta etapa: no existen `lint`, `test` ni `typecheck` en `frontend/package.json`, por lo que no se reportan como ejecutados.

### Auditoría página-composable

- Páginas administrativas revisadas: 25.
- Páginas con composable propio auditadas: `AdminAdministratorsPage.vue` → `useAdminAdministrators.js`, `AdminAnnouncementsPage.vue` → `useAdminAnnouncements.js`, `AdminBulkDiscountsPage.vue` → `useAdminBulkDiscounts.js`, `AdminCategoriesPage.vue` → `useAdminCategories.js`, `AdminCollectionsPage.vue` → `useAdminCollections.js`, `AdminCustomersPage.vue` → `useAdminCustomers.js`, `AdminDashboardPage.vue` → `useAdminDashboard.js`, `AdminDiscountCodesPage.vue` → `useAdminDiscountCodes.js`, `AdminDiscountSpecificCampaignPage.vue` → `useAdminDiscountSpecificCampaign.js`, `AdminInventoryPage.vue` → `useAdminInventory.js`, `AdminInvoicesPage.vue` → `useAdminInvoices.js`, `AdminOrderDetailPage.vue` → `useAdminOrderDetail.js`, `AdminOrdersPage.vue` → `useAdminOrders.js`, `AdminPaymentsPage.vue` → `useAdminPayments.js`, `AdminProductFormPage.vue` → `useAdminProductForm.js`, `AdminProductsPage.vue` → `useAdminProducts.js`, `AdminQuestionsPage.vue` → `useAdminQuestions.js`, `AdminReportsPage.vue` → `useAdminReports.js`, `AdminReviewsPage.vue` → `useAdminReviews.js`, `AdminSettingsPage.vue` → `useAdminSettings.js`, `AdminShippingMethodsPage.vue` → `useAdminShippingMethods.js`, `AdminShippingRulesPage.vue` → `useAdminShippingRules.js`, `AdminSizesPage.vue` → `useAdminSizes.js` y `AdminSlidersPage.vue` → `useAdminSliders.js`.
- Página excepcional sin composable administrativo propio: `AdminForgotPasswordPage.vue`, que reutiliza el flujo del módulo auth según el alcance aprobado.
- Verificación estructural positiva en las 24 parejas auditadas: import correcto y una sola invocación del composable por página.
- Estado duplicado en páginas: no se detectó duplicación de estado de negocio entre página y composable; la excepción intencional es `AdminDashboardPage.vue`, que conserva refs locales de Chart.js y destrucción de instancias como capa visual propia.
- Funciones duplicadas de negocio entre página y composable: no se detectaron duplicaciones funcionales objetivas.
- Problemas de reactividad y destructuración incorrecta: no se detectaron pérdidas de reactividad por destructuración incorrecta en la integración página-composable revisada.
- Errores objetivos encontrados y corregidos:
  - `frontend/src/modules/admin/composables/useAdminSettings.js`: faltaba exponer `openImagePicker` en la API pública mientras `AdminSettingsPage.vue` lo consumía. Se corrigió el retorno del composable para restaurar el contrato de la página.
  - `frontend/src/modules/admin/composables/useAdminCustomers.js`, `useAdminOrders.js` y `useAdminProducts.js`: existían timers de búsqueda diferida (`setTimeout`) sin limpieza al desmontar. Se agregó limpieza con `onBeforeUnmount` para evitar callbacks tardíos después de salir de la vista.
- Retornos públicos innecesarios detectados como deuda técnica de mantenibilidad, no como error funcional directo:
  - `useAdminAdministrators.js`: `loadAdmins`.
  - `useAdminAnnouncements.js`: `announcements`, `isTopBarType`, `previewBannerStyle`, `previewBarStyle`.
  - `useAdminBulkDiscounts.js`: `rules`.
  - `useAdminCategories.js`: `categories`, `loadCategories`.
  - `useAdminCollections.js`: `collections`.
  - `useAdminDiscountCodes.js`: `loadCodes`.
  - `useAdminDiscountSpecificCampaign.js`: `loadCampaignCustomers`, `loadCodes`.
  - `useAdminInventory.js`: `groupedProducts`.
  - `useAdminOrderDetail.js`: `loadOrder`, `orderId`.
  - `useAdminProducts.js`: `products`.
  - `useAdminQuestions.js`: `answeredCount`, `pendingCount`.
  - `useAdminSizes.js`: `sizes`.
  - `useAdminSliders.js`: `processingOrder`.
- Watchers, listeners y timers revisados:
  - `useAdminAnnouncements.js`: `onMounted` y `onBeforeUnmount` presentes para carga inicial y limpieza de preview blob.
  - `useAdminInventory.js`: watcher sobre `route.fullPath`, timer de sincronización realtime con limpieza en `onUnmounted`.
  - `useAdminReports.js`: watchers de ruta y filtros con destrucción explícita de gráficas en `onBeforeUnmount`.
  - `useAdminNotifications.js`: `setInterval` global con limpieza por suscripción compartida.
  - `useAdminProductForm.js`: watchers de slug/SKU sin timers ni listeners extra.
  - `useAdminCustomers.js`, `useAdminOrders.js` y `useAdminProducts.js`: cleanup de debounce agregado en esta etapa.
- Contratos API, payloads, rutas, template y CSS: preservados; no se cambiaron endpoints, payloads ni rutas públicas.

### Revisión especial de composables grandes

- `useAdminDataExport.js` (~1004 líneas): clasificación `grande pero justificable`.
  - Responsabilidades: exportación Excel/PDF compartida, branding, tablas, imágenes y formatos.
  - Dependencias y servicios: `exceljs`, `jspdf`, `jspdf-autotable`, `useAppShell`, `media`.
  - Watchers/listeners/timers: no usa watchers ni timers.
  - Efectos secundarios: genera archivos y consume branding global.
  - Funciones puras internas: helpers de color, celdas, layout, serialización y fallback visual.
  - Responsabilidades separables futuras: adaptador Excel, adaptador PDF y helpers de branding.
  - Riesgo de dividirlo: medio, por su uso transversal en todo admin.
  - Recomendación: separar solo por adaptadores compartidos, no por pantalla.
- `useAdminNotifications.js` (~843 líneas): clasificación `grande pero justificable`.
  - Responsabilidades: polling de órdenes/inventario, snapshots, notificaciones visibles, dismissals y contadores compartidos.
  - Dependencias y servicios: `catalogHttp`, `notificationHttp`, `orderHttp`, `inventoryPresentation`.
  - Watchers/listeners/timers: `setInterval` global cada 20 s; limpieza por contador de suscriptores.
  - Efectos secundarios: lecturas periódicas y marcado de lectura por ruta.
  - Funciones puras internas: normalización de estados, construcción de eventos y snapshots.
  - Responsabilidades separables futuras: helpers de snapshot y formatter de mensajes.
  - Riesgo de dividirlo: medio-alto, porque lo comparten header y sidebar.
  - Recomendación: extraer utilidades puras, mantener el polling centralizado.
- `useAdminProductForm.js` (~856 líneas): clasificación `grande pero justificable`.
  - Responsabilidades: formulario de producto, variantes, multimedia, validaciones, catálogos y guardado.
  - Dependencias y servicios: `useRoute`, `useRouter`, `catalogHttp`, `media`, `numericValidation`, `productFormPayload`, `productSlug`, `productSku`.
  - Watchers/listeners/timers: watchers de slug y SKU; sin timers ni listeners globales.
  - Efectos secundarios: carga de catálogos, lectura/escritura de producto y navegación.
  - Funciones puras internas: normalización de slug/SKU, armado de payloads y derivaciones de variantes.
  - Responsabilidades separables futuras: helpers de multimedia y derivación de variantes.
  - Riesgo de dividirlo: alto, por el acoplamiento entre tabs, variantes y payload final.
  - Recomendación: mantenerlo cohesionado; extraer solo helpers puros si reaparece deuda.
- `useAdminReports.js` (~851 líneas): clasificación `candidato futuro a extracción de utils`.
  - Responsabilidades: filtros multi-tab, datasets, fetchs de ventas/productos/clientes y render de gráficas.
  - Dependencias y servicios: `chart.js`, `useRoute`, `useRouter`, `authHttp`, `catalogHttp`, `orderHttp`, `useAdminDataExport`, `useAdminPagination`.
  - Watchers/listeners/timers: watchers de `route.path` y filtros; destrucción de charts en `onBeforeUnmount`.
  - Efectos secundarios: lecturas API y creación/destrucción de instancias Chart.js.
  - Funciones puras internas: agregaciones, tablas derivadas, armado de datasets y labels.
  - Responsabilidades separables futuras: utilidades de dominio `sales`, `products` y `customers`.
  - Riesgo de dividirlo: medio, porque mezcla fetch y transformación.
  - Recomendación: extraer utilidades puras primero, no fragmentar el composable por vista todavía.
- `useAdminInventory.js` (~783 líneas): clasificación `grande pero justificable`.
  - Responsabilidades: inventario agrupado, detalle, historial, ajustes, transferencias y sincronización realtime.
  - Dependencias y servicios: `useRoute`, `useRouter`, `useStockRealtime`, `catalogHttp`, `media`, `numericValidation`, `inventoryPresentation`, `useAdminDataExport`, `useAdminPagination`.
  - Watchers/listeners/timers: watcher de ruta, timeout de sincronización realtime con limpieza en `onUnmounted`.
  - Efectos secundarios: fetch de inventario/historial, ajustes de stock y sincronización visual.
  - Funciones puras internas: agrupación, normalización, labels de variantes e historial.
  - Responsabilidades separables futuras: utilidades puras de agregación y normalización.
  - Riesgo de dividirlo: medio-alto, por la interacción entre modales, ruta y realtime.
  - Recomendación: mantenerlo unido y mover solo funciones puras si hace falta.
- `useAdminDiscountCodes.js` (~740 líneas): clasificación `candidato futuro a extracción de utils`.
  - Responsabilidades: listado, filtros, formulario, campañas masivas/específicas y exportación.
  - Dependencias y servicios: `useRouter`, `discountHttp`, `useAdminDataExport`, `useAdminPagination`.
  - Watchers/listeners/timers: sin watchers relevantes; carga inicial con `onMounted`.
  - Efectos secundarios: fetch, creación/edición/eliminación y envío de campañas.
  - Funciones puras internas: ventanas de vigencia, etiquetas, payloads auxiliares y validaciones repetidas.
  - Responsabilidades separables futuras: helpers de fechas/validaciones y armadores de payload.
  - Riesgo de dividirlo: medio.
  - Recomendación: extraer utilidades puras; no dividir por subcomposable aún.
- `useAdminOrders.js` (~736 líneas): clasificación `candidato futuro a subcomposable`.
  - Responsabilidades: bandeja, filtros, selección masiva, detalle resumido, cambio de estado, pago y exportación.
  - Dependencias y servicios: `orderHttp`, `useAdminDataExport`, `useAdminPagination`, `orderPresentation`.
  - Watchers/listeners/timers: debounce por `setTimeout`, ahora con limpieza en `onBeforeUnmount`.
  - Efectos secundarios: lecturas API y cambios de estado/pago/desactivación.
  - Funciones puras internas: normalización de orden, keys de selección y formatos.
  - Responsabilidades separables futuras: acciones masivas y helpers de selección/estado.
  - Riesgo de dividirlo: medio, porque comparte bastante estado transversal.
  - Recomendación: si crece otra vez, separar acciones masivas y detalle resumido.
- `useAdminAnnouncements.js` (~612 líneas): clasificación `candidato futuro a extracción de utils`.
  - Responsabilidades: CRUD, filtros, scheduling, preview, colores, links y exportación.
  - Dependencias y servicios: `notificationHttp`, `catalogHttp`, `media`, `storeLinkOptions`, `useAdminDataExport`, `useAdminPagination`.
  - Watchers/listeners/timers: `onMounted` y `onBeforeUnmount` para previews blob; sin timers de polling.
  - Efectos secundarios: carga de colores/anuncios y creación/edición/eliminación.
  - Funciones puras internas: labels, clases, fechas, truncado y preview styles.
  - Responsabilidades separables futuras: helpers de scheduling y presentación.
  - Riesgo de dividirlo: medio.
  - Recomendación: extraer utilidades puras primero.
- `useAdminCustomers.js` (~505 líneas): clasificación `candidato futuro a extracción de utils`.
  - Responsabilidades: clientes, perfiles, pedidos asociados, filtros, exportación y foco por query params.
  - Dependencias y servicios: `useRoute`, `authHttp`, `orderHttp`, `useAdminCustomerProfiles`, `useAdminDataExport`, `useAdminPagination`.
  - Watchers/listeners/timers: watcher de `route.fullPath`; debounce con cleanup agregado.
  - Efectos secundarios: fetch de clientes/pedidos y bloqueo/desbloqueo.
  - Funciones puras internas: normalización de cliente/pedido, segmentación y métricas.
  - Responsabilidades separables futuras: helpers de métricas/perfiles y normalización.
  - Riesgo de dividirlo: medio-bajo.
  - Recomendación: extraer utilidades puras antes de pensar en subcomposable.
- `useAdminPayments.js` (~510 líneas): clasificación `candidato futuro a extracción de utils`.
  - Responsabilidades: bandeja de pagos, filtros, comprobantes, revisión y sincronización con órdenes.
  - Dependencias y servicios: `orderHttp`, `paymentHttp`, `paymentApi`, `useAdminPagination`, `orderPresentation`.
  - Watchers/listeners/timers: carga inicial en `onMounted`; sin timers persistentes.
  - Efectos secundarios: revisión de pagos y sincronización de estado de pago en órdenes.
  - Funciones puras internas: presentación de estados, filtros y mensajes de error.
  - Responsabilidades separables futuras: helpers de presentación y rollback.
  - Riesgo de dividirlo: medio.
  - Recomendación: mantener el flujo junto; mover helpers de presentación si reaparece deuda.
- `useAdminProducts.js` (~694 líneas): clasificación `candidato futuro a subcomposable`.
  - Responsabilidades: catálogo, filtros, selección, quick view, zoom, exportación y activación/desactivación.
  - Dependencias y servicios: `catalogHttp`, `media`, `useAdminDataExport`, `useAdminPagination`.
  - Watchers/listeners/timers: debounce con cleanup agregado; carga inicial en `onMounted`.
  - Efectos secundarios: fetch de productos/categorías, quick view y cambio de estado.
  - Funciones puras internas: normalización de producto/variantes/imágenes, labels y fallback visual.
  - Responsabilidades separables futuras: quick view/media helpers o subcomposable visual.
  - Riesgo de dividirlo: medio-alto, por el acoplamiento entre quick view, imágenes y filtros.
  - Recomendación: no dividir ahora; si crece, aislar quick view como subcomposable.
- `useAdminOrderDetail.js` (~942 líneas): clasificación `grande pero justificable`.
  - Responsabilidades: carga de pedido, direcciones, comprobante, historial, edición, pago, estados y facturas.
  - Dependencias y servicios: `useRoute`, `catalogHttp`, `orderHttp`, `paymentHttp`, `shippingHttp`, `media`, `orderPresentation`.
  - Watchers/listeners/timers: carga inicial con `onMounted`; sin timers globales.
  - Efectos secundarios: lecturas y cambios de orden/pago; consulta de comprobante y direcciones.
  - Funciones puras internas: matching de direcciones, labels de historial y normalizaciones.
  - Responsabilidades separables futuras: helpers de historial/direcciones y facturación.
  - Riesgo de dividirlo: alto, por el acoplamiento a un único pedido y sus modales.
  - Recomendación: mantenerlo intacto salvo extracción de funciones puras.
- `useAdminDashboard.js` (~664 líneas): clasificación `grande pero justificable`.
  - Responsabilidades: métricas, datasets, órdenes recientes, clientes, inventario, top productos y actividad.
  - Dependencias y servicios: `useRouter`, `authHttp`, `catalogHttp`, `orderHttp`, `media`, `orderPresentation`, `useAppShell`, `inventoryPresentation`.
  - Watchers/listeners/timers: watchers de `salesChartRange` y `statusChartRange`; sin listeners globales.
  - Efectos secundarios: carga de métricas y navegación a pantallas administrativas.
  - Funciones puras internas: agregaciones, porcentajes, actividad, labels y rutas derivadas.
  - Responsabilidades separables futuras: utilidades puras de agregación y rutas.
  - Riesgo de dividirlo: medio, porque la página conserva la capa de Chart.js y el composable la de datos.
  - Recomendación: mantener separación actual; no duplicar con `useAdminReports.js`.

### Validaciones estáticas, smoke tests y limitaciones

- Build local: no ejecutable en esta sesión por ausencia de binarios locales utilizables.
- Build en Docker ejecutado y exitoso con `docker compose exec -T frontend npm run build`.
- Reconstrucción final ejecutada por haber cambios de código: `docker compose up -d --build frontend`.
- Estado final validado con `docker compose ps frontend`: contenedor `frontend` en `Up` y exponiendo `5173`.
- Logs finales validados con `docker compose logs --tail=150 frontend`: arranque correcto de Vite, sin error fatal de inicio.
- Smoke tests seguros por HTTP: `200 OK` en `/admin`, `/admin/productos`, `/admin/productos/nuevo`, `/admin/ordenes`, `/admin/ordenes/1`, `/admin/pagos`, `/admin/inventario`, `/admin/informes`, `/admin/clientes`, `/admin/descuentos/codigos`, `/admin/envios/metodos`, `/admin/facturas` y `/admin/configuracion`.
- Alcance real del smoke test: valida entrega SPA y resolución de rutas, no autenticación administrativa, render pos-login ni mutaciones seguras de datos.
- Navegación/interacción manual no completada con browser integrado: el navegador `iab` no estuvo disponible en esta sesión (`Browser is not available: iab`), por lo que no fue posible certificar apertura/cierre de modales, quick views, tabs, loaders o botones deshabilitados desde una sesión visual real.
- Pruebas no ejecutadas por restricción funcional y de seguridad: cambios de estado de pedidos o pagos, aprobaciones, rechazos, bloqueos, eliminaciones, creación de datos, campañas reales, cambios de inventario y cualquier acción con efecto persistente.
- UTF-8: barrido sin coincidencias de secuencias típicas de mojibake o caracteres de reemplazo en `frontend/src/modules/admin/pages`, `composables`, `components`, `utils`, `views` y este documento.
- Warnings conocidos reportados y no corregidos en esta etapa:
  - `images/pattern.png` no resuelto en build.
  - Chunks mayores de 500 kB.
  - Vulnerabilidades npm.
  - Aviso de actualización mayor de npm.
  - Browser integrado no disponible en esta sesión.

### Confirmación de alcance y restricciones

- No se usó Git ni se ejecutaron comandos Git.
- No se tocó base de datos ni se ejecutaron migrations, seeders, resets, truncates, deletes ni updates.
- No se modificó backend, services, endpoints, payloads ni rutas.
- No se instalaron paquetes ni se tocaron `package.json` o `package-lock.json`.
- No se creó otra extracción masiva ni nuevos composables por página.
- No se realizaron acciones destructivas ni cambios reales de estado operativo.
- Esta etapa quedó cerrada como estabilización posterior, no como nueva refactorización.
