# audit-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_audit`, responsable de trazabilidad de categorías, órdenes, usuarios, productos y eliminaciones. La nomenclatura conserva nombres heredados para mantener compatibilidad con datos migrados.

## Diagrama

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
  sql_usuario : varchar(255)
  fecha : timestamp
  detalles : text
}

entity "audit_users" as aud_users {
  * id : int <<PK>>
  --
  usuario_id : varchar(20)
  accion : varchar(10)
  usuario_modificador : varchar(20) <<NULL>>
  sql_usuario : varchar(255)
  fecha : timestamp
  detalles : text
}

entity "productos_auditoria" as aud_products {
  * id : int <<PK>>
  --
  nombre : varchar(100)
  accion : varchar(50)
  created_at : timestamp
  updated_at : timestamp
}

entity "eliminaciones_auditoria" as aud_deletions {
  * id : int <<PK>>
  --
  nombre : varchar(100)
  accion : varchar(50)
  fecha_eliminacion : timestamp
}

entity "catalog.categories" as ext_categories <<externa>> {
  * id : int <<PK>>
}

entity "orders.orders" as ext_orders <<externa>> {
  * id : int <<PK>>
}

entity "auth.users" as ext_users <<externa>> {
  * id : varchar(20) <<PK>>
}

entity "catalog.products" as ext_products <<externa>> {
  * id : int <<PK>>
}

aud_categories }o..|| ext_categories : category_id
aud_orders }o..|| ext_orders : orden_id
aud_orders }o..|| ext_users : usuario_id
aud_users }o..|| ext_users : usuario_id / modificador
aud_products }o..|| ext_products : nombre/producto
aud_deletions }o..|| ext_products : nombre/producto eliminado

note bottom
Las tablas de auditoría registran referencias operativas
sin llaves foráneas físicas entre bases de datos.
end note
@enduml
```

## Fuentes revisadas

- `services/audit-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/audit-service/app/Http/Controllers/AuditController.php`
- `services/audit-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
