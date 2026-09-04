# Órdenes — Administración de pedidos (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Órdenes — Administración de pedidos (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir a administradores listar órdenes recientes\ncon filtros y búsqueda" as uc_013
  usecase "permitir ver el detalle administrativo de una orden" as uc_014
  usecase "permitir editar datos operativos de una orden desde el\npanel" as uc_015
  usecase "permitir cambiar el estado de una orden" as uc_016
  usecase "permitir cambiar el estado de pago de una orden" as uc_017
  usecase "permitir desactivar una orden cuando ya no debe operar\nen listados activos" as uc_018
}

actor_1 --> uc_013
actor_1 --> uc_014
actor_1 --> uc_015
actor_1 --> uc_016
actor_1 --> uc_017
actor_1 --> uc_018
@enduml
```



## Casos cubiertos

- El sistema debe permitir a administradores listar órdenes recientes con filtros y búsqueda.
- El sistema debe permitir ver el detalle administrativo de una orden.
- El sistema debe permitir editar datos operativos de una orden desde el panel.
- El sistema debe permitir cambiar el estado de una orden.
- El sistema debe permitir cambiar el estado de pago de una orden.
- El sistema debe permitir desactivar una orden cuando ya no debe operar en listados activos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
