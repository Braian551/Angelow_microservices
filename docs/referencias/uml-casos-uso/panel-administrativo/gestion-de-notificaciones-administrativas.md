# Panel Administrativo — Gestión de notificaciones administrativas

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Panel Administrativo — Gestión de notificaciones administrativas
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir y cerrar el panel de notificaciones\nadministrativas" as uc_005
  usecase "permitir actualizar manualmente las notificaciones\nadministrativas" as uc_006
  usecase "permitir marcar todas las notificaciones\nadministrativas como leídas" as uc_007
  usecase "permitir abrir la pantalla relacionada de una\nnotificación administrativa" as uc_008
}

actor_1 --> uc_005
actor_1 --> uc_006
actor_1 --> uc_007
actor_1 --> uc_008
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir y cerrar el panel de notificaciones administrativas.
- El sistema debe permitir actualizar manualmente las notificaciones administrativas.
- El sistema debe permitir marcar todas las notificaciones administrativas como leídas.
- El sistema debe permitir abrir la pantalla relacionada de una notificación administrativa.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
