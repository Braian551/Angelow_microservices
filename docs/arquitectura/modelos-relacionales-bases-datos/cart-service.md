# cart-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_cart`, responsable de carritos y productos seleccionados por usuario o por sesión anónima. Las referencias a productos y variantes son lógicas hacia catálogo.

## Diagrama

```plantuml
@startuml
title angelow_cart - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "carts" as cart_carts {
  * id : int <<PK>>
  --
  user_id : varchar(50) <<NULL>>
  session_id : varchar <<NULL>>
  created_at : timestamp
  updated_at : timestamp
}

entity "cart_items" as cart_items {
  * id : int <<PK>>
  --
  cart_id : int
  product_id : int
  color_variant_id : int <<NULL>>
  size_variant_id : int <<NULL>>
  quantity : int
  created_at : timestamp
  updated_at : timestamp
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

cart_carts ||--o{ cart_items : cart_id
cart_carts }o..|| ext_users : user_id
cart_items }o..|| ext_products : product_id
cart_items }o..|| ext_color_variants : color_variant_id
cart_items }o..|| ext_size_variants : size_variant_id

note right of cart_items
product_id, color_variant_id y size_variant_id
son referencias lógicas a catalog-service.
end note
@enduml
```

## Fuentes revisadas

- `services/cart-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/cart-service/app/Http/Controllers/CartController.php`
- `services/cart-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
