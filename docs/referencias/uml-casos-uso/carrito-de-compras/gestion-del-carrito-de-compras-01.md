# Carrito de Compras — Gestión del carrito de compras (parte 1)

> 7 casos de uso del subproceso.

```plantuml
@startuml
title Carrito de Compras — Gestión del carrito de compras (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir ver los productos agregados al carrito" as uc_001
  usecase "permitir ir a la tienda cuando el carrito está vacío" as uc_002
  usecase "permitir modificar la cantidad de un ítem del carrito" as uc_003
  usecase "permitir eliminar un producto individual del carrito" as uc_004
  usecase "permitir continuar desde el carrito al checkout de\nenvío" as uc_005
  usecase "permitir consultar los identificadores de productos\npresentes en el carrito" as uc_006
  usecase "validar que exista al menos un producto disponible\nseleccionado antes de avanzar al envío" as uc_011
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_005
uc_001 ..> uc_006 : <<include>>
uc_005 ..> uc_011 : <<include>>
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver los productos agregados al carrito.
- El sistema debe permitir ir a la tienda cuando el carrito está vacío.
- El sistema debe permitir modificar la cantidad de un ítem del carrito.
- El sistema debe permitir eliminar un producto individual del carrito.
- El sistema debe permitir continuar desde el carrito al checkout de envío.
- El sistema debe permitir consultar los identificadores de productos presentes en el carrito.
- El sistema debe impedir avanzar al envío cuando no exista al menos un producto disponible seleccionado.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
