# Order Service

Microservicio Laravel encargado de la gestión de órdenes, ítems y trazabilidad de cambios de estado.

## Responsabilidad

- Crear y consultar órdenes.
- Actualizar estado de órdenes con historial auditable.
- Exponer endpoints API para consumo interno y frontend.

## Endpoints

- `GET /api/health`
- `GET /api/orders`
- `POST /api/orders`
- `GET /api/orders/{id}`
- `PATCH /api/orders/{id}/status`

## Base de datos (PostgreSQL)

Tablas de dominio:

- `orders`
- `order_items`
- `order_status_history`
- `order_views`

## Patrones aplicados

- `Service + Controller API`: separación entre entrada HTTP y reglas.
- `Repository/DB Facade`: acceso persistente desacoplado de la capa de transporte.
- `Event history`: registro de transición de estado para trazabilidad.
