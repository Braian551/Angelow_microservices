# Catálogo y Productos — Administración de colecciones

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Administración de colecciones
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar colecciones en administración" as uc_068
  usecase "permitir crear colecciones" as uc_069
  usecase "permitir editar colecciones y sus productos asociados" as uc_070
  usecase "permitir eliminar colecciones" as uc_071
  usecase "permitir activar o desactivar colecciones" as uc_072
  usecase "permitir subir, cambiar o quitar imagen de una\ncolección" as uc_073
}

actor_1 --> uc_068
actor_1 --> uc_069
actor_1 --> uc_070
actor_1 --> uc_071
actor_1 --> uc_072
actor_1 --> uc_073
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar colecciones en administración.
- El sistema debe permitir crear colecciones.
- El sistema debe permitir editar colecciones y sus productos asociados.
- El sistema debe permitir eliminar colecciones.
- El sistema debe permitir activar o desactivar colecciones.
- El sistema debe permitir subir, cambiar o quitar imagen de una colección.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
