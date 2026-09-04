# Catálogo y Productos — Consulta del detalle de producto (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Consulta del detalle de producto (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir ver la ficha completa de un producto por su\nidentificador de navegación" as uc_022
  usecase "permitir volver desde la ficha del producto a la\ntienda" as uc_023
  usecase "permitir ampliar imágenes del producto en una ventana\nmodal" as uc_024
  usecase "permitir cambiar la imagen principal desde miniaturas" as uc_025
  usecase "permitir abrir la pestaña de descripción del producto" as uc_026
  usecase "permitir abrir la pestaña de especificaciones o guía\nde tallas" as uc_027
}

actor_1 --> uc_022
actor_1 --> uc_023
actor_1 --> uc_024
actor_1 --> uc_025
actor_1 --> uc_026
actor_1 --> uc_027
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver la ficha completa de un producto por su identificador de navegación.
- El sistema debe permitir volver desde la ficha del producto a la tienda.
- El sistema debe permitir ampliar imágenes del producto en una ventana modal.
- El sistema debe permitir cambiar la imagen principal desde miniaturas.
- El sistema debe permitir abrir la pestaña de descripción del producto.
- El sistema debe permitir abrir la pestaña de especificaciones o guía de tallas.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
