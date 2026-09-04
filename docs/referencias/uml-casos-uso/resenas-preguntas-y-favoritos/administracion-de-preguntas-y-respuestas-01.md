# Reseñas, Preguntas y Favoritos — Administración de preguntas y respuestas (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Reseñas, Preguntas y Favoritos — Administración de preguntas y respuestas (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar preguntas de productos" as uc_020
  usecase "permitir responder preguntas de clientes" as uc_021
  usecase "permitir eliminar preguntas" as uc_022
  usecase "permitir filtrar preguntas por búsqueda y estado de\nrespuesta" as uc_023
  usecase "permitir limpiar filtros de preguntas" as uc_024
  usecase "permitir exportar preguntas a PDF o Excel" as uc_025
}

actor_1 --> uc_020
actor_1 --> uc_021
actor_1 --> uc_022
actor_1 --> uc_023
actor_1 --> uc_024
actor_1 --> uc_025
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar preguntas de productos.
- El sistema debe permitir responder preguntas de clientes.
- El sistema debe permitir eliminar preguntas.
- El sistema debe permitir filtrar preguntas por búsqueda y estado de respuesta.
- El sistema debe permitir limpiar filtros de preguntas.
- El sistema debe permitir exportar preguntas a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
