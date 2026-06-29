# discount-service - Diagrama de clases en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Diagrama del microservicio de códigos de descuento, validación de cupones, descuentos por cantidad y campañas administrativas por correo. Incluye atributos de modelos, tablas auxiliares y relaciones de composición para reglas específicas del cupón.

## Diagrama

```plantuml
@startuml
title discount-service - Códigos, campañas y descuentos por cantidad
left to right direction
skinparam classAttributeIconSize 0
skinparam packageStyle rectangle

package "Controladores HTTP" {
  class "DiscountController" as DiscountController <<Controller>> {
    -discountCodeModel: DiscountCode
    -bulkRuleModel: BulkDiscountRule
    +listCodes()
    +validateCode(request)
    +validateBulkDiscount(request)
  }
  class "AdminDiscountController" as DiscountAdminController <<Controller>> {
    -discountCodeModel: DiscountCode
    -discountTypeModel: DiscountType
    -bulkRuleModel: BulkDiscountRule
    +codes()
    +storeCode(request)
    +updateCode(request, id)
    +destroyCode(id)
    +campaignCustomers(request)
    +sendMassCampaign(request)
    +sendSpecificCampaign(request)
    +bulkDiscounts()
    +storeBulkDiscount(request)
    +updateBulkDiscount(request, id)
    +destroyBulkDiscount(id)
  }
  class "HealthController" as DiscountHealthController <<Controller>> {
    +__invoke()
  }
}

package "Modelos" {
  class "DiscountCode (discount_codes)" as DiscountCode <<Model>> {
    +id: Integer
    +code: String
    +discount_type_id: Integer
    +discount_value: Decimal
    +max_uses: Integer
    +used_count: Integer
    +start_date: DateTime
    +end_date: DateTime
    +is_active: Boolean
    +is_single_use: Boolean
    +created_by: String
    +created_at: DateTime
    +updated_at: DateTime
    +type()
  }
  class "DiscountType (discount_types)" as DiscountType <<Model>> {
    +id: Integer
    +name: String
    +description: String
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "BulkDiscountRule (bulk_discount_rules)" as DiscountBulkRule <<Model>> {
    +id: Integer
    +min_quantity: Integer
    +max_quantity: Integer
    +discount_percentage: Decimal
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "User" as DiscountUser <<Model>> {
    +id: Integer
    +name: String
    +email: String
    -password: String
    +email_verified_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
}

package "Tablas Query Builder" {
  class "discount_code_usage" as DiscountUsageTable <<Tabla>> {
    +id: Integer
    +discount_code_id: Integer
    +user_id: String
    +order_id: Integer
    +used_at: DateTime
  }
  class "discount_code_products" as DiscountProductsTable <<Tabla>> {
    +id: Integer
    +discount_code_id: Integer
    +product_id: Integer
    +created_at: DateTime
  }
  class "percentage_discounts" as DiscountPercentageTable <<Tabla>> {
    +id: Integer
    +discount_code_id: Integer
    +percentage: Decimal
    +max_discount_amount: Decimal
  }
  class "fixed_amount_discounts" as DiscountFixedAmountTable <<Tabla>> {
    +id: Integer
    +discount_code_id: Integer
    +amount: Decimal
    +min_order_amount: Decimal
  }
  class "free_shipping_discounts" as DiscountFreeShippingTable <<Tabla>> {
    +id: Integer
    +discount_code_id: Integer
    +shipping_method_id: Integer
  }
  class "user_applied_discounts" as DiscountUserAppliedTable <<Tabla>> {
    +id: Integer
    +user_id: String
    +discount_code_id: Integer
    +discount_code: String
    +discount_amount: Decimal
    +applied_at: DateTime
    +expires_at: DateTime
    +is_used: Boolean
    +used_at: DateTime
  }
}

package "Soporte Laravel" {
  class "DiscountPdfAttachmentHelper" as DiscountPdfHelper <<Support>> {
    +build(payload)
  }
  class "EnsureAdmin" as DiscountEnsureAdmin <<Middleware>> {
    +handle(request, next)
  }
  class "AppServiceProvider" as DiscountAppServiceProvider <<Provider>> {
    +register()
    +boot()
  }
  class "RepositoryServiceProvider" as DiscountRepositoryServiceProvider <<Provider>> {
    +register()
    +boot()
  }
}

package "Servicios externos" {
  class "notification-service" as DiscountNotificationExternal <<External>>
  class "auth-service" as DiscountAuthExternal <<External>>
  class "catalog-service" as DiscountCatalogExternal <<External>>
  class "shipping-service" as DiscountShippingExternal <<External>>
  class "legacy_mysql" as DiscountLegacyExternal <<External>>
  class "PDF adjunto" as DiscountPdfExternal <<External>>
}

DiscountController --> DiscountCode
DiscountController --> DiscountType
DiscountController --> DiscountBulkRule
DiscountController --> DiscountUsageTable
DiscountAdminController --> DiscountCode
DiscountAdminController --> DiscountType
DiscountAdminController --> DiscountBulkRule
DiscountAdminController --> DiscountPdfHelper
DiscountAdminController --> DiscountUsageTable
DiscountAdminController ..> DiscountNotificationExternal : campañas y correos
DiscountAdminController ..> DiscountLegacyExternal : fallback de clientes
DiscountPdfHelper ..> DiscountPdfExternal : HTML a PDF
DiscountEnsureAdmin ..> DiscountAuthExternal : valida token admin

DiscountType "1" o-- "0..*" DiscountCode : clasifica
DiscountCode "1" *-- "0..1" DiscountPercentageTable : regla porcentual
DiscountCode "1" *-- "0..1" DiscountFixedAmountTable : regla fija
DiscountCode "1" *-- "0..1" DiscountFreeShippingTable : regla envío gratis
DiscountCode "1" *-- "0..*" DiscountProductsTable : productos permitidos
DiscountCode "1" *-- "0..*" DiscountUsageTable : usos
DiscountUser "1" o-- "0..*" DiscountUsageTable : registra
DiscountUser "1" o-- "0..*" DiscountUserAppliedTable : descuentos asignados
DiscountProductsTable "0..*" --> "1" DiscountCatalogExternal : producto
DiscountFreeShippingTable "0..*" --> "0..1" DiscountShippingExternal : método de envío
@enduml
```

## Fuentes revisadas

- `services/discount-service/app/**/*.php`
- `services/discount-service/routes/api.php`
- `services/discount-service/database/migrations/*.php`

## Documentos relacionados

- [Índice de diagramas](../diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
