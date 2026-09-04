# Catálogo y Productos — Administración de tallas (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Administración de tallas (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar tallas disponibles" as uc_074
  usecase "permitir crear tallas" as uc_075
  usecase "permitir editar tallas existentes" as uc_076
  usecase "permitir activar o desactivar tallas" as uc_077
  usecase "permitir eliminar tallas sin productos asociados" as uc_078
  usecase "permitir buscar y filtrar tallas por estado" as uc_079
}

actor_1 --> uc_074
actor_1 --> uc_075
actor_1 --> uc_076
actor_1 --> uc_077
actor_1 --> uc_078
actor_1 --> uc_079
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar tallas disponibles.
- El sistema debe permitir crear tallas.
- El sistema debe permitir editar tallas existentes.
- El sistema debe permitir activar o desactivar tallas.
- El sistema debe permitir eliminar tallas sin productos asociados.
- El sistema debe permitir buscar y filtrar tallas por estado.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
