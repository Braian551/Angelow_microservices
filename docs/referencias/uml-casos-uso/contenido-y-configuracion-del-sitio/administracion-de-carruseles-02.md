# Contenido y Configuración del Sitio — Administración de carruseles (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Contenido y Configuración del Sitio — Administración de carruseles (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir eliminar sliders" as uc_011
}

actor_1 --> uc_011
@enduml
```



## Casos cubiertos

- El sistema debe permitir eliminar sliders.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
