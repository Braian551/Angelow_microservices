# Direcciones y Envíos — Administración de direcciones

> 5 casos de uso del subproceso.

```plantuml
@startuml
title Direcciones y Envíos — Administración de direcciones
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir ver todas las direcciones guardadas del\nusuario" as uc_001
  usecase "permitir crear una dirección en un formulario por\npasos" as uc_002
  usecase "permitir editar una dirección guardada" as uc_003
  usecase "permitir eliminar una dirección después de\nconfirmación" as uc_004
  usecase "permitir marcar una dirección como predeterminada" as uc_005
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_005
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver todas las direcciones guardadas del usuario.
- El sistema debe permitir crear una dirección en un formulario por pasos.
- El sistema debe permitir editar una dirección guardada.
- El sistema debe permitir eliminar una dirección después de confirmación.
- El sistema debe permitir marcar una dirección como predeterminada.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
