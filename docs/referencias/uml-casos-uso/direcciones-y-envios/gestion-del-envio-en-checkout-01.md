# Direcciones y Envíos — Gestión del envío en checkout (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Direcciones y Envíos — Gestión del envío en checkout (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "mostrar el resumen de productos antes del pago" as uc_011
  usecase "permitir seleccionar una dirección guardada para la\nentrega" as uc_012
  usecase "permitir ir desde checkout a la página de direcciones\npara agregar o corregir una dirección" as uc_013
  usecase "permitir seleccionar un método de envío activo" as uc_014
  usecase "calcular recargos o envío gratis según el subtotal" as uc_015
  usecase "permitir aplicar un código de descuento en checkout" as uc_016
}

actor_1 --> uc_011
actor_1 --> uc_012
actor_1 --> uc_013
actor_1 --> uc_014
actor_1 --> uc_016
uc_014 ..> uc_015 : <<include>>
@enduml
```



## Casos cubiertos

- El sistema debe mostrar el resumen de productos antes del pago.
- El sistema debe permitir seleccionar una dirección guardada para la entrega.
- El sistema debe permitir ir desde checkout a la página de direcciones para agregar o corregir una dirección.
- El sistema debe permitir seleccionar un método de envío activo.
- El sistema debe calcular recargos o envío gratis según el subtotal.
- El sistema debe permitir aplicar un código de descuento en checkout.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
