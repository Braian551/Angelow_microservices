# Promociones y Descuentos — Administración de descuentos por cantidad (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Promociones y Descuentos — Administración de descuentos por cantidad (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir exportar reglas de descuento por cantidad a\nPDF o Excel" as uc_024
}

actor_1 --> uc_024
@enduml
```



## Casos cubiertos

- El sistema debe permitir exportar reglas de descuento por cantidad a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
