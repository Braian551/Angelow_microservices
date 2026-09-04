# Ayuda y Manual de Usuario — Orientación de acceso y registro

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Ayuda y Manual de Usuario — Orientación de acceso y registro
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "explicar paso a paso las funciones de inicio de\nsesión, registro y recuperación de cuenta" as uc_002
}

actor_1 --> uc_002
@enduml
```



## Casos cubiertos

- El sistema debe explicar paso a paso las funciones de inicio de sesión, registro y recuperación de cuenta.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
