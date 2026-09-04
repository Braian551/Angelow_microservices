# Catálogo y Productos — Interacción con tarjetas de producto

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Interacción con tarjetas de producto
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1
actor "Visitante" as actor_2

rectangle "Angelow" {
  usecase "permitir marcar o quitar favorito desde una tarjeta de\nproducto" as uc_019
  usecase "permitir abrir el detalle desde imagen, nombre o botón\nde la tarjeta" as uc_020
  usecase "mostrar una imagen alternativa cuando la imagen del\nproducto no está disponible" as uc_021
}

actor_1 --> uc_019
actor_2 --> uc_020
actor_2 --> uc_021
@enduml
```



## Casos cubiertos

- El sistema debe permitir marcar o quitar favorito desde una tarjeta de producto.
- El sistema debe permitir abrir el detalle desde imagen, nombre o botón de la tarjeta.
- El sistema debe mostrar una imagen alternativa cuando la imagen del producto no está disponible.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
