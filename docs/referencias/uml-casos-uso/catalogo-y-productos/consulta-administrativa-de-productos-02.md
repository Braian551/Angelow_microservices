# Catálogo y Productos — Consulta administrativa de productos (parte 2)

> 2 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Consulta administrativa de productos (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir ampliar imágenes desde la vista rápida\nadministrativa del producto" as uc_041
  usecase "permitir filtrar imágenes y variantes por color dentro\nde la vista rápida" as uc_042
}

actor_1 --> uc_041
actor_1 --> uc_042
@enduml
```



## Casos cubiertos

- El sistema debe permitir ampliar imágenes desde la vista rápida administrativa del producto.
- El sistema debe permitir filtrar imágenes y variantes por color dentro de la vista rápida.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
