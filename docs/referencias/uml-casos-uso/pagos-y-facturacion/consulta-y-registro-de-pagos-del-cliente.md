# Pagos y Facturación — Consulta y registro de pagos del cliente

> 2 casos de uso del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Consulta y registro de pagos del cliente
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir registrar una transacción de pago asociada a\nuna orden" as uc_018
  usecase "permitir consultar pagos asociados al usuario o sus\nórdenes" as uc_019
}

actor_1 --> uc_018
actor_1 --> uc_019
@enduml
```



## Casos cubiertos

- El sistema debe permitir registrar una transacción de pago asociada a una orden.
- El sistema debe permitir consultar pagos asociados al usuario o sus órdenes.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
