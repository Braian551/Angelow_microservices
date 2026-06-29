# notification-service - Diagrama de clases en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Diagrama del microservicio de notificaciones, preferencias, dispatch asíncrono, broadcasting, anuncios y descartes administrativos. Incluye atributos de tablas/modelos y relaciones UML con multiplicidad.

## Diagrama

```plantuml
@startuml
title notification-service - Notificaciones, preferencias y anuncios
left to right direction
skinparam classAttributeIconSize 0
skinparam packageStyle rectangle

package "Controladores HTTP" {
  class "NotificationController" as NotificationController <<Controller>> {
    -dispatchService: NotificationDispatchService
    +__construct(dispatchService)
    +index(request)
    +store(request)
    +dispatchTrigger(request)
    +markAllAsRead(request)
    +markAsRead(id)
    +destroy(request, id)
  }
  class "NotificationPreferenceController" as NotificationPreferenceController <<Controller>> {
    +show(request)
    +update(request)
  }
  class "AdminNotificationController" as NotificationAdminController <<Controller>> {
    +notificationDismissals(request)
    +storeNotificationDismissals(request)
    +homeAnnouncements()
    +announcements()
    +storeAnnouncement(request)
    +updateAnnouncement(request, id)
    +destroyAnnouncement(id)
  }
  class "HealthController" as NotificationHealthController <<Controller>> {
    +__invoke()
  }
}

package "Servicios, jobs y eventos" {
  class "NotificationDispatchService" as NotificationDispatchService <<Service>> {
    -typesTable: notification_types
    -preferencesTable: notification_preferences
    -notificationsTable: notifications
    -queueTable: notification_queue
    +dispatchToUser(payload)
    +resolveTargetUserIds(requestedUserIds, limit)
  }
  class "DispatchNotificationJob" as NotificationDispatchJob <<Job>> {
    +notificationId: Integer
    +channel: String
    +__construct(notificationId, channel)
    +uniqueId()
    +handle()
  }
  class "NotificationCreated" as NotificationCreatedEvent <<Event>> {
    +notification: Array
    +userId: String
    +__construct(notification)
    +broadcastOn()
    +broadcastAs()
    +broadcastWith()
  }
}

package "Modelos y tablas" {
  class "NotificationType (notification_types)" as NotificationType <<Model>> {
    +id: Integer
    +name: String
    +description: String
    +template: Text
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "NotificationPreference (notification_preferences)" as NotificationPreference <<Model>> {
    +id: Integer
    +user_id: String
    +type_id: Integer
    +email_enabled: Boolean
    +sms_enabled: Boolean
    +push_enabled: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "AdminNotificationDismissal (admin_notification_dismissals)" as NotificationDismissal <<Model>> {
    +id: Integer
    +admin_id: String
    +notification_key: String
    +dismissed_at: DateTime
  }
  class "User" as NotificationUser <<Model>> {
    +id: Integer
    +name: String
    +email: String
    -password: String
    +email_verified_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "notifications" as NotificationsTable <<Tabla>> {
    +id: Integer
    +user_id: String
    +type_id: Integer
    +title: String
    +message: Text
    +related_entity_type: String
    +related_entity_id: Integer
    +is_read: Boolean
    +is_email_sent: Boolean
    +is_sms_sent: Boolean
    +is_push_sent: Boolean
    +expires_at: DateTime
    +created_at: DateTime
    +read_at: DateTime
  }
  class "notification_queue" as NotificationQueueTable <<Tabla>> {
    +id: Integer
    +notification_id: Integer
    +channel: String
    +status: String
    +attempts: Integer
    +last_attempt_at: DateTime
    +scheduled_at: DateTime
    +sent_at: DateTime
    +error_message: Text
  }
  class "announcements" as NotificationAnnouncementsTable <<Tabla>> {
    +id: Integer
    +type: String
    +title: String
    +message: Text
    +subtitle: String
    +button_text: String
    +button_link: String
    +image: String
    +background_color: String
    +text_color: String
    +icon: String
    +priority: Integer
    +is_active: Boolean
    +start_date: DateTime
    +end_date: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
}

package "Soporte Laravel" {
  class "EnsureAdmin" as NotificationEnsureAdmin <<Middleware>> {
    +handle(request, next)
  }
  class "AppServiceProvider" as NotificationAppServiceProvider <<Provider>> {
    +register()
    +boot()
  }
  class "RepositoryServiceProvider" as NotificationRepositoryServiceProvider <<Provider>> {
    +register()
    +boot()
  }
}

package "Servicios externos" {
  class "auth-service" as NotificationAuthExternal <<External>>
  class "Redis / broadcast" as NotificationRedis <<External>>
  class "Mail SMTP" as NotificationMail <<External>>
  class "legacy_mysql" as NotificationLegacyExternal <<External>>
  class "/uploads/announcements" as NotificationUploads <<Storage>>
}

NotificationController --> NotificationDispatchService
NotificationController --> NotificationsTable
NotificationPreferenceController --> NotificationPreference
NotificationPreferenceController --> NotificationType
NotificationAdminController --> NotificationDismissal
NotificationAdminController --> NotificationAnnouncementsTable
NotificationAdminController --> NotificationUploads

NotificationDispatchService --> NotificationType
NotificationDispatchService --> NotificationPreference
NotificationDispatchService --> NotificationsTable
NotificationDispatchService --> NotificationQueueTable
NotificationDispatchService --> NotificationDispatchJob
NotificationDispatchService ..> NotificationMail : correo por evento
NotificationDispatchService ..> NotificationLegacyExternal : usuarios y preferencias
NotificationDispatchJob --> NotificationCreatedEvent
NotificationCreatedEvent ..> NotificationRedis : canal privado por usuario
NotificationEnsureAdmin ..> NotificationAuthExternal : valida token admin

NotificationUser "1" o-- "0..*" NotificationsTable : recibe
NotificationType "1" o-- "0..*" NotificationsTable : clasifica
NotificationsTable "1" *-- "0..*" NotificationQueueTable : entregas por canal
NotificationUser "1" *-- "0..*" NotificationPreference : preferencias
NotificationType "1" o-- "0..*" NotificationPreference : tipo
NotificationUser "1" o-- "0..*" NotificationDismissal : descarta
NotificationAnnouncementsTable "0..*" o-- "0..1" NotificationUploads : imagen
@enduml
```

## Fuentes revisadas

- `services/notification-service/app/**/*.php`
- `services/notification-service/routes/api.php`
- `services/notification-service/database/migrations/*.php`

## Documentos relacionados

- [Índice de diagramas](../diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
