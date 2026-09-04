# Contenido y Configuración del Sitio — Publicación de anuncios

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Contenido y Configuración del Sitio — Publicación de anuncios
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "mostrar el anuncio activo de tipo barra superior" as uc_018
  usecase "mostrar el banner promocional activo en la página\nprincipal" as uc_019
  usecase "permitir consultar anuncios vigentes para la página\nprincipal" as uc_020
}

actor_1 --> uc_018
actor_1 --> uc_019
actor_1 --> uc_020
@enduml
```



## Casos cubiertos

- El sistema debe mostrar el anuncio activo de tipo barra superior.
- El sistema debe mostrar el banner promocional activo en la página principal.
- El sistema debe permitir consultar anuncios vigentes para la página principal.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
