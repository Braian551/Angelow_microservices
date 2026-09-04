# Contenido y Configuración del Sitio — Administración de carruseles (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Contenido y Configuración del Sitio — Administración de carruseles (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar sliders de la página principal" as uc_005
  usecase "permitir crear un slider" as uc_006
  usecase "permitir previsualizar el slider antes de guardar" as uc_007
  usecase "permitir editar un slider existente" as uc_008
  usecase "permitir activar o desactivar sliders" as uc_009
  usecase "permitir reordenar sliders" as uc_010
}

actor_1 --> uc_005
actor_1 --> uc_006
actor_1 --> uc_007
actor_1 --> uc_008
actor_1 --> uc_009
actor_1 --> uc_010
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar sliders de la página principal.
- El sistema debe permitir crear un slider.
- El sistema debe permitir previsualizar el slider antes de guardar.
- El sistema debe permitir editar un slider existente.
- El sistema debe permitir activar o desactivar sliders.
- El sistema debe permitir reordenar sliders.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
