# Notification Service

Microservicio Laravel para notificaciones, preferencias, colas y broadcasting de eventos.

## Endpoints

- `GET /api/health`
- `GET /api/notifications?user_id=`
- `POST /api/notifications`
- `PATCH /api/notifications/{id}/read`

## Tablas de dominio

- `notification_types`
- `notifications`
- `notification_preferences`
- `notification_queue`
- `admin_notification_dismissals`

## Workers y websockets

- Job `DispatchNotificationJob` con `ShouldBeUnique` para evitar duplicidad.
- Evento `NotificationCreated` preparado para broadcasting.
- Cola configurada para Redis.
