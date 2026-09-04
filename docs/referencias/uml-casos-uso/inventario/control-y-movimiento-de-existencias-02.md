# Inventario — Control y movimiento de existencias (parte 2)

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Inventario — Control y movimiento de existencias (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir el detalle de inventario de un producto" as uc_007
  usecase "permitir actualizar manualmente el historial de\ninventario del producto abierto" as uc_008
  usecase "permitir exportar inventario a PDF o Excel" as uc_009
}

actor_1 --> uc_007
actor_1 --> uc_008
actor_1 --> uc_009
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir el detalle de inventario de un producto.
- El sistema debe permitir actualizar manualmente el historial de inventario del producto abierto.
- El sistema debe permitir exportar inventario a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
