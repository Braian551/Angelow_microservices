# Catálogo y Productos — Administración de categorías

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Administración de categorías
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar categorías en la tienda y\nadministración" as uc_062
  usecase "permitir crear categorías de productos" as uc_063
  usecase "permitir editar categorías existentes" as uc_064
  usecase "permitir eliminar categorías sin productos asociados" as uc_065
  usecase "permitir activar o desactivar categorías" as uc_066
  usecase "permitir subir, cambiar o quitar imagen de una\ncategoría" as uc_067
}

actor_1 --> uc_062
actor_1 --> uc_063
actor_1 --> uc_064
actor_1 --> uc_065
actor_1 --> uc_066
actor_1 --> uc_067
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar categorías en la tienda y administración.
- El sistema debe permitir crear categorías de productos.
- El sistema debe permitir editar categorías existentes.
- El sistema debe permitir eliminar categorías sin productos asociados.
- El sistema debe permitir activar o desactivar categorías.
- El sistema debe permitir subir, cambiar o quitar imagen de una categoría.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
