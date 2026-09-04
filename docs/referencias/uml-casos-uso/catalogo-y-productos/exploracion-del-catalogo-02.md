# Catálogo y Productos — Exploración del catálogo (parte 2)

> 5 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Exploración del catálogo (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir limpiar todos los filtros de tienda con un\nbotón" as uc_007
  usecase "permitir navegar entre páginas de productos" as uc_008
  usecase "permitir ver el listado público de colecciones" as uc_009
  usecase "permitir ordenar productos por novedad, popularidad,\nprecio menor y precio mayor" as uc_010
  usecase "permitir abrir y cerrar grupos de filtros en la tienda" as uc_011
}

actor_1 --> uc_007
actor_1 --> uc_008
actor_1 --> uc_009
actor_1 --> uc_010
actor_1 --> uc_011
@enduml
```



## Casos cubiertos

- El sistema debe permitir limpiar todos los filtros de tienda con un botón.
- El sistema debe permitir navegar entre páginas de productos.
- El sistema debe permitir ver el listado público de colecciones.
- El sistema debe permitir ordenar productos por novedad, popularidad, precio menor y precio mayor.
- El sistema debe permitir abrir y cerrar grupos de filtros en la tienda.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
