# Catálogo y Productos — Consulta del detalle de producto (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Consulta del detalle de producto (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir la guía de tallas desde la ficha del\nproducto" as uc_028
}

actor_1 --> uc_028
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir la guía de tallas desde la ficha del producto.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
