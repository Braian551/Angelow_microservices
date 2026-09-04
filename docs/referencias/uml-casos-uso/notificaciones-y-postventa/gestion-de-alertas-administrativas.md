# Notificaciones y Postventa — Gestión de alertas administrativas

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Notificaciones y Postventa — Gestión de alertas administrativas
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir registrar alertas administrativas descartadas" as uc_012
}

actor_1 --> uc_012
@enduml
```



## Casos cubiertos

- El sistema debe permitir registrar alertas administrativas descartadas.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
