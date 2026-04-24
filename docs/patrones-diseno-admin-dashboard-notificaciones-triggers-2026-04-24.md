# Patrones de diseño aplicados: dashboard admin + disparadores de notificaciones (2026-04-24)

## Contexto
Se corrigió la carga de órdenes recientes y productos destacados en dashboard admin, y se dejó funcional el flujo de disparadores para nuevos productos, ofertas y carrito abandonado con envío por notificación y/o email según preferencias.

## 1) Facade
- Patrón: Facade (Refactoring Guru)
- Problema que resuelve: evitar que cada controlador implemente manualmente validación de preferencias, resolución de tipo, encolado push, envío email y resultado por canal.
- Implementación: se centralizó una interfaz única de despacho en un servicio.
- Archivos:
  - services/notification-service/app/Services/NotificationDispatchService.php
  - services/notification-service/app/Http/Controllers/NotificationController.php

## 2) Chain of Responsibility
- Patrón: Chain of Responsibility (Refactoring Guru)
- Problema que resuelve: cuando una fuente de datos no está disponible, continuar con una fuente alternativa sin romper el flujo.
- Implementación A (dashboard): top productos usa cadena order-service -> catalog-service -> cálculo local con sold_count.
- Implementación B (disparadores): usuarios destino usa cadena user_ids explícitos -> usuarios legacy -> usuarios locales (preferencias/notificaciones).
- Archivos:
  - frontend/src/modules/admin/pages/AdminDashboardPage.vue
  - services/notification-service/app/Services/NotificationDispatchService.php

## 3) Strategy
- Patrón: Strategy (Refactoring Guru)
- Problema que resuelve: decidir el canal de entrega (push/email) y su comportamiento según combinación de flags solicitados + preferencias del usuario.
- Implementación: el despacho define estrategia de canal en tiempo de ejecución y reporta resultado por canal (sent/failed/skipped).
- Archivos:
  - services/notification-service/app/Services/NotificationDispatchService.php
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
  - services/cart-service/app/Services/CartService.php

## 4) Observer (implementación orientada a eventos de dominio)
- Patrón: Observer (Refactoring Guru)
- Problema que resuelve: emitir notificaciones ante eventos de negocio sin acoplar directamente la UI ni la lógica de cada dominio al detalle de entrega.
- Implementación: eventos de dominio (producto activado/creado, campaña de descuento, carrito abandonado) disparan publicación hacia notification-service.
- Archivos:
  - services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
  - services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php
  - services/cart-service/app/Services/CartService.php
  - services/cart-service/app/Http/Controllers/CartController.php

## Resultado funcional validado
- Guard de seguridad interno en carrito abandonado:
  - sin X-Internal-Token -> 403
  - con X-Internal-Token -> 200
- Disparador interno de notification-service:
  - con token y payload válido -> 200 con summary de envío por canal.
- Alta directa de notificación con evento promotion:
  - respuesta 201 con notification_sent=true.
