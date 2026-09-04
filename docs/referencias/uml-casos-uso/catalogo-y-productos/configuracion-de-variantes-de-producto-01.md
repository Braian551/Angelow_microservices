# Catálogo y Productos — Configuración de variantes de producto (parte 1)

> 5 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Configuración de variantes de producto (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir definir una variante de color como\npredeterminada" as uc_051
  usecase "permitir quitar una variante de color del formulario\nantes de guardar" as uc_052
  usecase "permitir agregar tallas a una variante desde la\nventana modal de tallas y precios" as uc_053
  usecase "permitir quitar una talla de una variante antes de\nguardar" as uc_054
  usecase "permitir marcar una imagen de variante como principal" as uc_055
}
actor_1 --> uc_051
actor_1 --> uc_052
actor_1 --> uc_053
actor_1 --> uc_054
actor_1 --> uc_055
@enduml
```



## Casos cubiertos

- El sistema debe permitir definir una variante de color como predeterminada.
- El sistema debe permitir quitar una variante de color del formulario antes de guardar.
- El sistema debe permitir agregar tallas a una variante desde la ventana modal de tallas y precios.
- El sistema debe permitir quitar una talla de una variante antes de guardar.
- El sistema debe permitir marcar una imagen de variante como principal.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe generar SKU para variantes usando marca, categoría, género, estilo, color y talla.
  - Tipo: generación automática.
  - Disparador: se modifican los atributos que componen el SKU y este no fue editado manualmente.
  - Actor directo: no aplica.
  - Contexto: formulario de variante.
  - Representación: comportamiento interno del formulario.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
