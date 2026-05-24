# Patrones de diseño aplicados (Aside admin + filtros tienda)

Fecha: 2026-04-21

## Contexto del problema
- El aside del dashboard no seguía los colores configurados en General (`primary_color` y `secondary_color`).
- El scrollbar del aside quedó demasiado delgado y no respetaba la identidad visual esperada.
- En tienda, al filtrar por género (`nina`, `nino`, `bebe`) se producía error 500.
- El flujo de filtros en frontend no estaba totalmente consistente (limpiar/aplicar/sincronizar query).

## Patrón 1: Adapter (normalización de filtros de UI a API)
- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: convertir valores de filtros de la interfaz (strings/query params) a un formato estable para la API del catálogo.
- Archivos aplicados:
  - frontend/src/modules/catalog/pages/StorePage.vue
- Implementación:
  - Normalización y tipado de filtros (`category`, `collection`, `min_price`, `max_price`).
  - Unificación de claves de filtros (`STORE_FILTER_KEYS`) para limpiar y aplicar query sin estados residuales.
  - Corrección del comportamiento de “Limpiar” para reiniciar filtros de forma consistente.

## Patrón 2: State (estado visual del aside basado en settings)
- Referencia: https://refactoring.guru/es/design-patterns/state
- Problema que resuelve: mantener el aside visualmente coherente con la configuración de marca sin hardcodear colores.
- Archivos aplicados:
  - frontend/src/modules/admin/components/AdminSidebar.vue
  - frontend/src/modules/admin/styles/admin.css
- Implementación:
  - Variables CSS dinámicas por inline style (`--admin-sidebar-*`) calculadas desde settings.
  - Conversión/normalización de color HEX y derivación de variantes (rgba y tonos más oscuros).
  - Scrollbar del aside ajustado a mayor grosor y tematizado con el color primario.

## Patrón 3: Guard Clauses (fallback seguro cuando legacy no está disponible)
- Referencia: https://refactoring.guru/es/design-patterns
- Problema que resuelve: evitar 500 cuando el fallback de catálogo hacia `legacy_mysql` no está disponible.
- Archivos aplicados:
  - services/catalog-service/app/Repositories/QueryBuilderProductRepository.php
- Implementación:
  - `try/catch` alrededor de la consulta fallback a legacy.
  - Log de advertencia controlado y retorno del resultado principal (aunque sea vacío) en lugar de romper la API.

## Resultado esperado
- El aside admin respeta colores configurados en General y mantiene scrollbar más visible solo en el aside.
- La tienda deja de fallar con 500 al filtrar por género cuando legacy está inaccesible.
- Los filtros del Store funcionan de forma consistente en aplicar, limpiar y sincronizar con la URL.

## Ajuste de corrección (misma fecha)

### Patrón adicional: Adapter (normalización robusta de género)
- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: productos con género persistido en variantes de texto (acentos, plural y valores con mojibake) no coincidían con filtros `nina/nino/bebe`.
- Archivos aplicados:
  - services/catalog-service/app/Repositories/QueryBuilderProductRepository.php
- Implementación:
  - Se reemplazó el `whereIn` exacto por búsqueda flexible `LIKE/ILIKE` con términos normalizados.
  - Se añadieron variantes de búsqueda para `niña/niño/bebé`, plurales y representaciones con codificación degradada.

### Patrón adicional: State (apertura/cierre de filtros resistente a colisión CSS)
- Referencia: https://refactoring.guru/es/design-patterns/state
- Problema que resuelve: el sidebar de filtros en tienda no desplegaba opciones por conflicto con estilos globales legacy (`.filter-options { display: none; }`).
- Archivos aplicados:
  - frontend/src/modules/catalog/pages/StorePage.vue
  - frontend/src/modules/catalog/pages/StorePage.css
- Implementación:
  - Se movió el control visual a clases explícitas (`store-filter-options` + `is-open`) independientes de reglas legacy.
  - Se conservó el estado reactivo por sección (`openFilters`) sin depender de clases globales heredadas.

### Patrón adicional: Decorator (scrollbar aplicado al contenedor correcto)
- Referencia: https://refactoring.guru/es/design-patterns/decorator
- Problema que resuelve: el scrollbar del aside parecía no cambiar porque el scroll real ocurre en `.sidebar-nav`, no en `.admin-sidebar`.
- Archivos aplicados:
  - frontend/src/modules/admin/styles/admin.css
- Implementación:
  - Se trasladó el estilo de scrollbar grueso al contenedor que realmente desplaza (`.sidebar-nav`).
  - Se mantuvo el alcance visual solo al aside admin, sin afectar scroll global del sitio.

## Ajuste visual adicional de tienda e inicio (misma fecha)

### Patrón adicional: State (filtros colapsables con estado explícito)
- Referencia: https://refactoring.guru/es/design-patterns/state
- Problema que resuelve: mantener el comportamiento de secciones de filtros cerradas por defecto, con apertura/cierre controlado sin depender de estilos heredados.
- Archivos aplicados:
  - frontend/src/modules/catalog/pages/StorePage.vue
  - frontend/src/modules/catalog/pages/StorePage.css
- Implementación:
  - Estado reactivo por grupo (`openFilters`) inicializado en `false` para paridad con la UI legacy.
  - Encabezados de sección convertidos a botones accesibles con ícono que refleja estado abierto/cerrado.

### Patrón adicional: Adapter (normalización de rango de precios para URL y API)
- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: traducir valores de sliders de precio a filtros limpios en query params y payload API sin enviar ruido cuando se usan valores por defecto.
- Archivos aplicados:
  - frontend/src/modules/catalog/pages/StorePage.vue
- Implementación:
  - Capa de normalización (`clampPrice`, `syncPriceRangeFromRoute`, `syncPriceFiltersFromRange`).
  - Conversión consistente de límites de precio entre estado visual, URL y consulta al catálogo.

### Patrón adicional: Facade (componente de paginación para catálogo)
- Referencia: https://refactoring.guru/es/design-patterns/facade
- Problema que resuelve: evitar lógica de paginación embebida en la vista de tienda y centralizar navegación de páginas en un componente reutilizable.
- Archivos aplicados:
  - frontend/src/modules/catalog/components/StorePagination.vue
  - frontend/src/modules/catalog/pages/StorePage.vue
- Implementación:
  - Componente único para cálculo de páginas visibles con elipsis y emisión de cambios de página.
  - Integración limpia en `StorePage` para mantener foco en filtros y render de productos.

### Patrón adicional: Parameter Object (límite explícito en destacados de inicio)
- Referencia: https://refactoring.guru/es/design-patterns
- Problema que resuelve: controlar de forma explícita cuántos productos destacados se renderizan para evitar sobrecarga visual en inicio.
- Archivos aplicados:
  - frontend/src/modules/home/pages/HomePage.vue
- Implementación:
  - Constante `HOME_FEATURED_PRODUCTS_LIMIT` para definir el límite desde un solo punto.
  - Uso del límite tanto en la consulta (`per_page`) como en el render final (`slice`).
