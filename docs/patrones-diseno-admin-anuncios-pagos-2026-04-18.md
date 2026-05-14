# Patrones de diseño aplicados (Admin anuncios y pagos)

Fecha: 2026-04-18

## Contexto del problema
- El módulo de anuncios en admin devolvía error de servidor cuando la tabla `announcements` no existía en la base del `notification-service`.
- El modal de configuración de cuenta bancaria en admin no mostraba bancos porque la tabla `colombian_banks` estaba vacía en `payment-service`.

## Patrón 1: Strategy (selección de fuente de datos)
- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: elegir en tiempo de ejecución la mejor fuente de datos disponible para bancos sin romper el endpoint.
- Aplicación:
  - `services/payment-service/app/Http/Controllers/PaymentController.php`
  - Método `banks()` con secuencia de resolución:
    1. Base distribuida local.
    2. Hidratación con API de Colombia.
    3. Fallback legacy.
    4. Catálogo local mínimo de respaldo.

## Patrón 2: Fail-Safe / Null Object pragmático para consulta opcional
- Nota: se documenta como implementación pragmática de robustez, alineada a encapsular ausencia de recurso sin excepción fatal.
- Problema que resuelve: evitar 500 cuando no existe la tabla de anuncios.
- Aplicación:
  - `services/notification-service/app/Http/Controllers/Admin/AdminNotificationController.php`
  - Método `announcementsQuery(): ?Builder` devuelve `null` cuando no existe tabla, y los handlers (`announcements`, `storeAnnouncement`, `updateAnnouncement`, `destroyAnnouncement`) responden de forma controlada.

## Patrón 3: Repository simplificado sobre Query Builder
- Referencia conceptual: encapsulación del acceso a datos para centralizar reglas de consulta.
- Problema que resuelve: evitar duplicación de lógica de acceso y fallback en múltiples puntos.
- Aplicación:
  - `services/payment-service/app/Http/Controllers/PaymentController.php`
  - Métodos internos: `resolveActiveBanks`, `hydrateBanksCatalogFromApi`, `normalizeBanksApiPayload`, `seedFallbackBankCatalog`, `fallbackBankCatalog`.

## Evidencia de datos iniciales idempotentes
- `services/notification-service/database/migrations/2026_04_18_000001_create_announcements_table.php`
  - Crea tabla `announcements` si no existe.
  - Inserta un anuncio base solo si la tabla está vacía.
- `services/payment-service/database/migrations/2026_04_18_000001_seed_colombian_banks_catalog.php`
  - Realiza `upsert` de un catálogo base de 27 bancos colombianos.

## Resultado esperado en UI
- Admin anuncios deja de mostrar error de servidor por ausencia de tabla y vuelve a listar anuncios.
- En el modal de pagos, el selector de bancos vuelve a poblarse con el catálogo activo.

## Patrón 4: Compensating Action pragmática para sincronización pago-orden
- Nota: no existe un patrón GoF exacto para esta compensación distribuida; se documenta la acción compensatoria como mecanismo pragmático de consistencia entre servicios.
- Problema que resuelve: evitar que un pago quede marcado como verificado en `payment-service` cuando `order-service` rechaza la actualización por conflicto de inventario o por fallo de sincronización.
- Aplicación:
  - `frontend/src/modules/admin/pages/AdminPaymentsPage.vue`
  - La acción administrativa primero actualiza el pago y luego sincroniza la orden.
  - Si `order-service` responde con error, la vista revierte el pago al estado anterior o lo lleva a `rejected` cuando la orden fue cancelada por falta de stock.
  - La IU muestra al administrador el mensaje real devuelto por backend en lugar de un warning genérico.

## Extensión 2026-05-10: formulario adaptable por tipo de anuncio

### Patrón 5: Strategy de variantes de formulario
- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: el modal de anuncios mostraba campos de banner aun cuando el admin elegía `Barra superior`, lo que inducía a errores y dejaba datos ocultos inconsistentes.
- Aplicación:
  - `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`
  - `services/notification-service/app/Http/Controllers/Admin/AdminNotificationController.php`
- Evidencia técnica:
  - El frontend cambia los campos visibles según `form.type` y solo envía subtítulo, botón, enlace e imagen cuando el tipo es `promo_banner`.
  - El backend refuerza la misma estrategia y limpia esos atributos cuando el anuncio queda en `top_bar`, incluyendo la eliminación segura de la imagen previa.
