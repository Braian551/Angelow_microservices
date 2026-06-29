# shipping-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_shipping`, responsable de métodos de envío, reglas de precio y direcciones del cliente. El contrato vigente de direcciones mantiene alias, datos del destinatario, barrio, tipo de vivienda y geolocalización opcional; el servicio normaliza campos equivalentes cuando recibe datos con nombres distintos.

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

shp_methods ||..o{ shp_rules : cálculo de costo
shp_addresses }o..|| ext_users : user_id

note right of shp_addresses
user_id referencia de forma lógica
a auth-service.
end note
@enduml
```

## Fuentes revisadas

- `services/shipping-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/shipping-service/database/migrations/2026_03_31_000001_create_user_addresses_table.php`
- `services/shipping-service/app/Models/UserAddress.php`
- `services/shipping-service/app/Http/Controllers/ShippingController.php`
- `services/shipping-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
