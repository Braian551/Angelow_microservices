# Panel Administrativo — Acceso a operaciones administrativas

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Panel Administrativo — Acceso a operaciones administrativas
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir acciones rápidas desde el encabezado\nadministrativo" as uc_015
}

actor_1 --> uc_015
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir acciones rápidas desde el encabezado administrativo.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
