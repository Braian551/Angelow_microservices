# Reseñas, Preguntas y Favoritos — Publicación y consulta de preguntas

> 2 casos de uso del subproceso.

```plantuml
@startuml
title Reseñas, Preguntas y Favoritos — Publicación y consulta de preguntas
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1
actor "Cliente" as actor_2

rectangle "Angelow" {
  usecase "permitir ver preguntas y respuestas del producto" as uc_003
  usecase "permitir iniciar una pregunta sobre el producto" as uc_004
}

actor_1 --> uc_003
actor_2 --> uc_004
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver preguntas y respuestas del producto.
- El sistema debe permitir iniciar una pregunta sobre el producto.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
