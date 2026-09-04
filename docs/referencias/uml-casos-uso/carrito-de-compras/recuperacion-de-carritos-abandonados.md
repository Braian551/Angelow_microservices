# Carrito de Compras — Recuperación de carritos abandonados

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Carrito de Compras — Recuperación de carritos abandonados
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir despachar recordatorios de carritos\nabandonados desde opción administrativa" as uc_012
}

actor_1 --> uc_012
@enduml
```



## Casos cubiertos

- El sistema debe permitir despachar recordatorios de carritos abandonados desde opción administrativa.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
