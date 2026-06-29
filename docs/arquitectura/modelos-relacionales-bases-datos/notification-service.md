# notification-service - Modelo relacional en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Modelo de la base `angelow_notifications`, responsable de tipos de notificación, bandeja de usuario, preferencias, cola de envíos, descartes administrativos y anuncios.

## Diagrama

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
  is_sms_sent : boolean
  is_push_sent : boolean
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
  last_attempt_at : timestamp
  scheduled_at : timestamp
  sent_at : timestamp
  error_message : text
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
  subtitle : varchar(150)
  button_text : varchar(50)
  button_link : varchar(255)
  image : varchar(255)
  priority : int
  is_active : boolean
  start_date : timestamp
  end_date : timestamp
}

entity "auth.users" as ext_users <<externa>> {
  * id : varchar(20) <<PK>>
}

not_types ||--o{ not_notifications : type_id
not_types ||--o{ not_preferences : type_id
not_notifications ||--o{ not_queue : notification_id
not_notifications }o..|| ext_users : user_id
not_preferences }o..|| ext_users : user_id
not_dismissals }o..|| ext_users : admin_id
not_types ||..o{ not_announcements : evento visible

note right of not_notifications
user_id y entidades relacionadas son referencias
lógicas a otros dominios.
end note
@enduml
```

## Fuentes revisadas

- `services/notification-service/database/migrations/0001_01_01_000000_create_users_table.php`
- `services/notification-service/database/migrations/2026_04_18_000001_create_announcements_table.php`
- `services/notification-service/app/Models/*.php`
- `services/notification-service/routes/api.php`

## Documentos relacionados

- [Índice de modelos relacionales](../modelos-relacionales-bases-datos-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
