# Promociones y Descuentos — Administración de cupones de descuento (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Promociones y Descuentos — Administración de cupones de descuento (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar códigos de descuento en administración" as uc_005
  usecase "permitir crear códigos de descuento" as uc_006
  usecase "permitir editar códigos de descuento" as uc_007
  usecase "permitir eliminar códigos de descuento" as uc_008
  usecase "permitir abrir el detalle de un código de descuento" as uc_009
  usecase "permitir exportar códigos de descuento a PDF o Excel" as uc_010
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

- El sistema debe permitir listar códigos de descuento en administración.
- El sistema debe permitir crear códigos de descuento.
- El sistema debe permitir editar códigos de descuento.
- El sistema debe permitir eliminar códigos de descuento.
- El sistema debe permitir abrir el detalle de un código de descuento.
- El sistema debe permitir exportar códigos de descuento a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
