# Repartidores y Entregas — Administración de perfiles de repartidor

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Repartidores y Entregas — Administración de perfiles de repartidor
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir editar desde el panel la identidad, contacto,\nrol, estado de cuenta y disponibilidad operativa de un\nrepartidor" as uc_007
}

actor_1 --> uc_007
@enduml
```



## Casos cubiertos

- El sistema debe permitir editar desde el panel la identidad, contacto, rol, estado de cuenta y disponibilidad operativa de un repartidor.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
