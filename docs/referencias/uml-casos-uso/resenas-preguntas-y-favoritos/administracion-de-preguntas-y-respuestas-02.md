# Reseñas, Preguntas y Favoritos — Administración de preguntas y respuestas (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Reseñas, Preguntas y Favoritos — Administración de preguntas y respuestas (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "mostrar un gráfico de preguntas respondidas y\npendientes" as uc_026
}

actor_1 --> uc_026
@enduml
```



## Casos cubiertos

- El sistema debe mostrar un gráfico de preguntas respondidas y pendientes.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
