# Contenido y Configuración del Sitio — Administración de anuncios

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Contenido y Configuración del Sitio — Administración de anuncios
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "permitir listar anuncios comerciales" as uc_012
  usecase "permitir crear anuncios de barra superior o banner\npromocional" as uc_013
  usecase "permitir editar anuncios comerciales" as uc_014
  usecase "permitir eliminar anuncios comerciales" as uc_015
  usecase "permitir abrir el detalle y previsualización de un\nanuncio comercial" as uc_016
  usecase "permitir exportar anuncios comerciales a PDF o Excel" as uc_017
}

actor_1 --> uc_012
actor_1 --> uc_013
actor_1 --> uc_014
actor_1 --> uc_015
actor_1 --> uc_016
actor_1 --> uc_017
@enduml
```



## Casos cubiertos

- El sistema debe permitir listar anuncios comerciales.
- El sistema debe permitir crear anuncios de barra superior o banner promocional.
- El sistema debe permitir editar anuncios comerciales.
- El sistema debe permitir eliminar anuncios comerciales.
- El sistema debe permitir abrir el detalle y previsualización de un anuncio comercial.
- El sistema debe permitir exportar anuncios comerciales a PDF o Excel.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
