# catalog-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_catalog`, responsable de catálogo, categorías, colecciones, variantes, imágenes, favoritos, reseñas, preguntas, búsquedas, configuración del sitio, sliders, anuncios, inventario y alertas de stock. Se actualiza con la política de reembolso por producto y las alertas de inventario agregadas al proyecto.

## Diagrama

```plantuml
@startuml
title angelow_catalog - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "categories" as cat_categories {
  * id : int <<PK>>
  --
  parent_id : int <<NULL>>
  name : varchar(100)
  slug : varchar(100) <<UQ>>
  description : text
  image : varchar(255)
  is_active : boolean
}

entity "collections" as cat_collections {
  * id : int <<PK>>
  --
  name : varchar(100)
  slug : varchar(100) <<UQ>>
  description : text
  image : varchar(255)
  launch_date : date
  is_active : boolean
}

entity "colors" as cat_colors {
  * id : int <<PK>>
  --
  name : varchar(50)
  hex_code : varchar(7)
  is_active : boolean
}

entity "sizes" as cat_sizes {
  * id : int <<PK>>
  --
  name : varchar(50)
  description : varchar(100)
  is_active : boolean
}

entity "products" as cat_products {
  * id : int <<PK>>
  --
  category_id : int
  collection_id : int <<NULL>>
  name : varchar(255)
  slug : varchar(255) <<UQ>>
  description : text
  brand : varchar(100)
  gender : varchar(20)
  material : varchar(100)
  compare_price : decimal(10,2)
  price : decimal(10,2)
  is_featured : boolean
  is_active : boolean
  is_refundable : boolean
  refund_days : smallint
}

entity "product_collections" as cat_product_collections {
  * id : int <<PK>>
  --
  product_id : int
  collection_id : int
  display_order : int
}

entity "product_color_variants" as cat_color_variants {
  * id : int <<PK>>
  --
  product_id : int
  color_id : int <<NULL>>
  is_default : boolean
}

entity "product_size_variants" as cat_size_variants {
  * id : int <<PK>>
  --
  color_variant_id : int
  size_id : int <<NULL>>
  sku : varchar(50)
  barcode : varchar(50)
  price : decimal(10,2)
  compare_price : decimal(10,2)
  quantity : int
  is_active : boolean
}

entity "product_images" as cat_product_images {
  * id : int <<PK>>
  --
  product_id : int
  color_variant_id : int <<NULL>>
  image_path : varchar(255)
  alt_text : varchar(255)
  order : int
  is_primary : boolean
}

entity "variant_images" as cat_variant_images {
  * id : int <<PK>>
  --
  color_variant_id : int
  product_id : int
  image_id : int <<NULL>>
  image_path : varchar(255)
  alt_text : varchar(255)
  order : int
  is_primary : boolean
}

entity "wishlist" as cat_wishlist {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  product_id : int
  created_at : timestamp
}

entity "product_reviews" as cat_reviews {
  * id : int <<PK>>
  --
  product_id : int
  user_id : varchar(20)
  order_id : int <<NULL>>
  rating : smallint
  title : varchar(100)
  comment : text
  images : text
  is_verified : boolean
  is_approved : boolean
}

entity "review_votes" as cat_review_votes {
  * id : int <<PK>>
  --
  review_id : int
  user_id : varchar(20)
  is_helpful : boolean
}

entity "product_questions" as cat_questions {
  * id : int <<PK>>
  --
  product_id : int
  user_id : varchar(20)
  question : text
}

entity "question_answers" as cat_answers {
  * id : int <<PK>>
  --
  question_id : int
  user_id : varchar(20)
  answer : text
  is_seller : boolean
}

entity "popular_searches" as cat_popular_searches {
  * id : int <<PK>>
  --
  search_term : varchar(255) <<UQ>>
  search_count : int
  last_searched : timestamp
}

entity "search_history" as cat_search_history {
  * id : int <<PK>>
  --
  user_id : varchar(20) <<NULL>>
  search_term : varchar(255)
  created_at : timestamp
}

entity "site_settings" as cat_site_settings {
  * id : int <<PK>>
  --
  setting_key : varchar(120) <<UQ>>
  setting_value : text
  category : varchar(40)
  updated_by : varchar(64)
}

entity "sliders" as cat_sliders {
  * id : int <<PK>>
  --
  title : varchar(255)
  subtitle : varchar(255)
  image : varchar(500)
  link : varchar(500)
  order_position : int
  is_active : boolean
}

entity "announcements" as cat_announcements {
  * id : int <<PK>>
  --
  type : varchar(12)
  title : varchar(255)
  message : text
  priority : int
  is_active : boolean
  start_date : timestamp
  end_date : timestamp
}

entity "stock_history" as cat_stock_history {
  * id : int <<PK>>
  --
  variant_id : int
  user_id : varchar(20)
  previous_qty : int
  new_qty : int
  operation : varchar(12)
  notes : text
}

entity "inventory_alerts" as cat_inventory_alerts {
  * id : int <<PK>>
  --
  variant_id : int <<UQ>>
  product_id : int <<NULL>>
  product_name : varchar(255)
  color_name : varchar(120)
  size_label : varchar(120)
  sku : varchar(80)
  stock : int
  status : varchar(20)
  out_of_stock_since : timestamp
  resolved_at : timestamp
}

entity "auth.users" as ext_auth_users <<externa>> {
  * id : varchar(20) <<PK>>
}

entity "orders.orders" as ext_orders <<externa>> {
  * id : int <<PK>>
}

cat_categories ||--o{ cat_categories : parent_id
cat_categories ||--o{ cat_products : category_id
cat_collections ||--o{ cat_products : collection_id
cat_products ||--o{ cat_product_collections : product_id
cat_collections ||--o{ cat_product_collections : collection_id
cat_products ||--o{ cat_color_variants : product_id
cat_colors ||--o{ cat_color_variants : color_id
cat_color_variants ||--o{ cat_size_variants : color_variant_id
cat_sizes ||--o{ cat_size_variants : size_id
cat_products ||--o{ cat_product_images : product_id
cat_color_variants ||--o{ cat_product_images : color_variant_id
cat_products ||--o{ cat_variant_images : product_id
cat_color_variants ||--o{ cat_variant_images : color_variant_id
cat_product_images ||--o{ cat_variant_images : image_id
cat_products ||--o{ cat_wishlist : product_id
cat_products ||--o{ cat_reviews : product_id
cat_reviews ||--o{ cat_review_votes : review_id
cat_products ||--o{ cat_questions : product_id
cat_questions ||--o{ cat_answers : question_id
cat_size_variants ||--o{ cat_stock_history : variant_id
cat_size_variants ||--o{ cat_inventory_alerts : variant_id
cat_products ||--o{ cat_inventory_alerts : product_id
cat_search_history }o..|| ext_auth_users : user_id
cat_wishlist }o..|| ext_auth_users : user_id
cat_reviews }o..|| ext_auth_users : user_id
cat_questions }o..|| ext_auth_users : user_id
cat_answers }o..|| ext_auth_users : user_id
cat_review_votes }o..|| ext_auth_users : user_id
cat_stock_history }o..|| ext_auth_users : user_id
cat_site_settings }o..|| ext_auth_users : updated_by
cat_reviews }o..|| ext_orders : order_id
cat_popular_searches ||..o{ cat_search_history : search_term
cat_site_settings ||..o{ cat_sliders : configuración visual
cat_site_settings ||..o{ cat_announcements : configuración visible
@enduml
```

## Fuentes revisadas

- `services/catalog-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/catalog-service/database/migrations/2026_05_10_000001_create_inventory_alerts_table.php`
- `services/catalog-service/database/migrations/2026_06_19_000001_add_refund_policy_to_products_table.php`
- `services/catalog-service/app/Models/*.php`
- `services/catalog-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
