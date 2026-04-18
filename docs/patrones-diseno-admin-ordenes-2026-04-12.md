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

## Extension 2026-04-15: direccion enriquecida en detalle de orden
- Problema:
  El detalle de orden mostraba direccion parcial (solo direccion/ciudad), mientras en direcciones del cliente existen mas datos operativos (alias, destinatario, telefono, complemento, barrio, tipo de edificacion, apartamento e indicaciones).
- Patron aplicado: Adapter
  - Archivo: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue`
  - Solucion: se adapto la respuesta de `shipping-service` (`/shipping/addresses`) al contrato de UI del detalle usando `normalizeCheckoutAddress`, permitiendo mostrar informacion completa sin acoplar la vista a formatos heterogeneos.
- Patron aplicado: Strategy
  - Archivo: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue`
  - Solucion: se implemento `findBestShippingAddressMatch` con estrategia escalonada de resolucion (por `shipping_address_id`, luego por coincidencia de contenido, luego por direccion principal) para tolerar variaciones historicas de datos sin romper el flujo.
- Servicios consultados:
  - `order-service` para obtener el pedido (`/orders/{id}`)
  - `shipping-service` para enriquecer direccion (`/shipping/addresses`)
- Dependencias:
  No se agregaron ni actualizaron librerias o dependencias en esta extension.

## Extension 2026-04-15: reorganizacion visual del resumen de orden
- Problema:
  El bloque inferior del resumen mezclaba datos de pedido, cliente y pago en una banda continua, sin agrupacion visual suficiente, lo que dificultaba el escaneo y rompia la jerarquia del dashboard admin.
- Patron aplicado: Composite
  - Archivo: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue`
  - Solucion: el resumen se reorganizo como un conjunto de paneles internos (`Datos del pedido`, `Cliente`, `Pago y totales`) dentro de una misma tarjeta contenedora, permitiendo separar responsabilidades visuales sin cambiar la fuente de datos ni duplicar componentes del sistema.
- Dependencias:
  No se agregaron ni actualizaron librerias o dependencias en esta extension.

## Extension 2026-04-15: reorganizacion visual de direccion, comprobante y productos
- Problema:
  La seccion inferior del detalle dejaba la direccion y el comprobante apilados en una sola columna, generando un hueco visual grande junto a productos y desaprovechando el ancho disponible.
- Patron aplicado: Composite
  - Archivo: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue`
  - Solucion: se redistribuyeron los tres bloques como una composicion de paneles coordinados dentro de un grid con areas (`address`, `proof`, `products`), dejando direccion y comprobante en paralelo y productos a ancho completo para mejorar lectura y balance visual.
- Dependencias:
  No se agregaron ni actualizaron librerias o dependencias en esta extension.

## Extension 2026-04-15: coherencia del contrato de direccion en tarjeta y modal
- Problema:
  La tarjeta de direccion y el modal de edicion mostraban campos inconsistentes con el flujo real de `Agregar Nueva Dirección`, incluyendo una ciudad que el usuario no diligencia porque la operacion trabaja con direccion, complemento, barrio/zona y detalles de edificacion.
- Patron aplicado: Adapter
  - Archivo: `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue`
  - Solucion: se adapto la presentacion del detalle admin al mismo contrato funcional de direcciones del cliente (`address`, `complement`, `neighborhood`, `building_type`, `building_name`, `apartment_number`, `delivery_instructions`), eliminando la ciudad del modal y reutilizando la direccion estructurada guardada como fuente principal de visualizacion.
- Dependencias:
  No se agregaron ni actualizaron librerias o dependencias en esta extension.

## Extensión 2026-04-17: notificación interna + correo al cambiar estado/pago
- Problema:
  Al actualizar estado de orden o estado de pago desde admin, el cliente no recibía aviso en su bandeja ni por correo.
- Patrón aplicado: Observer (evento de dominio tras actualización)
  - Archivo: services/order-service/app/Http/Controllers/OrderController.php
  - Solución: después de persistir el cambio y registrar historial, se dispara la orquestación de comunicación `notifyOrderUpdateChannels` para publicar notificación interna y correo al cliente.
- Patrón aplicado: Adapter
  - Archivo: services/order-service/app/Http/Controllers/OrderController.php
  - Solución: se normalizan estados operativos técnicos (`pending`, `paid`, `cancelled`, etc.) a copy en español antes de componer el mensaje visible para notificación y email.
- Patrón aplicado: Template Method
  - Archivos:
    - services/order-service/app/Http/Controllers/OrderController.php
    - services/order-service/config/services.php
    - services/order-service/.env.example
  - Solución: se unifica la secuencia de envío (resolver endpoint, construir payload, enviar, registrar warning sin romper flujo principal) para cualquier cambio de estado/pago.
- Verificación automática:
  - Archivo: services/order-service/tests/Feature/OrderApiTest.php
  - Cobertura: prueba de integración que valida despacho HTTP a `notification-service` y tentativa de envío de correo en cambios de estado y pago.

## Extensión 2026-04-17: facturación automática + bandeja admin de facturas
- Problema:
  El flujo de checkout y cumplimiento de orden no enviaba confirmación completa ni factura PDF al cliente, y administración no tenía una sección central para auditar/reenviar facturas.
- Patrón aplicado: Service Layer
  - Archivo: `services/order-service/app/Services/OrderInvoiceService.php`
  - Solución: se encapsuló la lógica de confirmación de checkout, validación de estado/pago, generación PDF, envío de correo y listado consolidado de facturas (microservicio + fallback legacy) en un servicio único reutilizable.
- Patrón aplicado: Template Method
  - Archivos:
    - `services/order-service/app/Http/Controllers/OrderController.php`
    - `services/order-service/app/Http/Controllers/Admin/AdminInvoiceController.php`
  - Solución: los controladores delegan la secuencia crítica al servicio y mantienen un flujo uniforme para enviar confirmación, disparar factura automática y exponer acciones de listado/descarga/reenvío.
- Patrón aplicado: Adapter
  - Archivo: `frontend/src/modules/admin/pages/AdminInvoicesPage.vue`
  - Solución: se normalizan filas heterogéneas de facturación (`order_source`, nombres de cliente, estados y totales) a un contrato de vista único para tabla, métricas y modal de detalle.
- Rutas impactadas:
  - `POST /api/orders/{id}/send-confirmation`
  - `GET /api/admin/invoices`
  - `GET /api/admin/invoices/{id}/download`
  - `POST /api/admin/invoices/{id}/resend`
- UI impactada:
  - `frontend/src/modules/admin/pages/AdminInvoicesPage.vue`
  - `frontend/src/modules/admin/components/AdminSidebar.vue`
  - `frontend/src/modules/admin/components/AdminHeader.vue`
  - `frontend/src/router/index.js`
- Dependencias:
  - Se requiere `dompdf/dompdf` en `services/order-service` para generación del PDF adjunto.

## Extensión 2026-04-17: notificaciones de órdenes + flujo de reembolso visible
- Problema:
  La campana del admin no estaba conectada a eventos reales de órdenes, en cliente no se reflejaban automáticamente las novedades de notificaciones, y al cancelar una orden pagada/verificada no existía una barra específica de progreso de reembolso.
- Patrón aplicado: Observer (orquestación de eventos de dominio de orden)
  - Archivo: `services/order-service/app/Http/Controllers/OrderController.php`
  - Solución: se incorporó notificación de creación de orden (`notifyOrderCreationChannels`) y automatización de transición a `pending_refund` cuando una cancelación aplica reembolso, notificando además el cambio de `payment_status`.
- Patrón aplicado: Strategy
  - Archivos:
    - `frontend/src/modules/account/pages/OrdersPage.vue`
    - `frontend/src/modules/account/pages/OrderDetailPage.vue`
  - Solución: se definieron estrategias de timeline por contexto (flujo normal vs flujo de reembolso), permitiendo mostrar una barra de progreso dedicada al reembolso sin romper el recorrido clásico de entrega.
- Patrón aplicado: Polling controlado (pull-based observer en UI)
  - Archivos:
    - `frontend/src/modules/admin/components/AdminHeader.vue`
    - `frontend/src/modules/account/pages/AccountLayoutPage.vue`
    - `frontend/src/modules/account/pages/NotificationsPage.vue`
  - Solución: refresco periódico con control por visibilidad para detectar nuevas órdenes/cancelaciones/reembolsos y mantener badges/listados de notificaciones sincronizados sin recarga manual.
- Patrón aplicado: Adapter
  - Archivo: `frontend/src/modules/admin/components/AdminHeader.vue`
  - Solución: normalización de payload heterogéneo de `/admin/orders` a un contrato de eventos de notificación (`new`, `cancel`, `pending_refund`, `refunded`) consumible por la campana del header.
- Ajuste de paridad operativa en filtros/acciones de admin:
  - Archivo: `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
  - Solución: se agregó `pending_refund` en filtros y formularios de estado de pago para que el operador admin pueda consultar y gestionar el estado intermedio de reembolso.
- Unificación de fuente de notificación al crear pedido:
  - Archivo: `frontend/src/modules/checkout/pages/PaymentPage.vue`
  - Solución: se retiró la creación manual directa de notificación desde checkout para evitar duplicados y delegar la notificación de creación al flujo central del `order-service`.
- Dependencias:
  No se agregaron ni actualizaron librerías o dependencias en esta extensión.
