# Catálogo y Productos — Gestión de variantes e imágenes

> 5 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Gestión de variantes e imágenes
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir asociar colores a un producto" as uc_057
  usecase "permitir asociar tallas y stock a un producto" as uc_058
  usecase "permitir subir imágenes generales del producto" as uc_059
  usecase "permitir subir imágenes específicas para variantes de\ncolor" as uc_060
  usecase "permitir quitar imágenes del producto o de una\nvariante" as uc_061
}

actor_1 --> uc_057
actor_1 --> uc_058
actor_1 --> uc_059
actor_1 --> uc_060
actor_1 --> uc_061
@enduml
```



## Casos cubiertos

- El sistema debe permitir asociar colores a un producto.
- El sistema debe permitir asociar tallas y stock a un producto.
- El sistema debe permitir subir imágenes generales del producto.
- El sistema debe permitir subir imágenes específicas para variantes de color.
- El sistema debe permitir quitar imágenes del producto o de una variante.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
