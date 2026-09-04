# Catálogo y Productos — Configuración de variantes de producto (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Configuración de variantes de producto (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir quitar una imagen de variante del formulario" as uc_056
}

actor_1 --> uc_056
@enduml
```



## Casos cubiertos

- El sistema debe permitir quitar una imagen de variante del formulario.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
