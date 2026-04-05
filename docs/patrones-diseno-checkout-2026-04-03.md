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
