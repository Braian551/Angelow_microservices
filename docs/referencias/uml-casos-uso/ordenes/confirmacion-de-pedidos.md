# Órdenes — Confirmación de pedidos

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Órdenes — Confirmación de pedidos
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "mostrar una página de confirmación con número de\npedido y resumen" as uc_001
  usecase "permitir descargar el PDF de la orden cuando esté\ndisponible" as uc_002
  usecase "permitir ir desde la confirmación al historial de\npedidos" as uc_003
  usecase "permitir volver a la tienda desde la confirmación" as uc_004
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
@enduml
```



## Casos cubiertos

- El sistema debe mostrar una página de confirmación con número de pedido y resumen.
- El sistema debe permitir descargar el PDF de la orden cuando esté disponible.
- El sistema debe permitir ir desde la confirmación al historial de pedidos.
- El sistema debe permitir volver a la tienda desde la confirmación.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
