# Patrones de diseño aplicados: admin clientes, facturación y notificaciones (2026-04-21)

Fecha: 2026-04-21

## Contexto
- En admin, órdenes y pagos mostraban cliente sin nombre/correo en casos donde solo existía user_id.
- Al completar/verificar pago, la factura no se persistía en algunos casos y no siempre quedaba visible en el listado de facturas.
- El cliente debía recibir aviso por notificación cuando la factura quedara disponible.

## Patrón 1: Adapter (hidratación de identidad cross-service)
- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: adaptar datos mínimos de órdenes/pagos (solo user_id) a una vista funcional con nombre/correo sin acoplar el frontend a estructuras internas.
- Archivos aplicados:
  - services/order-service/app/Http/Controllers/Admin/AdminOrderController.php
  - services/payment-service/app/Http/Controllers/Admin/AdminPaymentController.php
  - services/auth-service/app/Http/Controllers/Api/Internal/UserProfileController.php
- Implementación:
  - Se consulta auth-service por /internal/users/profiles para completar name/email/phone cuando no están en la fuente local.
  - Se preserva fallback local si auth-service no responde.

## Patrón 2: Strategy (selección de fuente de orden durante migración)
- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: decidir de forma consistente si una orden se lee/escribe en la base distribuida o en fallback legacy sin duplicar lógica por endpoint.
- Archivos aplicados:
  - services/order-service/app/Http/Controllers/OrderController.php
  - services/order-service/app/Services/OrderInvoiceService.php
- Implementación:
  - Se mantiene resolución por source con prioridad controlada (microservice/legacy) y fallback seguro.
  - Las acciones de facturación y estado de pago usan esa resolución para no perder coherencia de datos.

## Patrón 3: Guard Clauses (facturación robusta y notificación)
- Referencia: https://refactoring.guru/es/design-patterns
- Problema que resuelve: evitar abortar todo el flujo por un único fallo (correo, token interno o metadata parcial) y mantener estados claros.
- Archivos aplicados:
  - services/order-service/app/Services/OrderInvoiceService.php
  - frontend/src/modules/admin/pages/AdminPaymentsPage.vue
- Implementación:
  - En generación normal, la metadata de factura se persiste aunque falle el correo.
  - En reenvío forzado, se mantiene validación estricta de email.
  - Se agrega notificación al cliente cuando la factura queda disponible/reenviada.
  - El frontend de pagos envía source al sincronizar estado para evitar ambigüedad de conexión.

## Ajuste técnico adicional (compatibilidad de columna invoice_number)
- Problema: orders.invoice_number usa varchar(20), pero algunos números generados excedían ese límite.
- Archivo aplicado:
  - services/order-service/app/Services/OrderInvoiceService.php
- Solución:
  - Se normaliza y recorta el número de factura a un máximo de 20 caracteres con sufijo hash corto para mantener unicidad.

## Resultado esperado
- Admin de órdenes y pagos muestra cliente con nombre/correo en más casos reales.
- Facturas se generan, se persisten y se pueden listar/descargar con mayor confiabilidad.
- El cliente recibe notificación cuando su factura está disponible.