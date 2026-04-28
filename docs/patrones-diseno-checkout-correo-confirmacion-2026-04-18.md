# Patrones de diseño aplicados: Confirmación de correo en checkout (2026-04-18)

## Problema
En checkout se registraba la orden, pero el endpoint de confirmación devolvía `422` cuando la orden no tenía un correo persistido válido. Esto ocurría con más frecuencia en flujos no autenticados.

## Patrón 1: Chain of Responsibility (resolución de correo destinatario)
- Objetivo: definir una cadena robusta y explícita para obtener el correo de destino antes de enviar la confirmación.
- Implementación:
  - `services/order-service/app/Http/Controllers/OrderController.php`
  - `services/order-service/app/Services/OrderInvoiceService.php`
- Qué resuelve:
  - El controlador acepta `customer_email` en `send-confirmation`.
  - El servicio prioriza `customer_email` del contexto y, si no existe, mantiene fallback a correo de orden/usuario.
  - Reduce fallos por ausencia de columna o dato histórico en la orden.

## Patrón 2: Fail Fast + Validación en tiempo real
- Objetivo: prevenir submit inválido y asegurar feedback inmediato al usuario antes de crear/enviar confirmación.
- Implementación:
  - `frontend/src/modules/checkout/pages/PaymentPage.vue`
- Qué resuelve:
  - Se agrega campo obligatorio `Correo para confirmación` con validación `on input`.
  - Se bloquea submit cuando el correo no es válido.
  - El correo validado se reutiliza en creación de orden y en `send-confirmation`.

## Resultado esperado
- El checkout mantiene la creación del pedido y además tiene mayor probabilidad de envío correcto de correo de confirmación.
- Disminuye el escenario de advertencia: "El pedido quedó registrado, pero no pudimos enviar el correo de confirmación en este momento." cuando el usuario ingresa un correo válido.
