# Ayuda y Manual de Usuario — Orientación para administradores

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Ayuda y Manual de Usuario — Orientación para administradores
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir al administrador abrir desde Configuración\ngeneral el catálogo de recorridos del panel" as uc_004
}

actor_1 --> uc_004
@enduml
```



## Casos cubiertos

- El sistema debe permitir al administrador abrir desde Configuración general el catálogo de recorridos del panel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
