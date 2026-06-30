# shipping-service - Diagrama de clases en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Diagrama del microservicio de métodos de envío, reglas de precio, estimación de costos y direcciones de usuario con fallback a datos heredados durante la migración. Incluye atributos de modelos y relaciones con multiplicidad.

## Diagrama

```plantuml
@startuml
title shipping-service - Métodos, reglas y direcciones
left to right direction
skinparam classAttributeIconSize 0
skinparam packageStyle rectangle

package "Controladores HTTP" {
  class "ShippingController" as ShippingController <<Controller>> {
    -methodModel: ShippingMethod
    -priceRuleModel: ShippingPriceRule
    -addressModel: UserAddress
    +methods(request)
    +rules()
    +estimate(request)
    +userAddresses(request)
    +createUserAddress(request)
    +updateUserAddress(request, addressId)
    +deleteUserAddress(request, addressId)
    +setDefaultUserAddress(request, addressId)
  }
  class "AdminShippingController" as ShippingAdminController <<Controller>> {
    -methodModel: ShippingMethod
    -priceRuleModel: ShippingPriceRule
    +methods()
    +storeMethod(request)
    +updateMethod(request, id)
    +destroyMethod(id)
    +rules()
    +storeRule(request)
    +updateRule(request, id)
    +destroyRule(id)
  }
  class "HealthController" as ShippingHealthController <<Controller>> {
    +__invoke()
  }
}

package "Modelos" {
  class "ShippingMethod" as ShippingMethod <<Model>> {
    +id: Integer
    +name: String
    +description: Text
    +base_cost: Decimal
    +delivery_time: String
    +estimated_days_min: Integer
    +estimated_days_max: Integer
    +free_shipping_threshold: Decimal
    +free_shipping_minimum: Decimal
    +available_cities: Text
    +city: String
    +icon: String
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "ShippingPriceRule" as ShippingPriceRule <<Model>> {
    +id: Integer
    +min_price: Decimal
    +max_price: Decimal
    +shipping_cost: Decimal
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "UserAddress" as ShippingUserAddress <<Model>> {
    +id: Integer
    +user_id: String
    +address_type: String
    +alias: String
    +recipient_name: String
    +recipient_phone: String
    +address: String
    +complement: String
    +neighborhood: String
    +building_type: String
    +building_name: String
    +apartment_number: String
    +delivery_instructions: Text
    +is_default: Boolean
    +is_active: Boolean
    +gps_latitude: Decimal
    +gps_longitude: Decimal
    +gps_accuracy: Decimal
    +gps_timestamp: DateTime
    +gps_used: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "LegacyUserAddress" as ShippingLegacyUserAddress <<Model>> {
    +id: Integer
    +user_id: String
    +alias: String
    +recipient_name: String
    +recipient_phone: String
    +address: String
    +neighborhood: String
    +is_default: Boolean
    +is_active: Boolean
  }
  class "User" as ShippingUser <<Model>> {
    +id: Integer
    +name: String
    +email: String
    -password: String
    +email_verified_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
}

package "Soporte Laravel" {
  class "EnsureAdmin" as ShippingEnsureAdmin <<Middleware>> {
    +handle(request, next)
  }
  class "AppServiceProvider" as ShippingAppServiceProvider <<Provider>> {
    +register()
    +boot()
  }
  class "RepositoryServiceProvider" as ShippingRepositoryServiceProvider <<Provider>> {
    +register()
    +boot()
  }
}

package "Servicios externos" {
  class "auth-service" as ShippingAuthExternal <<External>>
  class "legacy_mysql" as ShippingLegacyExternal <<External>>
}

ShippingController --> ShippingMethod
ShippingController --> ShippingPriceRule
ShippingController --> ShippingUserAddress
ShippingController --> ShippingLegacyUserAddress
ShippingController --> ShippingUser
ShippingController ..> ShippingLegacyExternal : métodos, reglas y direcciones heredadas

ShippingAdminController --> ShippingMethod
ShippingAdminController --> ShippingPriceRule
ShippingAdminController ..> ShippingLegacyExternal : fallback administrativo
ShippingEnsureAdmin ..> ShippingAuthExternal : valida token admin

ShippingUser "1" *-- "0..*" ShippingUserAddress : direcciones
ShippingUser "1" o-- "0..*" ShippingLegacyUserAddress : direcciones heredadas
ShippingMethod "1" o-- "0..*" ShippingPriceRule : usa reglas
ShippingLegacyUserAddress ..> ShippingLegacyExternal : tabla heredada
@enduml
```

## Fuentes revisadas

- `services/shipping-service/app/**/*.php`
- `services/shipping-service/routes/api.php`
- `services/shipping-service/database/migrations/*.php`

## Documentos relacionados

- [Índice de diagramas](../diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
