# Patrones de diseño aplicados (Home anuncios + stock con reserva Redis)

Fecha: 2026-04-20

## Contexto del problema
- La página inicial no mostraba el anuncio correcto (ni la barra superior), porque la home del catálogo tomaba una fuente distinta a la usada por el módulo admin de anuncios.
- El detalle de producto y el carrito permitían trabajar con stock bruto de base de datos sin descontar reservas temporales en Redis, generando error tardío en checkout/pago.
- En carrito, cuando fallaba la actualización de cantidad, la UI quedaba en estado de error global y se ocultaba el contenido principal.

## Patrón 1: Strategy (selección de fuente de datos con fallback)
- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: elegir la fuente más confiable para anuncios de home sin romper la UX si un servicio está temporalmente no disponible.
- Aplicación:
  - services/catalog-service/app/Services/SiteService.php
  - Método getHomeData() prioriza anuncios remotos de notification-service y, si fallan, usa fallback del repositorio local.
  - Métodos de soporte: fetchHomeAnnouncementsFromNotification() y resolveNotificationHomeAnnouncementsEndpoint().

## Patrón 2: Facade de integración para exposición pública de anuncios
- Referencia conceptual: encapsular varias reglas de consulta en un punto de entrada estable.
- Problema que resuelve: centralizar en notification-service la regla de "anuncios activos para home" y evitar que cada consumidor replique filtros/ordenamientos.
- Aplicación:
  - services/notification-service/app/Http/Controllers/Admin/AdminNotificationController.php
  - Nuevo endpoint funcional homeAnnouncements() con selección de top_bar y promo_banner activos.
  - Filtros encapsulados en activeAnnouncementsQuery().
  - Ajuste de normalización en transformAnnouncement() para fallback de message usando title cuando aplica.
  - Exposición de ruta en services/notification-service/routes/api.php con GET /announcements/home.

## Patrón 3: Guard Clauses para control de stock en tiempo real
- Referencia: https://refactoring.guru/es/design-patterns
- Problema que resuelve: bloquear rápido operaciones inválidas (agregar/actualizar cantidad) cuando el stock disponible ya fue reservado temporalmente.
- Aplicación:
  - services/cart-service/app/Services/CartService.php
  - Métodos addToCart() y updateQuantity() ahora validan contra stock efectivo.
  - Métodos auxiliares: resolveRealtimeAvailableStock() y buildOutOfStockMessage().
  - getCartItems() expone available_stock usando el mismo cálculo de disponibilidad efectiva.

## Patrón 4: Single Source of Truth para stock disponible en frontend catálogo
- Referencia conceptual: evitar múltiples criterios de verdad para una misma métrica crítica.
- Problema que resuelve: mostrar en detalle de producto la disponibilidad real considerando reservas temporales en Redis.
- Aplicación:
  - services/catalog-service/app/Repositories/QueryBuilderProductRepository.php
  - services/catalog-service/app/Http/Controllers/InternalCatalogController.php
  - getVariants() calcula quantity usando resolveRealtimeAvailableStock() con prioridad Redis y fallback seguro a base de datos.
  - variant() del endpoint interno aplica la misma regla para consumidores de integración.

## Patrón 5: Separación de estados de error UI (error de carga vs error de acción)
- Referencia conceptual: separación de responsabilidades en estado de vista.
- Problema que resuelve: impedir que un fallo al actualizar cantidad destruya la vista completa del carrito.
- Aplicación:
  - frontend/src/modules/cart/pages/CartPage.vue
  - Estado actionMessage separado de errorMessage.
  - syncCartState() para refresco de datos sin reactivar estado de fallo global.
  - Mensajes de error del backend mostrados de forma contextual con snackbar y alerta inline.

## Ajustes de consistencia adicionales
- services/catalog-service/app/Repositories/QueryBuilderSiteRepository.php
  - Se agregó desempate por id DESC en consultas de anuncios para orden determinista.
- frontend/src/components/home/TopAnnouncementBar.vue
  - La barra superior ahora renderiza message o title como fallback para evitar vacíos por datos incompletos.
- frontend/src/modules/catalog/pages/ProductDetailPage.vue
  - addItemToCart() muestra el mensaje real devuelto por backend cuando no hay stock.

## Ajuste adicional 2026-04-20 (cantidad máxima + botón volver)
- Patrón aplicado: Guard Clauses en UI para validación temprana de cantidad.
- Problema que resuelve: evitar tope artificial (10) y explicar con feedback claro por qué no se puede superar el stock real.
- Aplicación:
  - frontend/src/modules/catalog/pages/ProductDetailPage.vue
  - quantityMax ahora usa stock real.
  - normalizeQuantity() y changeQuantity() muestran snackbar específico al intentar superar el límite.
  - addItemToCart() ahora muestra mensaje detallado del backend en lugar de error genérico.
- Patrón aplicado: Encapsulación de estilo contextual para evitar colisión de estilos globales.
- Problema que resuelve: botón "Volver" renderizado detrás/arriba del header tras navegación desde favoritos.
- Aplicación:
  - frontend/src/modules/catalog/views/ProductDetailView.css
  - back-button-container y back-button fuerzan posicionamiento en flujo del detalle para mantener jerarquía visual correcta.

## Resultado esperado
- Home consume y muestra anuncios coherentes con lo configurado en admin (top_bar + promo_banner).
- Desde detalle y carrito, la variante reservada temporalmente en Redis se visualiza con disponibilidad 0 y se bloquea agregar más unidades.
- El error de stock deja de aparecer tardíamente como sorpresa en pago y se informa antes, en el punto de interacción correcto.
- El carrito ya no desaparece al fallar un cambio de cantidad.
