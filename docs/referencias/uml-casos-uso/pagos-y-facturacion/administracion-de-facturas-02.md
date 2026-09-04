# Pagos y Facturación — Administración de facturas (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Administración de facturas (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir el detalle de una factura consultando\nla orden relacionada" as uc_017
}

actor_1 --> uc_017
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir el detalle de una factura consultando la orden relacionada.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
