# Patrones - Checkout 2026-04-03

## Template Method

- Aplicación: secuencia visual compartida del checkout con cabecera, pasos, bloques principales, sidebar sticky y cierre de compra.
- Ubicación: `frontend/src/modules/checkout/components/CheckoutFlowHeader.vue`
- Ubicación: `frontend/src/modules/checkout/pages/ShippingPage.vue`
- Ubicación: `frontend/src/modules/checkout/pages/PaymentPage.vue`
- Ubicación: `frontend/src/modules/checkout/pages/ConfirmationPage.vue`
- Problema resuelto: las pantallas de `envío`, `pago` y `confirmación` no seguían la misma narrativa ni las microinteracciones de Angelow legacy.

## Adapter

- Aplicación: normalización de direcciones, métodos de envío e items del carrito para mover datos entre pasos con un contrato estable.
- Ubicación: `frontend/src/modules/checkout/utils/checkoutHelpers.js`
- Ubicación: `frontend/src/modules/checkout/pages/ShippingPage.vue`
- Ubicación: `frontend/src/modules/checkout/pages/PaymentPage.vue`
- Ubicación: `frontend/src/modules/checkout/pages/ConfirmationPage.vue`
- Problema resuelto: evitar duplicación y divergencia entre los datos del carrito, direcciones guardadas y snapshots usados en el checkout SPA.

## Adapter (refuerzo de robustez en confirmación de pago)

- Aplicación: normalización defensiva de `user_id` y extracción unificada del mensaje de error de API antes de renderizar el feedback en la vista de pago.
- Ubicación: `frontend/src/modules/checkout/pages/PaymentPage.vue`
- Ubicación: `frontend/src/services/paymentApi.js`
- Problema resuelto: el checkout podía fallar al crear la orden cuando `user_id` llegaba numérico (el backend valida string) y el usuario recibía un mensaje genérico sin contexto.

## Adapter (traducción de errores técnicos de inventario)

- Aplicación: traducción de errores técnicos al consultar variantes de inventario para devolver mensajes accionables al checkout.
- Ubicación: `services/order-service/app/Services/StockReservationService.php`
- Problema resuelto: cuando una variante no existía, el flujo devolvía un 503 genérico (`redis_unavailable`) que impedía entender que el problema real era un ítem desactualizado en el carrito.

## Adapter (contrato de variantes carrito -> checkout)

- Aplicación: exposición explícita de `size_variant_id` y `color_variant_id` en cart-service y normalización defensiva de IDs en checkout antes de construir el payload de orden.
- Ubicación: `services/cart-service/app/Services/CartService.php`
- Ubicación: `frontend/src/modules/checkout/utils/checkoutHelpers.js`
- Ubicación: `frontend/src/modules/checkout/pages/PaymentPage.vue`
- Problema resuelto: algunos ítems llegaban al paso de pago sin `size_variant_id`, provocando rechazo de validación en creación de orden y un error visible para el usuario.
