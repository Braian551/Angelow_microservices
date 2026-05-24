# Patrones de diseno aplicados en sliders y configuracion general

Fecha: 2026-04-05

## Objetivo
Migrar sliders y configuracion general del admin hacia la logica funcional de Angelow legacy, manteniendo arquitectura SPA en microservicios, componentes reutilizables, validacion en tiempo real y consumo centralizado del catalog-service.

## Patron 1: Facade visual para vistas admin
- Problema que resuelve:
  - Evita que sliders y configuracion implementen por separado header, metricas, tablas, estados vacios, modales y zonas de carga de archivos con estilos distintos.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - frontend/src/modules/admin/pages/AdminSettingsPage.vue
  - frontend/src/modules/admin/components/AdminPageHeader.vue
  - frontend/src/modules/admin/components/AdminCard.vue
  - frontend/src/modules/admin/components/AdminStatsGrid.vue
  - frontend/src/modules/admin/components/AdminEmptyState.vue
  - frontend/src/modules/admin/components/AdminTableShimmer.vue
  - frontend/src/modules/admin/components/AdminModal.vue
- Como se usa:
  - Las dos vistas se montan con la misma fachada visual del dashboard y solo cambian los campos, acciones y estructuras de datos.

## Patron 2: Template Method para formularios operativos
- Problema que resuelve:
  - Tanto sliders como configuracion general comparten la misma secuencia de trabajo: cargar, normalizar, validar, abrir editor, guardar, refrescar y notificar resultado.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - frontend/src/modules/admin/pages/AdminSettingsPage.vue
- Como se usa:
  - Cada vista define funciones equivalentes para load, normalize, validate, save y manejo de preview, de modo que el flujo sea consistente con otras pantallas admin del microfrontend.

## Patron 3: Adapter para contratos legacy y microservicios
- Problema que resuelve:
  - La informacion de sliders y settings convive con nombres de columna heredados y nombres nuevos como image/image_url, link/link_url, order_position/sort_order, is_active/active.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - frontend/src/modules/admin/pages/AdminSettingsPage.vue
  - services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
  - services/catalog-service/app/Models/Slider.php
  - services/catalog-service/app/Models/SiteSetting.php
- Como se usa:
  - El backend normaliza la respuesta y el frontend la adapta a una forma estable para la UI, evitando acoplar la vista a variaciones del esquema.

## Patron 4: Metadata-driven form para configuracion general
- Problema que resuelve:
  - La configuracion general no debe quedar hardcodeada porque legacy ya organiza campos por categorias y tipos reutilizables.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminSettingsPage.vue
  - services/catalog-service/app/Support/SiteSettingsCatalog.php
  - services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
- Como se usa:
  - El catalog-service entrega definiciones y valores; el frontend renderiza inputs, textareas, booleanos, colores e imagenes a partir de esa metadata.

## Patron 5: Command para acciones puntuales del carrusel
- Problema que resuelve:
  - Cambiar estado, reordenar y eliminar sliders son acciones con semantica distinta y no conviene esconderlas detras de un unico update ambiguo.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
  - services/catalog-service/routes/api.php
- Como se usa:
  - El frontend dispara comandos explicitos para reorder y status toggle, y el backend responde con endpoints separados para cada operacion.

## Patron 6: Feedback centralizado con alertas y snackbars
- Problema que resuelve:
  - Sustituye confirmaciones nativas e inconsistencias de mensajes por un sistema uniforme para confirmar, advertir y notificar acciones.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - frontend/src/composables/useAlertSystem.js
  - frontend/src/composables/useSnackbarSystem.js
- Como se usa:
  - Eliminacion de sliders usa alerta confirmatoria y todas las operaciones relevantes notifican resultado con snackbar reutilizable.

## Resultado
- Sliders queda alineado con el flujo legacy de estado, orden, modal de edicion y confirmacion de borrado, pero dentro del dashboard SPA.
- Configuracion general deja de ser un formulario estatico y pasa a renderizarse por definiciones reutilizables, igualando la logica informativa del sistema original.
- El catalog-service absorbe la compatibilidad entre columnas legacy y actuales, reduciendo acoplamiento del frontend.