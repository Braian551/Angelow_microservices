# Direcciones y Envíos — Administración de tarifas de envío

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Direcciones y Envíos — Administración de tarifas de envío
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar reglas de recargo por rango de\nsubtotal" as uc_028
  usecase "permitir crear reglas de envío por rango de precio" as uc_029
  usecase "permitir editar reglas de envío por rango de precio" as uc_030
  usecase "permitir eliminar una regla de envío por precio" as uc_031
  usecase "permitir abrir el detalle de una regla de envío por\nprecio" as uc_032
  usecase "permitir exportar reglas de envío a PDF o Excel" as uc_033
}

actor_1 --> uc_028
actor_1 --> uc_029
actor_1 --> uc_030
actor_1 --> uc_031
actor_1 --> uc_032
actor_1 --> uc_033
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar reglas de recargo por rango de subtotal.
- El sistema debe permitir crear reglas de envío por rango de precio.
- El sistema debe permitir editar reglas de envío por rango de precio.
- El sistema debe permitir eliminar una regla de envío por precio.
- El sistema debe permitir abrir el detalle de una regla de envío por precio.
- El sistema debe permitir exportar reglas de envío a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
