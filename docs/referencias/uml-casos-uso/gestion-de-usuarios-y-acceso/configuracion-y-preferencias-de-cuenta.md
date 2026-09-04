# Gestión de Usuarios y Acceso — Configuración y preferencias de cuenta

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Configuración y preferencias de cuenta
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir ver preferencias de notificaciones y datos\nbásicos de cuenta" as uc_060
  usecase "permitir activar o desactivar preferencias de\nnotificación" as uc_061
  usecase "permitir alternar entre perfil, seguridad y\nnotificaciones" as uc_062
}

actor_1 --> uc_060
actor_1 --> uc_061
actor_1 --> uc_062
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver preferencias de notificaciones y datos básicos de cuenta.
- El sistema debe permitir activar o desactivar preferencias de notificación.
- El sistema debe permitir alternar entre perfil, seguridad y notificaciones.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
