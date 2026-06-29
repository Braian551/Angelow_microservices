# Modelo relacional completo en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Leyenda](#leyenda)
- [Diagrama global](#diagrama-global)
- [Criterios de lectura](#criterios-de-lectura)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Este documento consolida en un solo UML las tablas de negocio de las bases de datos de Angelow por microservicio y sus relaciones internas o lógicas. Sirve como mapa maestro para revisar cómo se conectan usuarios, catálogo, carrito, pedidos, pagos, descuentos, envíos, notificaciones y auditoría.

Se omiten del diagrama principal las tablas técnicas repetidas de Laravel (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`) porque no representan entidades del modelo de negocio. También se omiten los campos `trial*` heredados para mantener el diagrama legible.

## Leyenda

- `||--o{`: relación interna dentro de la misma base o dominio.
- `||..o{`: relación lógica entre servicios o sin llave foránea física.
- `<<externa>>` no se usa en este diagrama porque todas las tablas de negocio aparecen en el mismo mapa.

## Diagrama global

```plantuml
@startuml
title Angelow - Modelo relacional completo por microservicio
left to right direction
hide circle
skinparam linetype ortho
skinparam packageStyle rectangle

package "angelow_auth (auth-service)" {
  entity "users" as auth_users {
    * id : varchar(20) <<PK>>
    --
    name : varchar(100)
    email : varchar(100) <<UQ>>
    phone : varchar(15)
    password : varchar(255)
    image : varchar(255)
    role : enum(customer, admin)
    is_blocked : boolean
    last_access : datetime
  }

  entity "access_tokens" as auth_access_tokens {
    * id : int <<PK>>
    --
    user_id : varchar(20)
    token : varchar(255)
    ip_address : varchar(45)
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
    payload : text
    last_activity : int
  }

  entity "login_attempts" as auth_login_attempts_legacy {
    * id : int <<PK>>
    --
    username : varchar(255)
    ip_address : varchar(45)
    attempt_date : timestamp
  }

  entity "auth_login_attempts" as auth_login_attempts {
    * id : bigint <<PK>>
    --
    credential : varchar(150)
    ip_address : varchar(45)
    failed_attempts : smallint
    blocked_until : timestamp
  }

  entity "personal_access_tokens" as auth_personal_tokens {
    * id : bigint <<PK>>
    --
    tokenable_type : varchar
    tokenable_id : varchar
    token : varchar(64) <<UQ>>
    expires_at : timestamp
  }
}

package "angelow_catalog (catalog-service)" {
  entity "categories" as cat_categories {
    * id : int <<PK>>
    --
    parent_id : int <<NULL>>
    name : varchar(100)
    slug : varchar(100) <<UQ>>
    image : varchar(255)
    is_active : boolean
  }

  entity "collections" as cat_collections {
    * id : int <<PK>>
    --
    name : varchar(100)
    slug : varchar(100) <<UQ>>
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
    gender : varchar(20)
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
    quantity : int
    is_active : boolean
  }

  entity "product_images" as cat_product_images {
    * id : int <<PK>>
    --
    product_id : int
    color_variant_id : int <<NULL>>
    image_path : varchar(255)
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
    order : int
    is_primary : boolean
  }

  entity "wishlist" as cat_wishlist {
    * id : int <<PK>>
    --
    user_id : varchar(20)
    product_id : int
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
  }

  entity "inventory_alerts" as cat_inventory_alerts {
    * id : int <<PK>>
    --
    variant_id : int <<UQ>>
    product_id : int <<NULL>>
    product_name : varchar(255)
    stock : int
    status : varchar(20)
    out_of_stock_since : timestamp
    resolved_at : timestamp
  }
}

package "angelow_cart (cart-service)" {
  entity "carts" as cart_carts {
    * id : int <<PK>>
    --
    user_id : varchar(50) <<NULL>>
    session_id : varchar <<NULL>>
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
}

package "angelow_orders (order-service)" {
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
  }

  entity "order_items" as ord_items {
    * id : int <<PK>>
    --
    order_id : int
    product_id : int
    color_variant_id : int <<NULL>>
    size_variant_id : int <<NULL>>
    product_name : varchar(255)
    price : decimal(10,2)
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
    description : text
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
  }

  entity "order_refund_requests" as ord_refunds {
    * id : int <<PK>>
    --
    order_id : int
    user_id : varchar(40)
    user_email : varchar(255)
    reason : varchar(80)
    evidence_path : varchar(500)
    status : varchar(24)
    requested_at : timestamp
    resolved_at : timestamp
  }
}

package "angelow_payments (payment-service)" {
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
    created_by : varchar(20)
  }

  entity "payment_transactions" as pay_transactions {
    * id : int <<PK>>
    --
    order_id : int <<NULL>>
    user_id : varchar(20) <<NULL>>
    amount : decimal(10,2)
    reference_number : varchar(50)
    payment_proof : varchar(255)
    status : varchar(20)
    verified_by : varchar(20)
    verified_at : timestamp
  }
}

package "angelow_discounts (discount-service)" {
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
    expires_at : timestamp
    is_used : boolean
  }
}

package "angelow_shipping (shipping-service)" {
  entity "shipping_methods" as shp_methods {
    * id : int <<PK>>
    --
    name : varchar(100)
    base_cost : decimal(10,2)
    delivery_time : varchar(50)
    free_shipping_threshold : decimal(10,2)
    estimated_days_min : int
    estimated_days_max : int
    city : varchar(100)
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
    address_type : varchar(20)
    alias : varchar(50)
    recipient_name : varchar(100)
    recipient_phone : varchar(15)
    address : varchar(255)
    neighborhood : varchar(100)
    building_type : varchar(20)
    gps_latitude : decimal(10,8)
    gps_longitude : decimal(11,8)
    is_default : boolean
    is_active : boolean
  }
}

package "angelow_notifications (notification-service)" {
  entity "notification_types" as not_types {
    * id : int <<PK>>
    --
    name : varchar(50)
    description : varchar(255)
    template : text
    is_active : boolean
  }

  entity "notifications" as not_notifications {
    * id : int <<PK>>
    --
    user_id : varchar(20)
    type_id : int
    title : varchar(100)
    message : text
    related_entity_type : varchar(30)
    related_entity_id : int
    is_read : boolean
    is_email_sent : boolean
    expires_at : timestamp
    read_at : timestamp
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
    sent_at : timestamp
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
    message : text
    priority : int
    is_active : boolean
    start_date : timestamp
    end_date : timestamp
  }
}

package "angelow_audit (audit-service)" {
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
    detalles : text
  }

  entity "audit_users" as aud_users {
    * id : int <<PK>>
    --
    usuario_id : varchar(20)
    accion : varchar(10)
    usuario_modificador : varchar(20) <<NULL>>
    detalles : text
  }

  entity "productos_auditoria" as aud_products {
    * id : int <<PK>>
    --
    nombre : varchar(100)
    accion : varchar(50)
  }

  entity "eliminaciones_auditoria" as aud_deletions {
    * id : int <<PK>>
    --
    nombre : varchar(100)
    accion : varchar(50)
    fecha_eliminacion : timestamp
  }
}

' Relaciones internas de auth
auth_users ||--o{ auth_access_tokens : user_id
auth_users ||--o{ auth_google_auth : user_id
auth_users ||--o{ auth_password_resets : user_id
auth_users ||--o{ auth_sessions : user_id
auth_users ||--o{ auth_personal_tokens : tokenable_id
auth_users ||..o{ auth_login_attempts_legacy : username/email
auth_users ||..o{ auth_login_attempts : credential

' Relaciones internas de catálogo
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
cat_popular_searches ||..o{ cat_search_history : search_term
cat_site_settings ||..o{ cat_sliders : configuración visual
cat_site_settings ||..o{ cat_announcements : configuración visible

' Relaciones internas de carrito, órdenes, pagos, descuentos, envíos y notificaciones
cart_carts ||--o{ cart_items : cart_id
ord_orders ||--o{ ord_items : order_id
ord_orders ||--o{ ord_history : order_id
ord_orders ||--o{ ord_views : order_id
ord_orders ||--o{ ord_reservations : order_id
ord_orders ||--o{ ord_refunds : order_id
pay_banks ||--o{ pay_account_config : bank_code
pay_account_config ||..o{ pay_transactions : cuenta activa usada
dis_types ||--o{ dis_codes : discount_type_id
dis_types ||..o{ dis_bulk_rules : reglas por cantidad
dis_codes ||--o{ dis_code_products : discount_code_id
dis_codes ||--o{ dis_code_usage : discount_code_id
dis_codes ||--o{ dis_percentage : discount_code_id
dis_codes ||--o{ dis_fixed : discount_code_id
dis_codes ||--o{ dis_free_shipping : discount_code_id
dis_codes ||--o{ dis_user_applied : discount_code_id
shp_methods ||..o{ shp_rules : cálculo de costo
not_types ||--o{ not_notifications : type_id
not_types ||--o{ not_preferences : type_id
not_notifications ||--o{ not_queue : notification_id
not_types ||..o{ not_announcements : evento visible

' Relaciones lógicas transversales por identidad, catálogo, pedidos y operación
auth_users ||..o{ cat_wishlist : user_id
auth_users ||..o{ cat_reviews : user_id
auth_users ||..o{ cat_review_votes : user_id
auth_users ||..o{ cat_questions : user_id
auth_users ||..o{ cat_answers : user_id
auth_users ||..o{ cat_search_history : user_id
auth_users ||..o{ cat_stock_history : user_id
auth_users ||..o{ cat_site_settings : updated_by
auth_users ||..o{ cart_carts : user_id
auth_users ||..o{ ord_orders : user_id
auth_users ||..o{ ord_history : changed_by
auth_users ||..o{ ord_views : user_id
auth_users ||..o{ ord_refunds : user_id
auth_users ||..o{ pay_account_config : created_by
auth_users ||..o{ pay_transactions : user_id / verified_by
auth_users ||..o{ dis_codes : created_by
auth_users ||..o{ dis_code_usage : user_id
auth_users ||..o{ dis_user_applied : user_id
auth_users ||..o{ shp_addresses : user_id
auth_users ||..o{ not_notifications : user_id
auth_users ||..o{ not_preferences : user_id
auth_users ||..o{ not_dismissals : admin_id
auth_users ||..o{ aud_orders : usuario_id
auth_users ||..o{ aud_users : usuario_id / modificador

cat_products ||..o{ cart_items : product_id
cat_color_variants ||..o{ cart_items : color_variant_id
cat_size_variants ||..o{ cart_items : size_variant_id
cat_products ||..o{ ord_items : product_id
cat_color_variants ||..o{ ord_items : color_variant_id
cat_size_variants ||..o{ ord_items : size_variant_id
cat_products ||..o{ ord_reservations : product_id
cat_size_variants ||..o{ ord_reservations : size_variant_id
cat_products ||..o{ dis_code_products : product_id
cat_categories ||..o{ aud_categories : category_id
cat_products ||..o{ aud_products : nombre/producto
cat_products ||..o{ aud_deletions : nombre/producto eliminado

ord_orders ||..o{ cat_reviews : order_id
ord_orders ||..o{ pay_transactions : order_id
ord_orders ||..o{ dis_code_usage : order_id
ord_orders ||..o{ aud_orders : orden_id
ord_orders ||..o{ not_notifications : related_entity_id
ord_refunds ||..o{ not_notifications : related_entity_id

shp_methods ||..o{ ord_orders : shipping_method_id
shp_addresses ||..o{ ord_orders : shipping_address_id
shp_methods ||..o{ dis_free_shipping : shipping_method_id

pay_transactions ||..o{ not_notifications : eventos de pago
dis_codes ||..o{ not_notifications : campañas y descuentos
cat_inventory_alerts ||..o{ not_notifications : alertas de inventario

note bottom
Las relaciones punteadas representan integración lógica entre bases distribuidas.
No implican llaves foráneas físicas entre microservicios.
end note
@enduml
```

## Criterios de lectura

- Cada paquete representa una base de datos propiedad de un microservicio.
- Las tablas de negocio aparecen una sola vez, incluso cuando otros servicios las referencian por identificador.
- Las relaciones internas muestran dependencias directas por columnas del propio dominio.
- Las relaciones transversales muestran contratos por identificadores compartidos, llamadas internas, eventos o sincronización funcional.
- `realtime-gateway` no aparece como paquete relacional porque no tiene tablas persistentes; su conexión se documenta en [realtime-gateway](modelos-relacionales-bases-datos/realtime-gateway.md).

## Fuentes revisadas

- `services/*/database/migrations/*.php`
- `services/*/app/Models/*.php`
- `services/*/routes/api.php`
- `services/realtime-gateway/server.js`
- [Modelos relacionales separados](modelos-relacionales-bases-datos-plantuml.md)

## Documentos relacionados

- [Modelos relacionales por microservicio](modelos-relacionales-bases-datos-plantuml.md)
- [Referencias lógicas entre bases de datos](modelos-relacionales-bases-datos/referencias-logicas.md)
- [Diagramas de clases por microservicio](diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../microservicios/README.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
