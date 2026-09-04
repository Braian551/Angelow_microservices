# Gestión de Usuarios y Acceso — Administración de usuarios administrativos (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Administración de usuarios administrativos (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir seleccionar y previsualizar una foto para un\nadministrador" as uc_049
}

actor_1 --> uc_049
@enduml
```



## Casos cubiertos

- El sistema debe permitir seleccionar y previsualizar una foto para un administrador.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
