# Catálogo y Productos — Consulta administrativa de productos (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Consulta administrativa de productos (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar productos en administración con\nbúsqueda, filtros, imágenes, variantes y stock" as uc_035
  usecase "permitir abrir una vista rápida del producto desde el\nlistado" as uc_036
  usecase "permitir exportar productos a CSV" as uc_037
  usecase "permitir exportar productos a PDF" as uc_038
  usecase "permitir filtrar productos por búsqueda, categoría,\nestado, género y orden" as uc_039
  usecase "permitir limpiar todos los filtros del listado\nadministrativo de productos" as uc_040
}

actor_1 --> uc_035
actor_1 --> uc_036
actor_1 --> uc_037
actor_1 --> uc_038
actor_1 --> uc_039
actor_1 --> uc_040
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar productos en administración con búsqueda, filtros, imágenes, variantes y stock.
- El sistema debe permitir abrir una vista rápida del producto desde el listado.
- El sistema debe permitir exportar productos a CSV.
- El sistema debe permitir exportar productos a PDF.
- El sistema debe permitir filtrar productos por búsqueda, categoría, estado, género y orden.
- El sistema debe permitir limpiar todos los filtros del listado administrativo de productos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
