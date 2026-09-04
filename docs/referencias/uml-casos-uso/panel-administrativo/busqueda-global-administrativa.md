# Panel Administrativo — Búsqueda global administrativa

> 2 casos de uso del subproceso.

```plantuml
@startuml
title Panel Administrativo — Búsqueda global administrativa
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir cerrar el panel de búsqueda global" as uc_009
  usecase "permitir buscar órdenes, productos, clientes y atajos\ndesde el encabezado administrativo" as uc_010
}

actor_1 --> uc_009
actor_1 --> uc_010
@enduml
```



## Casos cubiertos

- El sistema debe permitir cerrar el panel de búsqueda global.
- El sistema debe permitir buscar órdenes, productos, clientes y atajos desde el encabezado administrativo.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
