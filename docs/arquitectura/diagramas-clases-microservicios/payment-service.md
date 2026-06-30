# payment-service - Diagrama de clases en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Diagrama del microservicio de pagos, transacciones, bancos colombianos, comprobantes y cuenta bancaria activa para pagos manuales. Incluye atributos persistentes y relaciones con multiplicidad entre bancos, cuentas configuradas, usuarios y transacciones.

## Diagrama

```plantuml
@startuml
title payment-service - Pagos, bancos y cuenta activa
left to right direction
skinparam classAttributeIconSize 0
skinparam packageStyle rectangle

package "Controladores HTTP" {
  class "PaymentController" as PaymentController <<Controller>> {
    -transactionsTable: payment_transactions
    -banksTable: colombian_banks
    -accountConfigTable: bank_account_config
    +index(request)
    +store(request)
    +verify(request, id)
    +banks()
    +paymentAccount()
  }
  class "AdminPaymentController" as PaymentAdminController <<Controller>> {
    -transactionsTable: payment_transactions
    -banksTable: colombian_banks
    -accountConfigTable: bank_account_config
    +index(request)
    +verify(request, id)
    +accountSettings()
    +saveAccountSettings(request)
  }
  class "HealthController" as PaymentHealthController <<Controller>> {
    +__invoke()
  }
}

package "Persistencia Query Builder" {
  class "User" as PaymentUser <<Model>> {
    +id: Integer
    +name: String
    +email: String
    -password: String
    +email_verified_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "payment_transactions" as PaymentTransactionsTable <<Tabla>> {
    +id: Integer
    +order_id: Integer
    +user_id: String
    +amount: Decimal
    +reference_number: String
    +payment_proof: String
    +status: String
    +admin_notes: Text
    +verified_by: String
    +verified_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "colombian_banks" as PaymentBanksTable <<Tabla>> {
    +id: Integer
    +bank_code: String
    +bank_name: String
    +is_active: Boolean
  }
  class "bank_account_config" as PaymentAccountConfigTable <<Tabla>> {
    +id: Integer
    +bank_code: String
    +account_number: String
    +account_type: String
    +account_holder: String
    +identification_type: String
    +identification_number: String
    +email: String
    +phone: String
    +is_active: Boolean
    +created_by: String
    +created_at: DateTime
    +updated_at: DateTime
  }
}

package "Soporte Laravel" {
  class "EnsureAdmin" as PaymentEnsureAdmin <<Middleware>> {
    +handle(request, next)
  }
  class "AppServiceProvider" as PaymentAppServiceProvider <<Provider>> {
    +register()
    +boot()
  }
  class "RepositoryServiceProvider" as PaymentRepositoryServiceProvider <<Provider>> {
    +register()
    +boot()
  }
}

package "Servicios externos" {
  class "auth-service" as PaymentAuthExternal <<External>>
  class "order-service" as PaymentOrderExternal <<External>>
  class "legacy_mysql" as PaymentLegacyExternal <<External>>
  class "API pública de bancos" as PaymentBanksExternal <<External>>
  class "/uploads/payment-proofs" as PaymentUploads <<Storage>>
}

PaymentController --> PaymentTransactionsTable
PaymentController --> PaymentBanksTable
PaymentController --> PaymentAccountConfigTable
PaymentController --> PaymentUploads
PaymentController ..> PaymentBanksExternal : hidrata catálogo
PaymentController ..> PaymentLegacyExternal : fallback bancos/cuenta

PaymentAdminController --> PaymentTransactionsTable
PaymentAdminController --> PaymentBanksTable
PaymentAdminController --> PaymentAccountConfigTable
PaymentAdminController --> PaymentUploads
PaymentAdminController ..> PaymentAuthExternal : perfiles internos
PaymentAdminController ..> PaymentLegacyExternal : fallback administrativo
PaymentEnsureAdmin ..> PaymentAuthExternal : valida token admin

PaymentUser "1" o-- "0..*" PaymentTransactionsTable : realiza
PaymentOrderExternal "1" o-- "0..*" PaymentTransactionsTable : pagos
PaymentBanksTable "1" o-- "0..*" PaymentAccountConfigTable : configura
PaymentAccountConfigTable "1" o-- "0..*" PaymentTransactionsTable : cuenta activa
PaymentTransactionsTable "1" *-- "0..1" PaymentUploads : comprobante
@enduml
```

## Fuentes revisadas

- `services/payment-service/app/**/*.php`
- `services/payment-service/routes/api.php`
- `services/payment-service/database/migrations/*.php`

## Documentos relacionados

- [Índice de diagramas](../diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
