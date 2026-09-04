# Contenido y Configuración del Sitio — Interacción con carruseles públicos

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Contenido y Configuración del Sitio — Interacción con carruseles públicos
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir avanzar al siguiente slide de la página\nprincipal" as uc_001
  usecase "permitir volver al slide anterior de la página\nprincipal" as uc_002
  usecase "permitir seleccionar un slide desde sus indicadores" as uc_003
  usecase "permitir abrir el enlace configurado de un slider" as uc_004
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
@enduml
```



## Casos cubiertos

- El sistema debe permitir avanzar al siguiente slide de la página principal.
- El sistema debe permitir volver al slide anterior de la página principal.
- El sistema debe permitir seleccionar un slide desde sus indicadores.
- El sistema debe permitir abrir el enlace configurado de un slider.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
