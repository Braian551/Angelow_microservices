# Inventario — Control y movimiento de existencias (parte 1)

> 5 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Inventario — Control y movimiento de existencias (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir ver inventario por producto, color y talla" as uc_001
  usecase "permitir ajustar manualmente el stock de una variante" as uc_002
  usecase "permitir transferir stock entre variantes" as uc_003
  usecase "permitir consultar historial de movimientos de\ninventario" as uc_004
  usecase "permitir alternar inventario entre todo, bajo stock y\nsin stock" as uc_006
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_006
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver inventario por producto, color y talla.
- El sistema debe permitir ajustar manualmente el stock de una variante.
- El sistema debe permitir transferir stock entre variantes.
- El sistema debe permitir consultar historial de movimientos de inventario.
- El sistema debe permitir alternar inventario entre todo, bajo stock y sin stock.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe descontar o confirmar inventario cuando se confirma una orden.
  - Tipo: automatismo interno de inventario.
  - Disparador: se confirma una orden.
  - Actor directo: no aplica.
  - Contexto: orden confirmada.
  - Representación: actualización interna de existencias; no hay una acción administrativa que la inicie.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
