# Promociones y Descuentos — Validación y aplicación de cupones

> 3 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Promociones y Descuentos — Validación y aplicación de cupones
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1
actor "Administrador" as actor_2

rectangle "Angelow" {
  usecase "permitir consultar códigos de descuento disponibles\npara validación" as uc_001
  usecase "validar un código de descuento ingresado" as uc_002
  usecase "registrar el uso de un código de descuento cuando se\nvincula a una orden" as uc_003
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_2 --> uc_003
@enduml
```



## Casos cubiertos

- El sistema debe permitir consultar códigos de descuento disponibles para validación.
- El sistema debe validar un código de descuento ingresado.
- El sistema debe registrar el uso de un código de descuento cuando se vincula a una orden.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe conservar descuentos aplicados o asignados al usuario hasta que sean usados o expiren.
  - Tipo: regla funcional interna.
  - Disparador: un descuento aplicado se consume o alcanza su vencimiento.
  - Actor directo: no aplica.
  - Contexto: descuento aplicado.
  - Representación: conservación y actualización interna de su estado.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
