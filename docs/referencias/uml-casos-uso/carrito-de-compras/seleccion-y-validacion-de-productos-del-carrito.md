# Carrito de Compras — Selección y validación de productos del carrito

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Carrito de Compras — Selección y validación de productos del carrito
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "informar si un producto del carrito está agotado o no\ndisponible" as uc_008
  usecase "permitir seleccionar o deseleccionar productos\ndisponibles del carrito antes de continuar al envío" as uc_009
  usecase "permitir seleccionar o deseleccionar todos los\nproductos disponibles del carrito en una sola acción" as uc_010
}

actor_1 --> uc_008
actor_1 --> uc_009
actor_1 --> uc_010
@enduml
```



## Casos cubiertos

- El sistema debe informar si un producto del carrito está agotado o no disponible.
- El sistema debe permitir seleccionar o deseleccionar productos disponibles del carrito antes de continuar al envío.
- El sistema debe permitir seleccionar o deseleccionar todos los productos disponibles del carrito en una sola acción.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
