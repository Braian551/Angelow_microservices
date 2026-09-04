# Pagos y Facturación — Verificación y administración de pagos (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Verificación y administración de pagos (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir al administrador abrir y previsualizar\ncomprobantes de pago desde el panel de pagos o el\ndetalle de la orden" as uc_026
}

actor_1 --> uc_026
@enduml
```



## Casos cubiertos

- El sistema debe permitir al administrador abrir y previsualizar comprobantes de pago desde el panel de pagos o el detalle de la orden.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
