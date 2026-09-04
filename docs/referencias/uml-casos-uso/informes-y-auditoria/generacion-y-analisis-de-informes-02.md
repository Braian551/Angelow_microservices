# Informes y Auditoría — Generación y análisis de informes (parte 2)

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Informes y Auditoría — Generación y análisis de informes (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir limpiar filtros del informe activo" as uc_007
  usecase "permitir abrir el detalle de una fila de informe" as uc_008
  usecase "permitir imprimir el informe activo" as uc_009
}

actor_1 --> uc_007
actor_1 --> uc_008
actor_1 --> uc_009
@enduml
```



## Casos cubiertos

- El sistema debe permitir limpiar filtros del informe activo.
- El sistema debe permitir abrir el detalle de una fila de informe.
- El sistema debe permitir imprimir el informe activo.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
