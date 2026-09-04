# Ayuda y Manual de Usuario — Orientación para visitantes

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Ayuda y Manual de Usuario — Orientación para visitantes
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante o cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir a visitantes y clientes abrir el manual\ninteractivo desde el pie de página" as uc_001
}

actor_1 --> uc_001
@enduml
```



## Casos cubiertos

- El sistema debe permitir a visitantes y clientes abrir el manual interactivo desde el pie de página.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
