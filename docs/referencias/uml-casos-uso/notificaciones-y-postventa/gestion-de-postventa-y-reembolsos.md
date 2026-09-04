# Notificaciones y Postventa — Gestión de postventa y reembolsos

> 3 casos de uso y 2 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Notificaciones y Postventa — Gestión de postventa y reembolsos
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "registrar movimientos negativos cuando existe\nreembolso" as uc_015
  usecase "permitir al cliente solicitar reembolso desde Mis\npedidos cuando la orden esté completada y el producto\ntenga política activa" as uc_016
  usecase "permitir al administrador listar solicitudes de\nreembolso, revisar motivo y evidencia, aceptar,\nrechazar o completar el reembolso desde una vista que\ninicialice correctamente su estado reactivo" as uc_017
}

actor_1 --> uc_015
actor_1 --> uc_016
actor_1 --> uc_017
@enduml
```



## Casos cubiertos

- El sistema debe registrar movimientos negativos cuando existe reembolso.
- El sistema debe permitir al cliente solicitar reembolso desde Mis pedidos cuando la orden esté completada y el producto tenga política activa.
- El sistema debe permitir al administrador listar solicitudes de reembolso, revisar motivo y evidencia, aceptar, rechazar o completar el reembolso desde una vista que inicialice correctamente su estado reactivo.

## Requerimientos internos relacionados

- El sistema debe generar y enviar factura cuando una orden pasa a entregada.
  - Tipo: automatismo interno.
  - Disparador: una orden pasa a entregada.
  - Actor directo: no aplica.
  - Representación: comportamiento posterior al cambio de estado de la orden.
- El sistema debe notificar al cliente cuando una orden se cancela.
  - Tipo: automatismo interno.
  - Disparador: una orden se cancela.
  - Actor directo: no aplica.
  - Representación: comportamiento posterior al cambio de estado de la orden.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
