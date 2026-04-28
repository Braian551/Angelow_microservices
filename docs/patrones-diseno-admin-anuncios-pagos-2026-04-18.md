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
