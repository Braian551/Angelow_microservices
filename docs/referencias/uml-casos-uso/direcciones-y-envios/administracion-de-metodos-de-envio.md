# Direcciones y Envíos — Administración de métodos de envío

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Direcciones y Envíos — Administración de métodos de envío
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar métodos de envío configurados" as uc_022
  usecase "permitir crear un método de envío" as uc_023
  usecase "permitir editar un método de envío" as uc_024
  usecase "permitir eliminar un método de envío" as uc_025
  usecase "permitir abrir el detalle de un método de envío" as uc_026
  usecase "permitir exportar métodos de envío a PDF o Excel" as uc_027
}

actor_1 --> uc_022
actor_1 --> uc_023
actor_1 --> uc_024
actor_1 --> uc_025
actor_1 --> uc_026
actor_1 --> uc_027
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar métodos de envío configurados.
- El sistema debe permitir crear un método de envío.
- El sistema debe permitir editar un método de envío.
- El sistema debe permitir eliminar un método de envío.
- El sistema debe permitir abrir el detalle de un método de envío.
- El sistema debe permitir exportar métodos de envío a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
