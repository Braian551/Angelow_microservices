# Catálogo y Productos — Exploración del catálogo (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Exploración del catálogo (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "mostrar la página principal con sliders, categorías,\ncolecciones, productos destacados y anuncios activos" as uc_001
  usecase "permitir consultar productos en la tienda con\npaginación y filtros" as uc_002
  usecase "permitir filtrar productos por categoría" as uc_003
  usecase "permitir filtrar productos por género: niña, niño,\nbebé u ofertas" as uc_004
  usecase "permitir filtrar productos por rango de precio" as uc_005
  usecase "permitir filtrar productos por colección" as uc_006
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_005
actor_1 --> uc_006
@enduml
```



## Casos cubiertos

- El sistema debe mostrar la página principal con sliders, categorías, colecciones, productos destacados y anuncios activos.
- El sistema debe permitir consultar productos en la tienda con paginación y filtros.
- El sistema debe permitir filtrar productos por categoría.
- El sistema debe permitir filtrar productos por género: niña, niño, bebé u ofertas.
- El sistema debe permitir filtrar productos por rango de precio.
- El sistema debe permitir filtrar productos por colección.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
