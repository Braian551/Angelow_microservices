# Modelo relacional por base de datos en PlantUML

## 1) Base de datos angelow_auth (auth-service)

```plantuml
@startuml
title angelow_auth - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "users" as auth_users {
  * id : varchar(20) <<PK>>
  --
  name : varchar(100)
  email : varchar(100) <<UQ>>
  role : enum(customer, admin)
  is_blocked : boolean
}

entity "access_tokens" as auth_access_tokens {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  token : varchar(255)
  expires_at : timestamp
  is_revoked : boolean
}

entity "google_auth" as auth_google_auth {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  google_id : varchar(255) <<UQ>>
  access_token : varchar(255)
}

entity "password_resets" as auth_password_resets {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  token : varchar(255)
  expires_at : timestamp
  is_used : boolean
}

entity "sessions" as auth_sessions {
  * id : varchar(255) <<PK>>
  --
  user_id : varchar(20) <<NULL>>
  ip_address : varchar(45)
  last_activity : int
}

entity "login_attempts" as auth_login_attempts {
  * id : int <<PK>>
  --
  username : varchar(255)
  ip_address : varchar(45)
  attempt_date : timestamp
}

entity "personal_access_tokens" as auth_personal_tokens {
  * id : bigint <<PK>>
  --
  tokenable_type : varchar
  tokenable_id : varchar(20)
  token : varchar(64) <<UQ>>
  expires_at : timestamp
}

auth_users ||--o{ auth_access_tokens : user_id
auth_users ||--o{ auth_google_auth : user_id
auth_users ||--o{ auth_password_resets : user_id
auth_users ||--o{ auth_sessions : user_id
auth_users ||--o{ auth_personal_tokens : tokenable_id
@enduml
```

## 2) Base de datos angelow_catalog (catalog-service)

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
  is_active : boolean
}

entity "collections" as cat_collections {
  * id : int <<PK>>
  --
  name : varchar(100)
  slug : varchar(100) <<UQ>>
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
  price : decimal(10,2)
  is_active : boolean
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
  quantity : int
  is_active : boolean
}

entity "product_images" as cat_product_images {
  * id : int <<PK>>
  --
  product_id : int
  color_variant_id : int <<NULL>>
  image_path : varchar(255)
  is_primary : boolean
}

entity "variant_images" as cat_variant_images {
  * id : int <<PK>>
  --
  color_variant_id : int
  product_id : int
  image_id : int <<NULL>>
  image_path : varchar(255)
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
  is_seller : boolean
}

entity "popular_searches" as cat_popular_searches {
  * id : int <<PK>>
  --
  search_term : varchar(255) <<UQ>>
  search_count : int
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
  category : varchar(40)
  updated_by : varchar(64)
}

entity "sliders" as cat_sliders {
  * id : int <<PK>>
  --
  title : varchar(255)
  image : varchar(500)
  order_position : int
  is_active : boolean
}

entity "announcements" as cat_announcements {
  * id : int <<PK>>
  --
  type : varchar(12)
  title : varchar(255)
  priority : int
  is_active : boolean
}

entity "stock_history" as cat_stock_history {
  * id : int <<PK>>
  --
  variant_id : int
  user_id : varchar(20)
  operation : varchar(12)
  created_at : timestamp
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
@enduml
```

## 3) Base de datos angelow_cart (cart-service)

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
}

cart_carts ||--o{ cart_items : cart_id
@enduml
```

## 4) Base de datos angelow_orders (order-service)

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
  user_id : varchar(20) <<NULL>>
  status : varchar(20)
  payment_status : varchar(20)
  shipping_method_id : int <<NULL>>
  shipping_address_id : int <<NULL>>
  total : decimal(10,2)
}

entity "order_items" as ord_items {
  * id : int <<PK>>
  --
  order_id : int
  product_id : int
  color_variant_id : int <<NULL>>
  size_variant_id : int <<NULL>>
  quantity : int
  total : decimal(10,2)
}

entity "order_status_history" as ord_history {
  * id : int <<PK>>
  --
  order_id : int
  changed_by : varchar(20) <<NULL>>
  change_type : varchar(20)
  field_changed : varchar(100)
  created_at : timestamp
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
}

ord_orders ||--o{ ord_items : order_id
ord_orders ||--o{ ord_history : order_id
ord_orders ||--o{ ord_views : order_id
ord_orders ||--o{ ord_reservations : order_id
@enduml
```

## 5) Base de datos angelow_payments (payment-service)

```plantuml
@startuml
title angelow_payments - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "colombian_banks" as pay_banks {
  * id : int <<PK>>
  --
  bank_code : varchar(10) <<UQ>>
  bank_name : varchar(100)
  is_active : boolean
}

entity "bank_account_config" as pay_account_config {
  * id : int <<PK>>
  --
  bank_code : varchar(10)
  account_number : varchar(50)
  account_type : varchar(20)
  account_holder : varchar(100)
  identification_number : varchar(20)
  is_active : boolean
}

entity "payment_transactions" as pay_transactions {
  * id : int <<PK>>
  --
  order_id : int <<NULL>>
  user_id : varchar(20) <<NULL>>
  amount : decimal(10,2)
  status : varchar(20)
  verified_by : varchar(20) <<NULL>>
  verified_at : timestamp <<NULL>>
}

pay_banks ||--o{ pay_account_config : bank_code
@enduml
```

## 6) Base de datos angelow_discounts (discount-service)

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
  is_active : boolean
}

entity "discount_codes" as dis_codes {
  * id : int <<PK>>
  --
  code : varchar(20) <<UQ>>
  discount_type_id : int
  discount_value : decimal(10,2)
  max_uses : int <<NULL>>
  used_count : int
  is_active : boolean
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
  max_discount_amount : decimal(10,2) <<NULL>>
}

entity "fixed_amount_discounts" as dis_fixed {
  * id : int <<PK>>
  --
  discount_code_id : int
  amount : decimal(10,2)
  min_order_amount : decimal(10,2) <<NULL>>
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
  is_used : boolean
}

dis_types ||--o{ dis_codes : discount_type_id
dis_codes ||--o{ dis_code_products : discount_code_id
dis_codes ||--o{ dis_code_usage : discount_code_id
dis_codes ||--o{ dis_percentage : discount_code_id
dis_codes ||--o{ dis_fixed : discount_code_id
dis_codes ||--o{ dis_free_shipping : discount_code_id
dis_codes ||--o{ dis_user_applied : discount_code_id
@enduml
```

## 7) Base de datos angelow_shipping (shipping-service)

```plantuml
@startuml
title angelow_shipping - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "shipping_methods" as shp_methods {
  * id : int <<PK>>
  --
  name : varchar(100)
  base_cost : decimal(10,2)
  free_shipping_threshold : decimal(10,2) <<NULL>>
  estimated_days_min : int
  estimated_days_max : int
  is_active : boolean
}

entity "shipping_price_rules" as shp_rules {
  * id : int <<PK>>
  --
  min_price : decimal(10,2)
  max_price : decimal(10,2) <<NULL>>
  shipping_cost : decimal(10,2)
  is_active : boolean
}

entity "user_addresses" as shp_addresses {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  alias : varchar(50)
  recipient_name : varchar(100)
  recipient_phone : varchar(15)
  address : varchar(255)
  city : varchar(100)
  is_default : boolean
  is_active : boolean
}
@enduml
```

## 8) Base de datos angelow_notifications (notification-service)

```plantuml
@startuml
title angelow_notifications - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "notification_types" as not_types {
  * id : int <<PK>>
  --
  name : varchar(50)
  description : varchar(255)
  is_active : boolean
}

entity "notifications" as not_notifications {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  type_id : int
  title : varchar(100)
  is_read : boolean
  is_email_sent : boolean
  expires_at : timestamp <<NULL>>
}

entity "notification_preferences" as not_preferences {
  * id : int <<PK>>
  --
  user_id : varchar(20)
  type_id : int
  email_enabled : boolean
  sms_enabled : boolean
  push_enabled : boolean
}

entity "notification_queue" as not_queue {
  * id : int <<PK>>
  --
  notification_id : int
  channel : varchar(10)
  status : varchar(20)
  attempts : smallint
  scheduled_at : timestamp
}

entity "admin_notification_dismissals" as not_dismissals {
  * id : int <<PK>>
  --
  admin_id : varchar(20)
  notification_key : varchar(120)
  dismissed_at : timestamp
}

entity "announcements" as not_announcements {
  * id : int <<PK>>
  --
  type : varchar(30)
  title : varchar(150)
  priority : int
  is_active : boolean
  start_date : timestamp <<NULL>>
  end_date : timestamp <<NULL>>
}

not_types ||--o{ not_notifications : type_id
not_types ||--o{ not_preferences : type_id
not_notifications ||--o{ not_queue : notification_id
@enduml
```

## 9) Base de datos angelow_audit (audit-service)

```plantuml
@startuml
title angelow_audit - Modelo relacional
left to right direction
hide circle
skinparam linetype ortho

entity "audit_categories" as aud_categories {
  * audit_id : int <<PK>>
  --
  category_id : int <<NULL>>
  action_type : varchar(10)
  old_name : varchar(100)
  new_name : varchar(100)
  action_date : timestamp
}

entity "audit_orders" as aud_orders {
  * id : int <<PK>>
  --
  orden_id : int
  accion : varchar(10)
  usuario_id : varchar(20) <<NULL>>
  fecha : timestamp
}

entity "audit_users" as aud_users {
  * id : int <<PK>>
  --
  usuario_id : varchar(20)
  accion : varchar(10)
  usuario_modificador : varchar(20) <<NULL>>
  fecha : timestamp
}

entity "productos_auditoria" as aud_products {
  * id : int <<PK>>
  --
  nombre : varchar(100)
  accion : varchar(50)
  created_at : timestamp
}

entity "eliminaciones_auditoria" as aud_deletions {
  * id : int <<PK>>
  --
  nombre : varchar(100)
  accion : varchar(50)
  fecha_eliminacion : timestamp
}
@enduml
```

## 10) Referencias lógicas entre bases de datos

```plantuml
@startuml
title Referencias lógicas entre dominios de datos
left to right direction
skinparam componentStyle rectangle
skinparam shadowing false

database "angelow_auth\nusers(id)" as db_auth
database "angelow_catalog\nproducts, variants" as db_catalog
database "angelow_cart\ncarts, cart_items" as db_cart
database "angelow_orders\norders, order_items, stock_reservations" as db_orders
database "angelow_payments\npayment_transactions" as db_payments
database "angelow_discounts\ndiscount_codes, usage" as db_discounts
database "angelow_shipping\nshipping_methods, user_addresses" as db_shipping
database "angelow_notifications\nnotifications, preferences" as db_notifications
database "angelow_audit\naudit_*" as db_audit

db_auth --> db_cart : user_id
db_auth --> db_orders : user_id / changed_by
db_auth --> db_payments : user_id / verified_by
db_auth --> db_discounts : user_id / created_by
db_auth --> db_shipping : user_id
db_auth --> db_notifications : user_id / admin_id
db_auth --> db_catalog : user_id (wishlist, reseñas, preguntas)

db_catalog --> db_cart : product_id / variantes
db_catalog --> db_orders : product_id / variantes
db_catalog --> db_discounts : product_id

db_shipping --> db_orders : shipping_method_id / shipping_address_id
db_orders --> db_payments : order_id
db_orders --> db_discounts : order_id

note bottom
Estas referencias son lógicas de integración entre servicios.
No dependen de llaves foráneas físicas entre bases de datos distintas.
end note
@enduml
```
