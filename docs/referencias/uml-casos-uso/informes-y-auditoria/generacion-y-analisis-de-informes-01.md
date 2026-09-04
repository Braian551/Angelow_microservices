# Informes y Auditoría — Generación y análisis de informes (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Informes y Auditoría — Generación y análisis de informes (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "generar informe de ventas por período" as uc_001
  usecase "generar informe de productos populares y stock" as uc_002
  usecase "generar informe de clientes recurrentes y mostrar el\ntop de clientes por valor acumulado" as uc_003
  usecase "permitir exportar informes administrativos" as uc_004
  usecase "permitir cambiar entre informes de ventas, productos\npopulares y clientes recurrentes" as uc_005
  usecase "permitir aplicar filtros del informe activo" as uc_006
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_005
actor_1 --> uc_006
@enduml
```



## Casos cubiertos

- El sistema debe generar informe de ventas por período.
- El sistema debe generar informe de productos populares y stock.
- El sistema debe generar informe de clientes recurrentes y mostrar el top de clientes por valor acumulado.
- El sistema debe permitir exportar informes administrativos.
- El sistema debe permitir cambiar entre informes de ventas, productos populares y clientes recurrentes.
- El sistema debe permitir aplicar filtros del informe activo.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
