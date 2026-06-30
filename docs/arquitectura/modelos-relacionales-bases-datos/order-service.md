# order-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_orders`, responsable de pedidos, productos del pedido, historial de cambios, vistas, reservas de inventario, facturación básica y solicitudes de reembolso. Se actualiza con `stock_reservations` y `order_refund_requests`.

## Diagrama

```plantuml
@startuml
title angelow_orders - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "orders" as ord_orders {
  * id : int <<PK>>
  --
  order_number : varchar(20) <<UQ>>
  invoice_number : varchar(20)
  user_id : varchar(20) <<NULL>>
  status : varchar(20)
  subtotal : decimal(10,2)
  shipping_cost : decimal(10,2)
  discount_amount : decimal(10,2)
  total : decimal(10,2)
  payment_method : varchar(50)
  payment_status : varchar(20)
  shipping_method_id : int <<NULL>>
  shipping_address_id : int <<NULL>>
  billing_address_id : int <<NULL>>
  invoice_resolution : varchar(50)
  invoice_date : timestamp
}

entity "order_items" as ord_items {
  * id : int <<PK>>
  --
  order_id : int
  product_id : int
  color_variant_id : int <<NULL>>
  size_variant_id : int <<NULL>>
  product_name : varchar(255)
  variant_name : varchar(255)
  price : decimal(10,2)
  quantity : int
  total : decimal(10,2)
}

entity "order_status_history" as ord_history {
  * id : int <<PK>>
  --
  order_id : int
  changed_by : varchar(20) <<NULL>>
  changed_by_name : varchar(100)
  change_type : varchar(20)
  field_changed : varchar(100)
  old_value : text
  new_value : text
  description : text
  ip_address : varchar(45)
}

entity "order_views" as ord_views {
  * id : int <<PK>>
  --
  order_id : int
  user_id : varchar(20)
  viewed_at : timestamp
}

entity "stock_reservations" as ord_reservations {
  * id : bigint <<PK>>
  --
  order_id : bigint
  product_id : bigint
  size_variant_id : bigint <<NULL>>
  reservation_key : varchar(120)
  quantity : int
  status : varchar(20)
  expires_at : timestamp
  confirmed_at : timestamp
  released_at : timestamp
  metadata : json
}

entity "order_refund_requests" as ord_refunds {
  * id : int <<PK>>
  --
  order_id : int
  user_id : varchar(40)
  user_email : varchar(255)
  reason : varchar(80)
  details : text
  evidence_path : varchar(500)
  evidence_original_name : varchar(255)
  status : varchar(24)
  requested_at : timestamp
  resolved_at : timestamp
}

entity "auth.users" as ext_users <<externa>> {
  * id : varchar(20) <<PK>>
}

entity "catalog.products" as ext_products <<externa>> {
  * id : int <<PK>>
}

entity "catalog.product_color_variants" as ext_color_variants <<externa>> {
  * id : int <<PK>>
}

entity "catalog.product_size_variants" as ext_size_variants <<externa>> {
  * id : int <<PK>>
}

entity "shipping.shipping_methods" as ext_shipping_methods <<externa>> {
  * id : int <<PK>>
}

entity "shipping.user_addresses" as ext_user_addresses <<externa>> {
  * id : int <<PK>>
}

ord_orders ||--o{ ord_items : order_id
ord_orders ||--o{ ord_history : order_id
ord_orders ||--o{ ord_views : order_id
ord_orders ||--o{ ord_reservations : order_id
ord_orders ||--o{ ord_refunds : order_id
ord_orders }o..|| ext_users : user_id
ord_history }o..|| ext_users : changed_by
ord_views }o..|| ext_users : user_id
ord_refunds }o..|| ext_users : user_id
ord_orders }o..|| ext_shipping_methods : shipping_method_id
ord_orders }o..|| ext_user_addresses : shipping_address_id
ord_items }o..|| ext_products : product_id
ord_items }o..|| ext_color_variants : color_variant_id
ord_items }o..|| ext_size_variants : size_variant_id
ord_reservations }o..|| ext_products : product_id
ord_reservations }o..|| ext_size_variants : size_variant_id

note right of ord_orders
shipping_method_id y shipping_address_id
son referencias lógicas a shipping-service.
end note

note right of ord_items
product_id y variantes son referencias lógicas
a catalog-service.
end note
@enduml
```

## Fuentes revisadas

- `services/order-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/order-service/database/migrations/2026_04_17_180000_create_stock_reservations_table.php`
- `services/order-service/database/migrations/2026_06_19_000001_create_order_refund_requests_table.php`
- `services/order-service/app/Models/StockReservation.php`
- `services/order-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
