# Referencias lógicas entre bases de datos

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Criterio de lectura](#criterio-de-lectura)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Mapa de referencias lógicas entre bases de datos de microservicios. Las líneas indican integración por identificadores compartidos, llamadas internas, eventos o contratos de negocio; no representan llaves foráneas físicas entre bases distintas.

## Diagrama

```plantuml
@startuml
title Referencias lógicas entre dominios de datos
left to right direction
skinparam componentStyle rectangle
skinparam shadowing false

database "angelow_auth\nusers, tokens, sesiones" as db_auth
database "angelow_catalog\nproductos, variantes, favoritos,\nreseñas, preguntas, inventario" as db_catalog
database "angelow_cart\ncarts, cart_items" as db_cart
database "angelow_orders\norders, items, reservas,\nreembolsos, facturas" as db_orders
database "angelow_payments\nbancos, cuenta,\ntransacciones" as db_payments
database "angelow_discounts\ncódigos, reglas,\nusos" as db_discounts
database "angelow_shipping\nmétodos, reglas, direcciones,\nrepartidores y entregas" as db_shipping
database "angelow_notifications\ntipos, bandeja,\npreferencias, cola" as db_notifications
database "angelow_audit\naudit_*" as db_audit
cloud "Redis / WebSocket\nrealtime-gateway" as realtime

db_auth --> db_cart : user_id
db_auth --> db_orders : user_id / changed_by
db_auth --> db_payments : user_id / verified_by
db_auth --> db_discounts : user_id / created_by
db_auth --> db_shipping : user_id / courier user_id / reviewed_by
db_auth --> db_notifications : user_id / admin_id
db_auth --> db_catalog : user_id en favoritos, reseñas y preguntas

db_catalog --> db_cart : product_id / variantes
db_catalog --> db_orders : product_id / variantes / política de reembolso
db_catalog --> db_discounts : product_id
db_catalog --> db_notifications : alertas de inventario

db_cart --> db_orders : productos seleccionados
db_shipping --> db_orders : shipping_method_id / shipping_address_id
db_orders --> db_shipping : elegibilidad y order_id
db_shipping --> db_notifications : solicitudes, asignaciones y estados de entrega
db_orders --> db_catalog : confirmación y liberación de inventario
db_orders --> db_payments : order_id / estado de pago
db_orders --> db_discounts : order_id / uso de descuento
db_orders --> db_notifications : eventos de pedido, factura y reembolso
db_orders --> db_audit : trazabilidad de pedidos

db_payments --> db_orders : pago aprobado, rechazado o reembolsado
db_payments --> db_notifications : eventos de pago
db_discounts --> db_notifications : campañas y beneficios
db_notifications --> realtime : eventos para clientes conectados
db_orders --> realtime : cambios de pedidos y reservas
db_catalog --> realtime : cambios de stock

note bottom
Estas referencias son lógicas de integración entre servicios.
No dependen de llaves foráneas físicas entre bases de datos distintas.
end note
@enduml
```

## Criterio de lectura

- Cada base mantiene sus tablas propias y consulta otros dominios por API, eventos o identificadores compartidos.
- Los campos `user_id`, `product_id`, `order_id`, `shipping_method_id` y similares representan contratos entre servicios, no integridad referencial física entre bases.
- `shipping-service` conserva la asignación, el destino y la trazabilidad de la entrega; `order-service` mantiene la propiedad del pedido y publica la elegibilidad mediante contrato interno.
- `realtime-gateway` no guarda tablas; solo reenvía eventos publicados por los servicios.

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Arquitectura web en PlantUML](../arquitectura-web-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
