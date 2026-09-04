# Catálogo y Productos — Mantenimiento de productos

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Mantenimiento de productos
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir registrar un producto nuevo" as uc_043
  usecase "permitir editar la información de un producto" as uc_044
  usecase "permitir activar o desactivar un producto" as uc_045
  usecase "permitir eliminar un producto desde administración" as uc_046
}

actor_1 --> uc_043
actor_1 --> uc_044
actor_1 --> uc_045
actor_1 --> uc_046
@enduml
```



## Casos cubiertos

- El sistema debe permitir registrar un producto nuevo.
- El sistema debe permitir editar la información de un producto.
- El sistema debe permitir activar o desactivar un producto.
- El sistema debe permitir eliminar un producto desde administración.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
