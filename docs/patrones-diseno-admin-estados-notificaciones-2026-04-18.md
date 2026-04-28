# Patrones de diseño aplicados: Estados y notificaciones admin (2026-04-18)

## Problema
En el panel de administración existían dos inconsistencias operativas:
1. Estados técnicos como `in_review` se mostraban sin traducción consistente en la UI.
2. Los badges de Órdenes/Pagos y la campana de notificaciones dependían de deltas posteriores al primer snapshot, por lo que una orden recién creada podía no reflejarse al entrar al admin.

## Patrón 1: Adapter (traducción de estados de dominio a UI)
- Objetivo: desacoplar los valores técnicos de base de datos del texto visible al usuario.
- Implementación:
  - `frontend/src/modules/admin/utils/orderPresentation.js`
- Qué resuelve:
  - Traduce estados nuevos (`created`, `pending_payment`, `in_review`, `en_revision`) a etiquetas de negocio en español.
  - Evita que tokens con guion bajo se muestren en crudo (fallback humanizado).
  - Alinea clases visuales de badges con estados reales para mantener coherencia visual.

## Patrón 2: Observer + Event Aggregation (polling con consolidación de eventos)
- Objetivo: centralizar el cálculo de notificaciones operativas desde eventos de órdenes sin depender solo de cambios detectados después de la inicialización.
- Implementación:
  - `frontend/src/modules/admin/composables/useAdminNotifications.js`
- Qué resuelve:
  - Genera eventos bootstrap iniciales para órdenes recientes.
  - Agrega eventos de revisión de pagos (`payments`) además de eventos de órdenes (`orders`).
  - Deduplica por `id` y preserva estado de lectura (`read_at`) para mantener coherencia entre campana y badges del aside.
  - Evita marcar como leído en el primer render, y solo marca por cambio real de ruta.

## Patrón 3: Single Source of Truth para estados en filtros/acciones
- Objetivo: que filtros y formularios usen el mismo vocabulario de estados que devuelve backend.
- Implementación:
  - `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
  - `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php`
- Qué resuelve:
  - Añade estados operativos en filtros y acciones (`created`, `pending_payment`, `in_review`, `completed`).
  - Corrige cálculo de pendientes en backend admin para incluir estados de revisión y pago pendiente.

## Resultado esperado
- Estado `in_review` y estados relacionados visibles en español y con badge correcto.
- Notificaciones iniciales coherentes al entrar al admin, con badges de Órdenes/Pagos conectados a la campana.
- Conteos de pendientes alineados con el flujo real de revisión operativa.
