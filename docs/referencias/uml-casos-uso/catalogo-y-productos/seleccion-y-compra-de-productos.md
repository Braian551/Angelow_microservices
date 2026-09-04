# Catálogo y Productos — Selección y compra de productos

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Selección y compra de productos
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir seleccionar color disponible del producto" as uc_029
  usecase "permitir seleccionar talla disponible del producto" as uc_030
  usecase "permitir aumentar o disminuir la cantidad antes de\ncomprar" as uc_031
  usecase "permitir agregar el producto al carrito desde la ficha" as uc_032
  usecase "permitir iniciar compra inmediata desde la ficha del\nproducto" as uc_033
  usecase "permitir marcar o desmarcar el producto como favorito" as uc_034
}

actor_1 --> uc_029
actor_1 --> uc_030
actor_1 --> uc_031
actor_1 --> uc_032
actor_1 --> uc_033
actor_1 --> uc_034
@enduml
```



## Casos cubiertos

- El sistema debe permitir seleccionar color disponible del producto.
- El sistema debe permitir seleccionar talla disponible del producto.
- El sistema debe permitir aumentar o disminuir la cantidad antes de comprar.
- El sistema debe permitir agregar el producto al carrito desde la ficha.
- El sistema debe permitir iniciar compra inmediata desde la ficha del producto.
- El sistema debe permitir marcar o desmarcar el producto como favorito.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
