# Contenido y Configuración del Sitio — Configuración general del sitio

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Contenido y Configuración del Sitio — Configuración general del sitio
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1
actor "Visitante" as actor_2

rectangle "Angelow" {
  usecase "permitir ver configuración general del sitio" as uc_021
  usecase "permitir actualizar configuración general del sitio" as uc_022
  usecase "permitir cambiar entre secciones de configuración\ngeneral" as uc_023
  usecase "permitir seleccionar colores corporativos mediante\nselector visual" as uc_024
  usecase "permitir subir, cambiar o quitar imágenes de\nconfiguración como logos y recursos visuales" as uc_025
  usecase "permitir consultar ajustes públicos del sitio para\nencabezado, pie, logos y colores" as uc_026
}

actor_1 --> uc_021
actor_1 --> uc_022
actor_1 --> uc_023
actor_1 --> uc_024
actor_1 --> uc_025
actor_2 --> uc_026
@enduml
```



## Casos cubiertos

- El sistema debe permitir ver configuración general del sitio.
- El sistema debe permitir actualizar configuración general del sitio.
- El sistema debe permitir cambiar entre secciones de configuración general.
- El sistema debe permitir seleccionar colores corporativos mediante selector visual.
- El sistema debe permitir subir, cambiar o quitar imágenes de configuración como logos y recursos visuales.
- El sistema debe permitir consultar ajustes públicos del sitio para encabezado, pie, logos y colores.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
