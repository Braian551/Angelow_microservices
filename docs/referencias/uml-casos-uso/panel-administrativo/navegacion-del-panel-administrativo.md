# Panel Administrativo — Navegación del panel administrativo

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Panel Administrativo — Navegación del panel administrativo
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir el menú lateral administrativo en\npantallas pequeñas" as uc_001
  usecase "permitir cerrar el menú lateral administrativo" as uc_002
  usecase "permitir expandir y contraer submenús del panel\nadministrativo" as uc_003
  usecase "permitir cerrar sesión desde el menú administrativo" as uc_004
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir el menú lateral administrativo en pantallas pequeñas.
- El sistema debe permitir cerrar el menú lateral administrativo.
- El sistema debe permitir expandir y contraer submenús del panel administrativo.
- El sistema debe permitir cerrar sesión desde el menú administrativo.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
