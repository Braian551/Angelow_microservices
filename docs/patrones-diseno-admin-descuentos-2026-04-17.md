# Patrones de diseño aplicados - Admin Descuentos (2026-04-17)

## Contexto
Se corrigió la desincronización de anuncios en home (arriba/abajo) y se implementó el flujo de campañas de códigos de descuento con envío masivo y envío a usuarios específicos, incluyendo notificación interna y correo con PDF adjunto.

## 1) Template Method
- Patrón: Template Method (Refactoring Guru)
- Problema que resuelve:
  Estandarizar la secuencia de campaña para no duplicar pasos en envío masivo y específico: validación de canales, resolución de código, carga de destinatarios, envío por canal y resumen final.
- Aplicado en:
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
- Evidencia técnica:
  - El método dispatchCampaign centraliza el flujo común y los endpoints sendMassCampaign / sendSpecificCampaign reutilizan esa secuencia.

## 2) Strategy
- Patrón: Strategy (Refactoring Guru)
- Problema que resuelve:
  Permitir variación de canales de comunicación (notificación, correo o ambos) sin condicionar la vista ni duplicar endpoints por combinación.
- Aplicado en:
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
  - frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue
- Evidencia técnica:
  - El backend ejecuta estrategias de canal con sendCampaignNotification y sendCampaignEmail.
  - El frontend permite activar/desactivar cada canal en tiempo real para ambos modos de campaña.

## 3) Adapter
- Patrón: Adapter (Refactoring Guru)
- Problema que resuelve:
  Adaptar fuentes de datos heterogéneas durante migración (legacy/distribuida) a un contrato estable para anuncios y clientes de campaña.
- Aplicado en:
  - services/catalog-service/app/Repositories/QueryBuilderSiteRepository.php
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
- Evidencia técnica:
  - QueryBuilderSiteRepository prioriza legacy para announcements y mantiene la misma salida para top_bar/promo_banner.
  - AdminDiscountController normaliza usuarios/códigos desde distintas conexiones con fallback sin cambiar el JSON que consume frontend.

## 4) Reuse Component / Composition
- Patrón: Composición sobre duplicación (Refactoring Guru)
- Problema que resuelve:
  Evitar implementaciones ad hoc de modales, grillas y feedback para campañas en admin.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue
- Evidencia técnica:
  - La vista reutiliza AdminPageHeader, AdminModal, AdminFilterCard, AdminPagination y el sistema global de snackbar/alert para los nuevos flujos.

## 5) Guard Clause (Fail-Fast) sobre estrategia de correo
- Patrón: Fail-Fast con guard clause (aplicación operativa sobre Strategy de mailer)
- Problema que resuelve:
  Evitar falsos positivos de envío cuando el mailer está en modo no entregable (por ejemplo log), cortando el flujo antes de iterar destinatarios y devolviendo una respuesta clara para configuración.
- Aplicado en:
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
  - docker-compose.yml
  - services/discount-service/.env.example
- Evidencia técnica:
  - Se agrega emailDeliveryConfigured para validar el mailer activo antes de iniciar la campaña por correo.
  - sendCampaignEmail y sendCampaignNotification registran advertencias estructuradas en log cuando fallan, facilitando diagnóstico sin romper el flujo general de campaña.

## Archivos impactados por esta implementación
- services/catalog-service/app/Repositories/QueryBuilderSiteRepository.php
- services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
- services/discount-service/app/Support/DiscountPdfAttachmentHelper.php
- services/discount-service/routes/api.php
- services/discount-service/config/services.php
- services/discount-service/.env.example
- frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue

## Extensión 2026-04-17: campaña específica en vista dedicada (sin modal)

### 6) Facade + Composition
- Patrón: Facade / Composition (Refactoring Guru)
- Problema que resuelve:
  El modal de campaña específica concentraba demasiada información y degradaba legibilidad en desktop/móvil. Se necesitaba un flujo completo tipo página, manteniendo componentes compartidos del dashboard admin.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminDiscountSpecificCampaignPage.vue
  - frontend/src/router/index.js
  - frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue
- Evidencia técnica:
  - Se creó la ruta SPA `admin-discount-codes-specific-campaign` y un botón de navegación desde códigos.
  - La nueva vista reutiliza `AdminPageHeader`, `AdminCard`, `AdminFilterCard`, `AdminResultsBar`, `AdminInfoTooltip` y `AdminShimmer` para evitar implementación ad hoc.

## Mejora UX 2026-05-10: rediseño de layout campaña a usuarios específicos

### 7) Presentación por pasos (Step UI) + Extracción de barra de acción
- Patrón: Composite + SRP (Refactoring Guru)
- Problema que resuelve:
  La página `AdminDiscountSpecificCampaignPage.vue` mostraba:
  - Una tarjeta de preview del código redundante con el selector (duplicaba información).
  - Dos tarjetas de canal de envío horizontales voluminosas que ocupaban demasiado espacio vertical.
  - La barra de selección de destinatarios incrustada dentro del slot de `AdminFilterCard`, lo que generaba un bloque visual confuso y difícil de leer.
- Cambios aplicados:
  - Se eliminó la tarjeta `campaign-code-preview` y se reemplazó por un resumen compacto `campaign-code-summary` (pill + metadatos en línea).
  - Las tarjetas de canal pasaron de diseño horizontal (`flex align-items: center`) a diseño vertical compacto en grid de 2 columnas (`campaign-channels-grid`).
  - Se añadieron indicadores de paso numerados (`specific-campaign-step__num`) para dar jerarquía visual clara a la configuración.
  - La barra de "Selección de destinatarios" se extrajo del slot de `AdminFilterCard` a un `campaign-recipients-toolbar` dedicado entre la búsqueda y la lista.
  - Se eliminó el gradiente CSS del avatar de usuario (regla 22 SKILL).
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminDiscountSpecificCampaignPage.vue

### 7) Command
- Patrón: Command (Refactoring Guru)
- Problema que resuelve:
  Encapsular acciones operativas de selección masiva (`Todos`, `Limpiar`) y envío de campaña sin mezclar controles visuales con lógica de negocio.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminDiscountSpecificCampaignPage.vue
- Evidencia técnica:
  - Acciones `selectAllFilteredCustomers`, `clearSpecificCustomerSelection` y `submitSpecificCampaign` quedan separadas, con validación en tiempo real antes del envío.

## Extensión 2026-05-10: prioridad visual del snackbar y código automático

### 8) State
- Patrón: State (Refactoring Guru)
- Problema que resuelve:
  El formulario de códigos necesitaba alternar entre captura manual y generación automática sin duplicar campos, manteniendo la validación en tiempo real y la misma experiencia dentro del modal.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue
- Evidencia técnica:
  - El estado reactivo `autoGenerateCode` gobierna el modo del campo, el texto auxiliar, el bloqueo temporal del input y la regeneración del código sugerido desde el mismo flujo.

### 9) Composition
- Patrón: Composition (Refactoring Guru)
- Problema que resuelve:
  Mantener un único sistema global de feedback visible por encima de los modales del admin, evitando reimplementar notificaciones locales en la vista de descuentos.
- Aplicado en:
  - frontend/src/components/ui/UserSnackbarSystem.css
- Evidencia técnica:
  - El snackbar global conserva una sola capa reutilizable y ahora se renderiza por encima del `AdminModal`, por lo que los mensajes operativos siguen visibles aun con overlays activos.

## Extensión 2026-05-10: prevalidación de destinatarios en campaña masiva

### 10) Guard Clause
- Patrón: Guard Clause / Fail-Fast (Refactoring Guru)
- Problema que resuelve:
  El envío masivo permitía avanzar hasta un `422` cuando no había clientes elegibles, generando una mala experiencia para el admin aunque la regla de negocio del backend era correcta.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
- Evidencia técnica:
  - El modal ahora consulta destinatarios antes de habilitar el botón y muestra el estado de disponibilidad dentro de la propia interfaz.
  - El backend mantiene la validación de negocio en `dispatchCampaign`, mientras el frontend evita invocarla cuando la lista está vacía.

## Extensión 2026-05-10: clientes de campaña desde el servicio dueño del dominio

### 11) Adapter
- Patrón: Adapter (Refactoring Guru)
- Problema que resuelve:
  El microservicio de descuentos intentaba detectar clientes desde tablas locales o legacy que podían no existir, quedar desactualizadas o no reflejar el estado real de bloqueo del servicio de autenticación, por eso el envío masivo se quedaba sin destinatarios aunque el admin sí tuviera clientes registrados.
- Aplicado en:
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
- Evidencia técnica:
  - `AdminDiscountController` ahora consulta primero `auth-service` con el mismo token de admin ya validado por middleware y adapta la respuesta a un contrato uniforme de campaña.
  - Si `auth-service` no está disponible, el controlador conserva el fallback legacy/local para no romper el flujo durante la migración.
