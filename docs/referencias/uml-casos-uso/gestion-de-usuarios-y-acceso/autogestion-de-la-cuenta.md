# Gestión de Usuarios y Acceso — Autogestión de la cuenta

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Autogestión de la cuenta
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "mostrar un resumen con pedidos recientes, direcciones,\nfavoritos y recomendaciones" as uc_056
  usecase "permitir ir desde el resumen de cuenta al historial\ncompleto de pedidos" as uc_057
  usecase "permitir ir desde el resumen de cuenta a la gestión de\ndirecciones" as uc_058
  usecase "permitir ir desde el resumen de cuenta a la lista de\nfavoritos" as uc_059
}

actor_1 --> uc_056
actor_1 --> uc_057
actor_1 --> uc_058
actor_1 --> uc_059
@enduml
```



## Casos cubiertos

- El sistema debe mostrar un resumen con pedidos recientes, direcciones, favoritos y recomendaciones.
- El sistema debe permitir ir desde el resumen de cuenta al historial completo de pedidos.
- El sistema debe permitir ir desde el resumen de cuenta a la gestión de direcciones.
- El sistema debe permitir ir desde el resumen de cuenta a la lista de favoritos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
