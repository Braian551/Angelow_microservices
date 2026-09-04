# Pagos y Facturación — Configuración bancaria

> 2 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Pagos y Facturación — Configuración bancaria
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir a administradores ver la configuración de\ncuenta bancaria" as uc_027
  usecase "permitir guardar o actualizar la cuenta bancaria de\nrecepción" as uc_028
}

actor_1 --> uc_027
actor_1 --> uc_028
@enduml
```



## Casos cubiertos

- El sistema debe permitir a administradores ver la configuración de cuenta bancaria.
- El sistema debe permitir guardar o actualizar la cuenta bancaria de recepción.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe listar el catálogo de bancos colombianos activos para formularios de pago y configuración bancaria.
  - Tipo: carga interna de información.
  - Disparador: se abre un formulario de pago o configuración bancaria.
  - Actor directo: no aplica.
  - Contexto: formulario bancario.
  - Representación: consulta interna del catálogo de bancos activos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
