# Pagos y Facturación — Registro y confirmación del pago en checkout (parte 2)

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Registro y confirmación del pago en checkout (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "crear la orden, registrar el pago y enviar\nconfirmación al terminar el checkout" as uc_007
  usecase "enviar confirmación de checkout con el detalle del\npedido" as uc_008
  usecase "permitir volver desde el pago al paso de envío" as uc_009
  usecase "mostrar un enlace navegable a términos y condiciones\nal registrar el comprobante de pago" as uc_010
}

actor_1 --> uc_007
actor_1 --> uc_008
actor_1 --> uc_009
actor_1 --> uc_010
@enduml
```



## Casos cubiertos

- El sistema debe crear la orden, registrar el pago y enviar confirmación al terminar el checkout.
- El sistema debe enviar confirmación de checkout con el detalle del pedido.
- El sistema debe permitir volver desde el pago al paso de envío.
- El sistema debe mostrar un enlace navegable a términos y condiciones al registrar el comprobante de pago.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
