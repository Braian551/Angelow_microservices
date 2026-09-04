# Catálogo y Productos — Búsqueda y descubrimiento de productos (parte 1)

> 6 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Catálogo y Productos — Búsqueda y descubrimiento de productos (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir buscar productos desde el encabezado del\nsitio" as uc_012
  usecase "mostrar sugerencias de productos y términos mientras\nel usuario escribe" as uc_013
  usecase "guardar el historial de búsquedas del usuario o\nvisitante" as uc_014
  usecase "permitir abrir un producto desde las sugerencias de\nbúsqueda" as uc_015
  usecase "permitir buscar usando un término sugerido" as uc_016
  usecase "incrementar el contador de una búsqueda popular cuando\nse registra una búsqueda" as uc_018
}

actor_1 --> uc_012
actor_1 --> uc_013
actor_1 --> uc_015
actor_1 --> uc_016
uc_012 ..> uc_014 : <<include>>
uc_014 ..> uc_018 : <<include>>
@enduml
```



## Casos cubiertos

- El sistema debe permitir buscar productos desde el encabezado del sitio.
- El sistema debe mostrar sugerencias de productos y términos mientras el usuario escribe.
- El sistema debe guardar el historial de búsquedas del usuario o visitante.
- El sistema debe permitir abrir un producto desde las sugerencias de búsqueda.
- El sistema debe permitir buscar usando un término sugerido.
- El sistema debe incrementar el contador de una búsqueda popular cuando se registra una búsqueda.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe incluir términos populares dentro de las sugerencias de búsqueda.
  - Tipo: cálculo interno de sugerencias.
  - Disparador: se preparan sugerencias de búsqueda.
  - Actor directo: no aplica.
  - Contexto: búsqueda en curso.
  - Representación: composición interna de términos populares dentro de las sugerencias.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
