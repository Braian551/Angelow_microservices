# discount-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_discounts`, responsable de tipos de descuento, códigos, productos asociados, uso de códigos, reglas por cantidad y descuentos asignados a usuarios.

## Diagrama

```plantuml
@startuml
title angelow_discounts - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "discount_types" as dis_types {
  * id : int <<PK>>
  --
  name : varchar(50)
  description : varchar(255)
  is_active : boolean
}

entity "discount_codes" as dis_codes {
  * id : int <<PK>>
  --
  code : varchar(20) <<UQ>>
  discount_type_id : int
  discount_value : decimal(10,2)
  max_uses : int
  used_count : int
  start_date : timestamp
  end_date : timestamp
  is_active : boolean
  is_single_use : boolean
  created_by : varchar(20)
}

entity "discount_code_products" as dis_code_products {
  * id : int <<PK>>
  --
  discount_code_id : int
  product_id : int
}

entity "discount_code_usage" as dis_code_usage {
  * id : int <<PK>>
  --
  discount_code_id : int
  user_id : varchar(20) <<NULL>>
  order_id : int <<NULL>>
  used_at : timestamp
}

entity "percentage_discounts" as dis_percentage {
  * id : int <<PK>>
  --
  discount_code_id : int
  percentage : decimal(5,2)
  max_discount_amount : decimal(10,2)
}

entity "fixed_amount_discounts" as dis_fixed {
  * id : int <<PK>>
  --
  discount_code_id : int
  amount : decimal(10,2)
  min_order_amount : decimal(10,2)
}

entity "free_shipping_discounts" as dis_free_shipping {
  * id : int <<PK>>
  --
  discount_code_id : int
  shipping_method_id : int <<NULL>>
}

entity "bulk_discount_rules" as dis_bulk_rules {
  * id : int <<PK>>
  --
  min_quantity : int
  max_quantity : int <<NULL>>
  discount_percentage : decimal(5,2)
  is_active : boolean
}

entity "user_applied_discounts" as dis_user_applied {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  discount_code_id : int
  discount_code : varchar(20)
  discount_amount : decimal(10,2)
  applied_at : timestamp
  expires_at : timestamp
  is_used : boolean
  used_at : timestamp
}

entity "auth.users" as ext_users <<externa>> {
  * id : varchar(20) <<PK>>
}

entity "catalog.products" as ext_products <<externa>> {
  * id : int <<PK>>
}

entity "orders.orders" as ext_orders <<externa>> {
  * id : int <<PK>>
}

entity "shipping.shipping_methods" as ext_shipping_methods <<externa>> {
  * id : int <<PK>>
}

dis_types ||--o{ dis_codes : discount_type_id
dis_types ||..o{ dis_bulk_rules : reglas por cantidad
dis_codes ||--o{ dis_code_products : discount_code_id
dis_codes ||--o{ dis_code_usage : discount_code_id
dis_codes ||--o{ dis_percentage : discount_code_id
dis_codes ||--o{ dis_fixed : discount_code_id
dis_codes ||--o{ dis_free_shipping : discount_code_id
dis_codes ||--o{ dis_user_applied : discount_code_id
dis_codes }o..|| ext_users : created_by
dis_code_products }o..|| ext_products : product_id
dis_code_usage }o..|| ext_users : user_id
dis_code_usage }o..|| ext_orders : order_id
dis_user_applied }o..|| ext_users : user_id
dis_free_shipping }o..|| ext_shipping_methods : shipping_method_id

note right of dis_code_products
product_id referencia de forma lógica
a catalog-service.
end note

note right of dis_free_shipping
shipping_method_id referencia de forma lógica
a shipping-service.
end note
@enduml
```

## Fuentes revisadas

- `services/discount-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/discount-service/app/Models/*.php`
- `services/discount-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
