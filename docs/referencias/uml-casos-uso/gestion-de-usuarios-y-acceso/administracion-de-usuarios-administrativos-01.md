# Gestión de Usuarios y Acceso — Administración de usuarios administrativos (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Administración de usuarios administrativos (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir crear cuentas administrativas desde el panel" as uc_043
  usecase "mostrar un listado con el equipo administrativo" as uc_044
  usecase "permitir editar los datos de contacto y estado de un\nadministrador" as uc_045
  usecase "permitir bloquear una cuenta administrativa desde el\nlistado" as uc_046
  usecase "permitir reactivar una cuenta administrativa bloqueada" as uc_047
  usecase "permitir eliminar cuentas administrativas que ya no se\nusarán" as uc_048
}

actor_1 --> uc_043
actor_1 --> uc_044
actor_1 --> uc_045
actor_1 --> uc_046
actor_1 --> uc_047
actor_1 --> uc_048
@enduml
```



## Casos cubiertos

- El sistema debe permitir crear cuentas administrativas desde el panel.
- El sistema debe mostrar un listado con el equipo administrativo.
- El sistema debe permitir editar los datos de contacto y estado de un administrador.
- El sistema debe permitir bloquear una cuenta administrativa desde el listado.
- El sistema debe permitir reactivar una cuenta administrativa bloqueada.
- El sistema debe permitir eliminar cuentas administrativas que ya no se usarán.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
