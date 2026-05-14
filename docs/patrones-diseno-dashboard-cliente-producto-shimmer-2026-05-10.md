# Patrones de diseño aplicados (shimmer en dashboard cliente y detalle de producto)

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