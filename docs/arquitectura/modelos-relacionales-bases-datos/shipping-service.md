# shipping-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_shipping`, responsable de métodos y reglas de envío, direcciones del cliente, vinculación operativa de repartidores y ciclo de entregas. La identidad global continúa en `auth-service`; `user_id`, `customer_user_id`, `reviewed_by`, `order_id` y `shipping_method_id` son referencias lógicas entre dominios.

## Diagrama

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
  description : text
  base_cost : decimal(10,2)
  delivery_time : varchar(50)
  free_shipping_threshold : decimal(10,2)
  available_cities : text
  estimated_days_min : int
  estimated_days_max : int
  city : varchar(100)
  free_shipping_minimum : decimal(10,2)
  icon : varchar(50)
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
  complement : varchar(100)
  neighborhood : varchar(100)
  building_type : varchar(20)
  building_name : varchar(100)
  apartment_number : varchar(20)
  delivery_instructions : text
  gps_latitude : decimal(10,8)
  gps_longitude : decimal(11,8)
  gps_accuracy : decimal(10,2)
  gps_timestamp : timestamp
  gps_used : boolean
  is_default : boolean
  is_active : boolean
}

entity "auth.users" as ext_users <<externa>> {
  * id : varchar(20) <<PK>>
}

entity "courier_profiles" as couriers {
  * id : bigint <<PK>>
  --
  user_id : varchar(20) <<UQ, lógica>>
  email : varchar(100)
  document_type : varchar(20)
  document_number : text <<cifrado>>
  document_number_hash : char(64) <<UQ>>
  birth_date : date
  phone : varchar(15)
  address : varchar(180)
  status : varchar(20)
  rejection_reason : text <<NULL>>
  is_active : boolean
  terms_version : varchar(30)
  terms_accepted_at : timestamp
  reviewed_at : timestamp <<NULL>>
  reviewed_by : varchar(20) <<NULL, lógica>>
}

entity "courier_vehicles" as vehicles {
  * id : bigint <<PK>>
  courier_profile_id : bigint <<FK>>
  --
  type : varchar(20)
  make_id : varchar(40) <<NULL>>
  make_name : varchar(100) <<NULL>>
  model_id : varchar(40) <<NULL>>
  model_name : varchar(100) <<NULL>>
  color_name : varchar(60) <<NULL>>
  color_hex : varchar(7) <<NULL>>
  year : smallint unsigned <<NULL>>
  plate : varchar(12) <<NULL>>
  ownership_type : varchar(20) <<NULL>>
}

entity "courier_documents" as documents {
  * id : bigint <<PK>>
  courier_profile_id : bigint <<FK>>
  --
  type : varchar(40)
  path : varchar(255)
  expires_at : date <<NULL>>
  status : varchar(20)
  review_note : text <<NULL>>
}

entity "delivery_assignments" as deliveries {
  * id : bigint <<PK>>
  order_id : bigint <<UQ, lógica>>
  --
  courier_profile_id : bigint <<FK,NULL>>
  order_number : varchar(30) <<NULL>>
  order_source : varchar(20) <<NULL>>
  customer_user_id : varchar(40) <<NULL, lógica>>
  customer_email : varchar(100) <<NULL>>
  shipping_method_id : bigint <<NULL, lógica>>
  shipping_method_name : varchar(100) <<NULL>>
  delivery_time : varchar(80) <<NULL>>
  destination_address : text <<NULL>>
  destination_city : varchar(100) <<NULL>>
  destination_latitude : decimal(10,7) <<NULL>>
  destination_longitude : decimal(10,7) <<NULL>>
  status : varchar(20)
  delivery_code_hash : varchar(255) <<NULL>>
  delivery_code : text <<NULL, cifrado>>
  sharing_location : boolean
  accepted_at : timestamp <<NULL>>
  route_started_at : timestamp <<NULL>>
  arrived_at : timestamp <<NULL>>
  delivered_at : timestamp <<NULL>>
}

entity "courier_locations" as locations {
  * id : bigint <<PK>>
  * delivery_assignment_id : bigint <<FK>>
  --
  latitude : decimal(10,7)
  longitude : decimal(10,7)
  heading : decimal(6,2) <<NULL>>
  speed : decimal(8,2) <<NULL>>
  accuracy : decimal(8,2) <<NULL>>
  recorded_at : timestamp
}

shp_methods ||..o{ shp_rules : cálculo de costo
shp_addresses }o..|| ext_users : user_id
couriers }o..|| ext_users : user_id lógico
couriers ||--o{ vehicles : relación física sin UNIQUE
couriers ||--o{ documents
couriers |o--o{ deliveries
deliveries ||--o{ locations
shp_methods ||..o{ deliveries : shipping_method_id lógico

note right of shp_addresses
user_id referencia de forma lógica
a auth-service.
end note

note right of deliveries
order_id y shipping_method_id son referencias lógicas.
El destino se conserva como instantánea de la entrega;
no se persiste una FK física hacia user_addresses.
end note

note right of vehicles
El modelo operativo usa un vehículo por perfil,
pero la migración no declara UNIQUE sobre courier_profile_id.
end note
@enduml
```

## Fuentes revisadas

- `services/shipping-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/shipping-service/database/migrations/2026_03_31_000001_create_user_addresses_table.php`
- `services/shipping-service/database/migrations/2026_07_22_000001_create_courier_delivery_tables.php`
- `services/shipping-service/database/migrations/2026_07_25_000001_remove_unused_courier_profile_fields.php`
- `services/shipping-service/app/Models/UserAddress.php`
- `services/shipping-service/app/Models/CourierProfile.php`
- `services/shipping-service/app/Models/CourierVehicle.php`
- `services/shipping-service/app/Models/CourierDocument.php`
- `services/shipping-service/app/Models/DeliveryAssignment.php`
- `services/shipping-service/app/Models/CourierLocation.php`
- `services/shipping-service/app/Http/Controllers/ShippingController.php`
- `services/shipping-service/app/Http/Controllers/CourierController.php`
- `services/shipping-service/app/Http/Controllers/Admin/AdminCourierController.php`
- `services/shipping-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
