# Patrones de diseño aplicados (shimmer en dashboard cliente y detalle de producto)

<!-- indice:auto:start -->
## Índice rápido

- [Contexto del problema](#contexto-del-problema)
- [Patrón 1: State](#patrón-1-state)
- [Patrón 2: Flyweight](#patrón-2-flyweight)
- [Resultado esperado](#resultado-esperado)
- [Extensión 2026-06-07: responsive compacto para cuenta y detalle de producto](#extensión-2026-06-07-responsive-compacto-para-cuenta-y-detalle-de-producto)
  - [Patrón 3: Template Method + Composition](#patrón-3-template-method-composition)
<!-- indice:auto:end -->

Fecha: 2026-05-10

## Contexto del problema
- El dashboard del cliente mezclaba cargas planas con un skeleton aislado en notificaciones, generando una experiencia inconsistente entre resumen, pedidos, direcciones, favoritos, ajustes y detalle de pedido.
- La foto de perfil del aside no mostraba estado intermedio mientras cargaba la imagen real.
- El detalle de producto esperaba la respuesta completa con texto de carga y la imagen principal aparecía de forma brusca cuando terminaba de descargar.

## Patrón 1: State
- Referencia: https://refactoring.guru/es/design-patterns/state
- Problema que resuelve: controlar explícitamente los estados de carga visual antes de mostrar el contenido real del avatar del cliente y de la imagen principal del producto.
- Aplicación:
  - frontend/src/modules/account/components/AccountDashboardLayout.vue
  - frontend/src/modules/catalog/pages/ProductDetailPage.vue
- Implementación clave:
  - Estado reactivo para distinguir cuándo la imagen ya está lista para renderizarse.
  - Shimmer visible mientras la imagen está pendiente.
  - Transición a la imagen final solo cuando se confirma `load`.

## Patrón 2: Flyweight
- Referencia: https://refactoring.guru/es/design-patterns/flyweight
- Problema que resuelve: reutilizar la misma base visual de shimmer en todas las secciones del dashboard del cliente y evitar implementaciones ad hoc por página.
- Aplicación:
  - frontend/src/modules/account/components/AccountShimmer.vue
  - frontend/src/modules/account/views/AccountDashboardView.css
  - frontend/src/modules/account/pages/DashboardPage.vue
  - frontend/src/modules/account/pages/OrdersPage.vue
  - frontend/src/modules/account/pages/AddressesPage.vue
  - frontend/src/modules/account/pages/WishlistPage.vue
  - frontend/src/modules/account/pages/NotificationsPage.vue
  - frontend/src/modules/account/pages/OrderDetailPage.vue
  - frontend/src/modules/account/pages/SettingsPage.vue
  - frontend/src/modules/catalog/components/ProductDetailShimmer.vue
  - frontend/src/modules/catalog/views/ProductDetailView.css
- Implementación clave:
  - Un componente `AccountShimmer` parametrizado por variante para resumen, pedidos, direcciones, favoritos, notificaciones, ajustes y detalle de pedido.
  - Un componente `ProductDetailShimmer` para la estructura del detalle de producto.
  - Tokens visuales compartidos de shimmer en CSS para mantener ritmo, color y densidad visual consistentes.

## Resultado esperado
- Todo el dashboard de cliente muestra shimmer consistente durante la carga inicial.
- El aside mantiene shimmer en la foto de perfil hasta que la imagen esté lista.
- El detalle de producto presenta un esqueleto completo mientras llega la data y la imagen principal mantiene shimmer hasta terminar su carga real.
- La experiencia queda alineada entre desktop, tablet y móvil sin saltos bruscos de contenido.

## Extensión 2026-06-07: responsive compacto para cuenta y detalle de producto

### Patrón 3: Template Method + Composition
- Referencia: https://refactoring.guru/es/design-patterns/template-method
- Referencia complementaria: https://refactoring.guru/es/design-patterns/composite
- Problema que resuelve: en móvil el dashboard del cliente seguía usando proporciones de sidebar de escritorio y el detalle de producto comprimía tabs, CTA y miniaturas, dejando una lectura incómoda en anchos pequeños.
- Aplicación:
  - `frontend/src/modules/account/views/AccountDashboardView.css`
  - `frontend/src/modules/account/views/AddressesView.css`
  - `frontend/src/modules/catalog/views/ProductDetailView.css`
- Implementación clave:
  - se ajustan breakpoints compartidos del dashboard cliente para compactar perfil, navegación, tarjetas y formularios de direcciones sin duplicar layouts por vista;
  - el detalle de producto reorganiza galería, tabs y bloque de compra con una composición móvil más estable, manteniendo el mismo flujo funcional entre desktop, tablet y teléfono.

## Extensión 2026-06-07: stock realtime en detalle de producto

### Observer + Adapter

- Referencia: https://refactoring.guru/es/design-patterns/observer
- Referencia complementaria: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: el detalle del producto podía mostrar una talla como disponible mientras otra reserva o un ajuste de inventario ya había consumido esas unidades en el backend.
- Aplicación:
  - `frontend/src/composables/useStockRealtime.js`
  - `frontend/src/services/catalogApi.js`
  - `frontend/src/modules/catalog/pages/ProductDetailPage.vue`
- Implementación clave:
  - el composable `useStockRealtime` centraliza una sola conexión websocket y normaliza eventos de reserva/inventario;
  - `ProductDetailPage.vue` observa los `size_variant_id` del producto abierto, refresca solo las variantes tocadas mediante `/api/internal/variants/{id}` y reajusta la cantidad seleccionada si el stock bajó en tiempo real.
