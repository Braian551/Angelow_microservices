# audit-service - Diagrama de clases en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Diagrama del microservicio de auditoría para órdenes, usuarios, productos, categorías y eliminaciones auditadas. Incluye atributos principales de las tablas de trazabilidad.

## Diagrama

```plantuml
@startuml
title audit-service - Auditoría de órdenes, usuarios y productos
left to right direction
skinparam classAttributeIconSize 0
skinparam packageStyle rectangle

package "Controladores HTTP" {
  class "AuditController" as AuditController <<Controller>> {
    -ordersTable: audit_orders
    -usersTable: audit_users
    -productsTable: productos_auditoria
    +orders()
    +users()
    +products()
  }
  class "HealthController" as AuditHealthController <<Controller>> {
    +__invoke()
  }
}

package "Modelos y tablas" {
  class "User" as AuditUser <<Model>> {
    +id: Integer
    +name: String
    +email: String
    -password: String
    +email_verified_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "audit_categories" as AuditCategoriesTable <<Tabla>> {
    +audit_id: Integer
    +category_id: Integer
    +action_type: String
    +old_name: String
    +new_name: String
    +action_date: DateTime
  }
  class "audit_orders" as AuditOrdersTable <<Tabla>> {
    +id: Integer
    +orden_id: Integer
    +accion: String
    +usuario_id: String
    +sql_usuario: String
    +fecha: DateTime
    +detalles: Text
  }
  class "audit_users" as AuditUsersTable <<Tabla>> {
    +id: Integer
    +usuario_id: String
    +accion: String
    +usuario_modificador: String
    +sql_usuario: String
    +fecha: DateTime
    +detalles: Text
  }
  class "productos_auditoria" as AuditProductsTable <<Tabla>> {
    +id: Integer
    +nombre: String
    +accion: String
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "eliminaciones_auditoria" as AuditDeletionsTable <<Tabla>> {
    +id: Integer
    +nombre: String
    +accion: String
    +fecha_eliminacion: DateTime
  }
}

package "Soporte Laravel" {
  class "AppServiceProvider" as AuditAppServiceProvider <<Provider>> {
    +register()
    +boot()
  }
  class "RepositoryServiceProvider" as AuditRepositoryServiceProvider <<Provider>> {
    +register()
    +boot()
  }
}

package "Dominios auditados" {
  class "order-service" as AuditOrderExternal <<External>>
  class "auth-service" as AuditAuthExternal <<External>>
  class "catalog-service" as AuditCatalogExternal <<External>>
}

AuditController --> AuditOrdersTable
AuditController --> AuditUsersTable
AuditController --> AuditProductsTable
AuditController --> AuditUser

AuditOrderExternal "1" o-- "0..*" AuditOrdersTable : eventos
AuditAuthExternal "1" o-- "0..*" AuditUsersTable : eventos
AuditCatalogExternal "1" o-- "0..*" AuditCategoriesTable : categorías
AuditCatalogExternal "1" o-- "0..*" AuditProductsTable : productos
AuditProductsTable "1" *-- "0..*" AuditDeletionsTable : eliminaciones
AuditUser "1" o-- "0..*" AuditOrdersTable : ejecuta
AuditUser "1" o-- "0..*" AuditUsersTable : modifica
@enduml
```

## Fuentes revisadas

- `services/audit-service/app/**/*.php`
- `services/audit-service/routes/api.php`
- `services/audit-service/database/migrations/*.php`

## Documentos relacionados

- [Índice de diagramas](../diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
