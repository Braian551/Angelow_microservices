# Patrones de diseño aplicados - Admin Ordenes (2026-04-12)

## Contexto
Se alineó la vista de detalle de ordenes del dashboard con el flujo funcional esperado (resumen completo, direccion, productos, historial y acciones de cambio de estado), corrigiendo el mapeo de datos y la cobertura de acciones para evitar comportamiento incompleto.

## 1) Template Method
- Patron: Template Method (Refactoring Guru)
- Problema que resuelve:
  Estandarizar la secuencia de actualizacion de orden (resolver origen de datos, detectar columna real, actualizar registro y registrar historial) evitando logica duplicada y errores cuando la orden proviene de la conexion principal o de fallback.
- Aplicado en:
  - services/order-service/app/Http/Controllers/OrderController.php
- Evidencia tecnica:
  - Los metodos updateStatus y updatePaymentStatus ejecutan una secuencia uniforme apoyandose en pasos reutilizables: resolveOrderSource, firstExistingColumn e insertOrderHistory.

## 2) Adapter
- Patron: Adapter (Refactoring Guru)
- Problema que resuelve:
  Adaptar estructuras de respuesta heterogeneas del endpoint de orden (order/items/history con variantes de campos) a un modelo de vista estable para el frontend.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminOrderDetailPage.vue
- Evidencia tecnica:
  - La funcion normalizeOrder transforma diferentes nombres de campos (status u order_status, user_name/customer_name, billing_*) a una forma unica consumida por la UI.

## 3) Command
- Patron: Command (Refactoring Guru)
- Problema que resuelve:
  Encapsular acciones de usuario del detalle (editar orden, cambiar estado, cambiar estado de pago) en operaciones explicitas y desacopladas de la capa visual principal.
- Aplicado en:
  - frontend/src/modules/admin/pages/AdminOrderDetailPage.vue
- Evidencia tecnica:
  - Operaciones separadas en metodos submitEditOrder, submitStatusChange y submitPaymentStatusChange, cada una con su validacion en tiempo real y su llamada de API.

## Archivos impactados por la implementacion
- frontend/src/modules/admin/pages/AdminOrderDetailPage.vue
- services/order-service/app/Http/Controllers/OrderController.php
- services/order-service/routes/api.php

## Dependencias
No se agregaron ni actualizaron librerias o dependencias en esta tarea.

## Extension 2026-04-12: acciones rapidas y desactivacion en listado
- Problema:
  La vista de listado no tenia paridad de acciones rapidas (acciones masivas y flujo de desactivacion), y aun existia el enfoque de eliminar en vez de desactivar.
- Patron aplicado: Command
  - Archivo: `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
  - Solucion: acciones explicitas `submitStatusChange`, `submitPaymentStatusChange`, `submitBulkAction` y `confirmDeactivateOrder`, desacoplando eventos de UI de la ejecucion API.
- Patron aplicado: Template Method
  - Archivo: `services/order-service/app/Http/Controllers/OrderController.php`
  - Solucion: `deactivate` reutiliza la misma secuencia estandar del controlador (resolver origen, detectar columna real, actualizar, registrar historial), manteniendo consistencia entre conexion principal y fallback.
- Rutas impactadas:
  - `services/order-service/routes/api.php` agrega `PATCH /api/orders/{id}/deactivate`.
