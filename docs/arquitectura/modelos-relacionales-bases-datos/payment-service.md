# payment-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_payments`, responsable del catálogo de bancos colombianos, cuenta bancaria activa y transacciones de pago manual con comprobante.

## Diagrama

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
  identification_type : varchar(10)
  identification_number : varchar(20)
  email : varchar(100)
  phone : varchar(15)
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
  admin_notes : text
  verified_by : varchar(20)
  verified_at : timestamp
}

pay_banks ||--o{ pay_account_config : bank_code
pay_account_config ||..o{ pay_transactions : cuenta activa usada

note right of pay_transactions
order_id, user_id y verified_by
son referencias lógicas a otros servicios.
end note
@enduml
```

## Fuentes revisadas

- `services/payment-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/payment-service/database/migrations/2026_04_18_000001_seed_colombian_banks_catalog.php`
- `services/payment-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
