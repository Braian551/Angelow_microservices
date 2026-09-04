# Pagos y Facturación — Registro y confirmación del pago en checkout (parte 1)

> 4 casos de uso y 2 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Registro y confirmación del pago en checkout (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "mostrar la cuenta bancaria activa para realizar\ntransferencia" as uc_001
  usecase "permitir seleccionar el banco relacionado con la\ntransferencia" as uc_002
  usecase "permitir subir comprobante de pago" as uc_004
  usecase "permitir quitar el comprobante seleccionado antes de\nconfirmar" as uc_005
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_004
actor_1 --> uc_005
@enduml
```



## Casos cubiertos

- El sistema debe mostrar la cuenta bancaria activa para realizar transferencia.
- El sistema debe permitir seleccionar el banco relacionado con la transferencia.
- El sistema debe permitir subir comprobante de pago.
- El sistema debe permitir quitar el comprobante seleccionado antes de confirmar.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe exigir el número de referencia de transferencia.
  - Tipo: validación interna.
  - Disparador: se intenta registrar una transferencia.
  - Actor directo: no aplica.
  - Contexto: checkout con transferencia.
  - Representación: comprobación interna del número de referencia.
- El sistema debe exigir aceptación de términos antes de crear la orden.
  - Tipo: validación interna.
  - Disparador: se intenta crear una orden.
  - Actor directo: no aplica.
  - Contexto: checkout activo.
  - Representación: comprobación interna de aceptación de términos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
