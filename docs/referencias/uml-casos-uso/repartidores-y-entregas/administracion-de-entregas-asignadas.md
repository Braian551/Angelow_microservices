# Repartidores y Entregas — Administración de entregas asignadas

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Repartidores y Entregas — Administración de entregas asignadas
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir al administrador listar las entregas, sus\nestados, el método de envío y el repartidor asignado" as uc_016
}

actor_1 --> uc_016
@enduml
```



## Casos cubiertos

- El sistema debe permitir al administrador listar las entregas, sus estados, el método de envío y el repartidor asignado.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
