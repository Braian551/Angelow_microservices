# Reseñas, Preguntas y Favoritos — Moderación de reseñas (parte 2)

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Reseñas, Preguntas y Favoritos — Moderación de reseñas (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir exportar reseñas a PDF o Excel" as uc_016
  usecase "permitir filtrar reseñas por búsqueda, estado,\ncalificación y compra verificada" as uc_017
  usecase "permitir limpiar todos los filtros de reseñas" as uc_018
  usecase "mostrar estadísticas visuales de reseñas para apoyar\nla moderación" as uc_019
}

actor_1 --> uc_016
actor_1 --> uc_017
actor_1 --> uc_018
actor_1 --> uc_019
@enduml
```



## Casos cubiertos

- El sistema debe permitir exportar reseñas a PDF o Excel.
- El sistema debe permitir filtrar reseñas por búsqueda, estado, calificación y compra verificada.
- El sistema debe permitir limpiar todos los filtros de reseñas.
- El sistema debe mostrar estadísticas visuales de reseñas para apoyar la moderación.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
