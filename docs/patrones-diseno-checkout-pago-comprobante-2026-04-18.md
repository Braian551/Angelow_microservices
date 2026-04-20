# Patrones de diseño aplicados (Checkout pago - comprobante)

Fecha: 2026-04-18

## Contexto del problema
- En checkout, al confirmar el pago aparecía el error: "The payment proof field must be a string".
- La causa era el envío de `FormData` con un `Content-Type: application/json` forzado desde el cliente HTTP compartido.

## Patrón 1: Strategy para serialización de request
- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: seleccionar la estrategia correcta de serialización según el tipo de payload.
- Aplicación:
  - [frontend/src/services/http.js](frontend/src/services/http.js)
  - En el interceptor request:
    - Si el payload es `FormData`, elimina `Content-Type` para que el navegador genere `multipart/form-data` con boundary.
    - En payloads JSON, Axios mantiene su serialización estándar.

## Patrón 2: Adapter de mensajes técnicos a copy de usuario
- Referencia conceptual: adaptar errores de backend a mensajes comprensibles y consistentes en UI.
- Problema que resuelve: evitar mensajes técnicos en inglés al usuario final.
- Aplicación:
  - [frontend/src/modules/checkout/pages/PaymentPage.vue](frontend/src/modules/checkout/pages/PaymentPage.vue)
  - Función `translateCheckoutApiMessage(...)` para mapear validaciones técnicas de `payment_proof` a mensajes claros en español.

## Resultado esperado
- El comprobante se envía como archivo multipart y el endpoint crea la transacción sin error de tipo.
- Si el backend responde validaciones técnicas, la UI muestra mensajes en español orientados al usuario.
