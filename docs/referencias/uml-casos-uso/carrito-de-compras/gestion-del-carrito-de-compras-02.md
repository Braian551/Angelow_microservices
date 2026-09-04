# Carrito de Compras — Gestión del carrito de compras (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Carrito de Compras — Gestión del carrito de compras (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir la ficha del producto desde el carrito" as uc_007
}

actor_1 --> uc_007
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir la ficha del producto desde el carrito.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
