# Promociones y Descuentos — Administración de descuentos por cantidad (parte 1)

> 5 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Promociones y Descuentos — Administración de descuentos por cantidad (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1
actor "Administrador" as actor_2

rectangle "Angelow" {
  usecase "permitir listar reglas de descuento por cantidad" as uc_019
  usecase "permitir crear reglas de descuento por cantidad" as uc_020
  usecase "permitir editar reglas de descuento por cantidad" as uc_021
  usecase "permitir eliminar reglas de descuento por cantidad" as uc_022
  usecase "permitir abrir el detalle de una regla de descuento\npor cantidad" as uc_023
}

actor_2 --> uc_019
actor_2 --> uc_020
actor_2 --> uc_021
actor_2 --> uc_022
actor_2 --> uc_023
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar reglas de descuento por cantidad.
- El sistema debe permitir crear reglas de descuento por cantidad.
- El sistema debe permitir editar reglas de descuento por cantidad.
- El sistema debe permitir eliminar reglas de descuento por cantidad.
- El sistema debe permitir abrir el detalle de una regla de descuento por cantidad.

## Requerimientos internos relacionados

- El sistema debe validar descuentos automáticos por cantidad en el carrito.
  - Tipo: validación interna.
  - Disparador: el carrito contiene cantidades que pueden satisfacer una regla automática.
  - Actor directo: no aplica.
  - Representación: regla interna del cálculo del carrito; no se usa <<include>> porque puede ejecutarse desde varias acciones que alteran sus cantidades.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
