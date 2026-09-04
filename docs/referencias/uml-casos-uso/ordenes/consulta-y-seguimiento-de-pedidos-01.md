# Órdenes — Consulta y seguimiento de pedidos (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Órdenes — Consulta y seguimiento de pedidos (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir al usuario ver su historial de órdenes" as uc_005
  usecase "permitir ver el detalle completo de una orden propia" as uc_006
  usecase "mostrar una línea de tiempo de estados de la orden" as uc_007
  usecase "permitir al cliente cancelar pedidos en estado\npermitido" as uc_008
  usecase "permitir volver a tienda desde una orden para comprar\nnuevamente" as uc_009
  usecase "permitir descargar la factura PDF generada desde el\ndetalle de un pedido propio" as uc_010
}

actor_1 --> uc_005
actor_1 --> uc_006
actor_1 --> uc_007
actor_1 --> uc_008
actor_1 --> uc_009
actor_1 --> uc_010
@enduml
```



## Casos cubiertos

- El sistema debe permitir al usuario ver su historial de órdenes.
- El sistema debe permitir ver el detalle completo de una orden propia.
- El sistema debe mostrar una línea de tiempo de estados de la orden.
- El sistema debe permitir al cliente cancelar pedidos en estado permitido.
- El sistema debe permitir volver a tienda desde una orden para comprar nuevamente.
- El sistema debe permitir descargar la factura PDF generada desde el detalle de un pedido propio.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
