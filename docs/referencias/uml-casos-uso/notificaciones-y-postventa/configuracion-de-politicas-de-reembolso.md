# Notificaciones y Postventa — Configuración de políticas de reembolso

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Notificaciones y Postventa — Configuración de políticas de reembolso
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir al administrador activar opcionalmente una\npolítica de reembolso al crear o editar productos" as uc_018
}

actor_1 --> uc_018
@enduml
```



## Casos cubiertos

- El sistema debe permitir al administrador activar opcionalmente una política de reembolso al crear o editar productos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
