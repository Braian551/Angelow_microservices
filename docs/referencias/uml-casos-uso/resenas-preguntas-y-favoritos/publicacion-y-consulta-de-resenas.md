# Reseñas, Preguntas y Favoritos — Publicación y consulta de reseñas

> 2 casos de uso del subproceso.

```plantuml
@startuml
title Reseñas, Preguntas y Favoritos — Publicación y consulta de reseñas
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1
actor "Cliente" as actor_2

rectangle "Angelow" {
  usecase "permitir ver reseñas, calificación promedio y\ndistribución de estrellas en la ficha del producto" as uc_001
  usecase "permitir iniciar la acción de escribir reseña desde la\nficha del producto" as uc_002
}

actor_1 --> uc_001
actor_2 --> uc_002
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver reseñas, calificación promedio y distribución de estrellas en la ficha del producto.
- El sistema debe permitir iniciar la acción de escribir reseña desde la ficha del producto.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
