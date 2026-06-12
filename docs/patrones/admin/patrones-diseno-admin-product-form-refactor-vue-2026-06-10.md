# Patrón aplicado: formulario de producto como orquestador Vue

<!-- indice:auto:start -->
## Índice

- [Objetivo](#objetivo)
- [Archivos intervenidos](#archivos-intervenidos)
- [Patrones aplicados](#patrones-aplicados)
- [Decisiones de arquitectura](#decisiones-de-arquitectura)
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
- No se cambiaron endpoints, payloads, `FormData`, rutas públicas ni nombres de campos enviados al backend.
- La separación se hizo solo para el formulario de producto como primera etapa segura; las demás vistas grandes quedan pendientes para una etapa posterior validada.

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

- Pendiente por bloqueo de herramienta local: validación visual con consola del navegador integrado; el kernel falló por `windows sandbox failed: spawn setup refresh`.

## Documentos relacionados

- [Índice de patrones](../README.md)
- [Patrones de productos admin](patrones-diseno-admin-productos-2026-04-03.md)
