# Operación e Integración del Sistema — Monitoreo de disponibilidad de servicios

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Operación e Integración del Sistema — Monitoreo de disponibilidad de servicios
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Personal de soporte" as actor_1

rectangle "Angelow" {
  usecase "permitir consultar si las áreas principales están\ndisponibles para operar" as uc_001
}

actor_1 --> uc_001
@enduml
```



## Casos cubiertos

- El sistema debe permitir consultar si las áreas principales están disponibles para operar.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
