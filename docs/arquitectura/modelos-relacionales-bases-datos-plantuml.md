# Modelos relacionales por base de datos en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagramas separados](#diagramas-separados)
- [Modelo completo](#modelo-completo)
- [Criterios de actualización](#criterios-de-actualización)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Este índice organiza los modelos relacionales de Angelow por microservicio. El documento monolítico anterior fue separado para que cada base de datos pueda renderizarse, revisarse y mantenerse de forma independiente.

La revisión se hizo el 2026-08-17 contra migraciones, modelos, rutas y controladores de `services/*`, incluyendo el flujo de vinculación de repartidores, asignación y seguimiento de entregas.

## Diagramas separados

- [auth-service](modelos-relacionales-bases-datos/auth-service.md)
- [catalog-service](modelos-relacionales-bases-datos/catalog-service.md)
- [cart-service](modelos-relacionales-bases-datos/cart-service.md)
- [order-service](modelos-relacionales-bases-datos/order-service.md)
- [payment-service](modelos-relacionales-bases-datos/payment-service.md)
- [discount-service](modelos-relacionales-bases-datos/discount-service.md)
- [shipping-service](modelos-relacionales-bases-datos/shipping-service.md)
- [notification-service](modelos-relacionales-bases-datos/notification-service.md)
- [audit-service](modelos-relacionales-bases-datos/audit-service.md)
- [realtime-gateway](modelos-relacionales-bases-datos/realtime-gateway.md)
- [Referencias lógicas entre bases de datos](modelos-relacionales-bases-datos/referencias-logicas.md)

## Modelo completo

- [Modelo relacional completo con todas las tablas de negocio](modelo-relacional-completo-plantuml.md)

## Criterios de actualización

- Se omiten tablas técnicas de Laravel como `cache`, `cache_locks`, `jobs`, `job_batches` y `failed_jobs` porque no representan entidades de negocio.
- Las referencias entre bases distintas se documentan como relaciones lógicas, no como llaves foráneas físicas.
- Los campos remanentes de migración `trial*` se omiten en los diagramas para mantener el foco en el modelo funcional.
- Los diagramas incluyen tablas nuevas o ajustadas por cambios recientes: `auth_login_attempts`, `inventory_alerts`, `products.is_refundable`, `products.refund_days`, `stock_reservations`, `order_refund_requests`, `courier_profiles`, `courier_vehicles`, `courier_documents`, `delivery_assignments` y `courier_locations`.
- En `shipping-service`, la migración de depuración elimina de `courier_profiles` los campos laborales y de ciudad que ya no son parte del registro; el modelo documentado representa el esquema posterior a esa migración.
- `realtime-gateway` queda documentado como servicio sin base relacional propia.

## Documentos relacionados

- [Diagramas de clases por microservicio](diagramas-clases-microservicios-plantuml.md)
- [Modelo relacional completo](modelo-relacional-completo-plantuml.md)
- [Arquitectura web en PlantUML](arquitectura-web-plantuml.md)
- [Mapa de navegación del sistema](mapas-navegacion-sistema-plantuml.md)
- [Documentación por microservicio](../microservicios/README.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
