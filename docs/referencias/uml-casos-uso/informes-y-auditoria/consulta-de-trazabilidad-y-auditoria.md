# Informes y Auditoría — Consulta de trazabilidad y auditoría

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Informes y Auditoría — Consulta de trazabilidad y auditoría
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir consultar auditoría de órdenes" as uc_010
  usecase "permitir consultar auditoría de usuarios" as uc_011
  usecase "permitir consultar auditoría de productos" as uc_012
}

actor_1 --> uc_010
actor_1 --> uc_011
actor_1 --> uc_012
@enduml
```



## Casos cubiertos

- El sistema debe permitir consultar auditoría de órdenes.
- El sistema debe permitir consultar auditoría de usuarios.
- El sistema debe permitir consultar auditoría de productos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
