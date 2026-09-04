# Gestión de Usuarios y Acceso — Registro y verificación de usuarios (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Registro y verificación de usuarios (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "mostrar un enlace navegable a términos y condiciones\njunto a la aceptación del registro" as uc_007
}

actor_1 --> uc_007
@enduml
```



## Casos cubiertos

- El sistema debe mostrar un enlace navegable a términos y condiciones junto a la aceptación del registro.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
