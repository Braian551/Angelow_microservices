# Catálogo y Productos — Administración de tallas (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Administración de tallas (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir limpiar filtros del listado de tallas" as uc_080
}

actor_1 --> uc_080
@enduml
```



## Casos cubiertos

- El sistema debe permitir limpiar filtros del listado de tallas.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
