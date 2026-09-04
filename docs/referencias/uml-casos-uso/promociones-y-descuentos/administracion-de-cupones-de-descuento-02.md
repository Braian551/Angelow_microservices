# Promociones y Descuentos — Administración de cupones de descuento (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Promociones y Descuentos — Administración de cupones de descuento (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir generar o regenerar un código automático en\nel formulario" as uc_011
}

actor_1 --> uc_011
@enduml
```



## Casos cubiertos

- El sistema debe permitir generar o regenerar un código automático en el formulario.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
