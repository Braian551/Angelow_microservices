# Notificaciones y Postventa — Gestión de notificaciones del usuario

> 5 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Notificaciones y Postventa — Gestión de notificaciones del usuario
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir al usuario ver su bandeja de notificaciones" as uc_001
  usecase "permitir marcar una notificación como leída" as uc_002
  usecase "permitir marcar todas las notificaciones como leídas" as uc_003
  usecase "permitir eliminar una notificación individual" as uc_004
  usecase "permitir abrir el pedido o entidad relacionada desde\nuna notificación" as uc_005
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_005
@enduml
```



## Casos cubiertos

- El sistema debe permitir al usuario ver su bandeja de notificaciones.
- El sistema debe permitir marcar una notificación como leída.
- El sistema debe permitir marcar todas las notificaciones como leídas.
- El sistema debe permitir eliminar una notificación individual.
- El sistema debe permitir abrir el pedido o entidad relacionada desde una notificación.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe actualizar periódicamente el contador de notificaciones sin leer.
  - Tipo: automatismo periódico.
  - Disparador: intervalo de actualización mientras existe una sesión autenticada.
  - Actor directo: no aplica.
  - Contexto: usuario autenticado.
  - Representación: comportamiento interno asociado a la gestión de notificaciones.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
