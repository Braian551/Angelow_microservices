# Repartidores y Entregas — Revisión de solicitudes de vinculación

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Repartidores y Entregas — Revisión de solicitudes de vinculación
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir al administrador previsualizar adjuntos,\naprobar solicitudes, solicitar correcciones\ndocumentales y desactivar repartidores" as uc_006
}

actor_1 --> uc_006
@enduml
```



## Casos cubiertos

- El sistema debe permitir al administrador previsualizar adjuntos, aprobar solicitudes, solicitar correcciones documentales y desactivar repartidores.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
