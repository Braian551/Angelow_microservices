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

## Decisiones de arquitectura

- La página mantiene `RouterLink`, encabezado, tarjeta principal y coordinación de componentes, pero no contiene la lógica extensa del formulario.
- El CSS se movió a `frontend/src/modules/admin/views/AdminProductFormPage.css` y se importa desde la página para conservar la convención de estilos por vista.
- Corrección de regresión: el CSS externo quedó encapsulado bajo `.admin-product-form-page`, y el modal de variantes incluye la misma raíz dentro del contenido teletransportado por `AdminModal`, para conservar el aislamiento que antes aportaba `<style scoped>`.
- Etapa `AdminProductsPage.vue`: se movieron los estilos locales de productos a `frontend/src/modules/admin/views/AdminProductsPage.css`, encapsulados bajo `.admin-products-page`; los modales de vista rápida y zoom agregan wrapper interno con la misma raíz por el `Teleport` de `AdminModal`.
- No se cambiaron endpoints, payloads, `FormData`, rutas públicas ni nombres de campos enviados al backend.
- La separación se hizo solo para el formulario de producto como primera etapa segura; las demás vistas grandes quedan pendientes para una etapa posterior validada.

## Validaciones

- `npm run build` en `frontend`: exitoso.
- `docker compose exec -T frontend npm run build`: exitoso después de la corrección de aislamiento CSS.
- `npm run build` después de extraer CSS de `AdminProductsPage.vue`: exitoso.
- Pendiente por bloqueo de herramienta local: validación visual con consola del navegador integrado; el kernel falló por `windows sandbox failed: spawn setup refresh`.

## Documentos relacionados

- [Índice de patrones](../README.md)
- [Patrones de productos admin](patrones-diseno-admin-productos-2026-04-03.md)
