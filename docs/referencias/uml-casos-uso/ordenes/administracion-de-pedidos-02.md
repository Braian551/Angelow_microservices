# Órdenes — Administración de pedidos (parte 2)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Órdenes — Administración de pedidos (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "registrar cuando un administrador visualiza órdenes\nnuevas" as uc_019
  usecase "mostrar un contador de órdenes nuevas en el panel" as uc_020
  usecase "permitir aplicar filtros de órdenes por búsqueda,\nestado, pago y fechas" as uc_021
  usecase "permitir limpiar filtros del listado de órdenes" as uc_022
  usecase "permitir exportar órdenes a PDF o Excel" as uc_023
  usecase "permitir seleccionar órdenes individuales para\nacciones masivas" as uc_024
}

actor_1 --> uc_019
actor_1 --> uc_020
actor_1 --> uc_021
actor_1 --> uc_022
actor_1 --> uc_023
actor_1 --> uc_024
@enduml
```



## Casos cubiertos

- El sistema debe registrar cuando un administrador visualiza órdenes nuevas.
- El sistema debe mostrar un contador de órdenes nuevas en el panel.
- El sistema debe permitir aplicar filtros de órdenes por búsqueda, estado, pago y fechas.
- El sistema debe permitir limpiar filtros del listado de órdenes.
- El sistema debe permitir exportar órdenes a PDF o Excel.
- El sistema debe permitir seleccionar órdenes individuales para acciones masivas.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
