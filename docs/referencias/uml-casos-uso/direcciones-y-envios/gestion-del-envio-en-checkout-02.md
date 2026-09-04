# Direcciones y Envíos — Gestión del envío en checkout (parte 2)

> 4 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Direcciones y Envíos — Gestión del envío en checkout (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir eliminar un descuento aplicado" as uc_017
  usecase "permitir continuar al paso de pago después de\nseleccionar dirección y método de envío" as uc_019
  usecase "permitir volver desde el paso de envío al carrito" as uc_020
  usecase "permitir abrir productos desde el resumen de checkout" as uc_021
}

actor_1 --> uc_017
actor_1 --> uc_019
actor_1 --> uc_020
actor_1 --> uc_021
@enduml
```



## Casos cubiertos

- El sistema debe permitir eliminar un descuento aplicado.
- El sistema debe permitir continuar al paso de pago después de seleccionar dirección y método de envío.
- El sistema debe permitir volver desde el paso de envío al carrito.
- El sistema debe permitir abrir productos desde el resumen de checkout.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe aplicar automáticamente descuentos por cantidad cuando el carrito cumple las reglas.
  - Tipo: cálculo automático.
  - Disparador: el carrito cumple una regla de descuento por cantidad.
  - Actor directo: no aplica.
  - Contexto: checkout activo.
  - Representación: regla interna aplicada durante el cálculo del checkout.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
