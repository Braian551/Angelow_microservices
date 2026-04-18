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

### 7) Command
- Patrón: Command (Refactoring Guru)
- Problema que resuelve:
  Encapsular acciones operativas de selección masiva (`Todos`, `Limpiar`) y envío de campaña sin mezclar controles visuales con lógica de negocio.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminDiscountSpecificCampaignPage.vue
- Evidencia técnica:
  - Acciones `selectAllFilteredCustomers`, `clearSpecificCustomerSelection` y `submitSpecificCampaign` quedan separadas, con validación en tiempo real antes del envío.
