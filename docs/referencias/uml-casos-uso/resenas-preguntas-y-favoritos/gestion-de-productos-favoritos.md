# Reseñas, Preguntas y Favoritos — Gestión de productos favoritos

> 5 casos de uso del subproceso.

```plantuml
@startuml
title Reseñas, Preguntas y Favoritos — Gestión de productos favoritos
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir cargar la lista de deseos completa del\nusuario" as uc_005
  usecase "permitir abrir la ficha de un producto desde la lista\nde deseos" as uc_006
  usecase "permitir quitar un producto individual de la lista de\ndeseos desde la tarjeta" as uc_007
  usecase "permitir limpiar todos los productos de la lista de\ndeseos después de confirmación" as uc_008
  usecase "permitir ir a la tienda cuando la lista de deseos está\nvacía" as uc_009
}

actor_1 --> uc_005
actor_1 --> uc_006
actor_1 --> uc_007
actor_1 --> uc_008
actor_1 --> uc_009
@enduml
```



## Casos cubiertos

- El sistema debe permitir cargar la lista de deseos completa del usuario.
- El sistema debe permitir abrir la ficha de un producto desde la lista de deseos.
- El sistema debe permitir quitar un producto individual de la lista de deseos desde la tarjeta.
- El sistema debe permitir limpiar todos los productos de la lista de deseos después de confirmación.
- El sistema debe permitir ir a la tienda cuando la lista de deseos está vacía.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
