# Reseñas, Preguntas y Favoritos — Moderación de reseñas (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Reseñas, Preguntas y Favoritos — Moderación de reseñas (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar reseñas con filtros y estadísticas" as uc_010
  usecase "permitir abrir el detalle de una reseña" as uc_011
  usecase "permitir aprobar una reseña" as uc_012
  usecase "permitir devolver una reseña a estado pendiente" as uc_013
  usecase "permitir marcar o quitar verificación de una reseña" as uc_014
  usecase "permitir eliminar reseñas" as uc_015
}

actor_1 --> uc_010
actor_1 --> uc_011
actor_1 --> uc_012
actor_1 --> uc_013
actor_1 --> uc_014
actor_1 --> uc_015
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar reseñas con filtros y estadísticas.
- El sistema debe permitir abrir el detalle de una reseña.
- El sistema debe permitir aprobar una reseña.
- El sistema debe permitir devolver una reseña a estado pendiente.
- El sistema debe permitir marcar o quitar verificación de una reseña.
- El sistema debe permitir eliminar reseñas.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
