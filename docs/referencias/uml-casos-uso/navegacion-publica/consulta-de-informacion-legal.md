# Navegación Pública — Consulta de información legal

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Navegación Pública — Consulta de información legal
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir consultar una vista pública con los términos\ny condiciones del sitio" as uc_008
}

actor_1 --> uc_008
@enduml
```



## Casos cubiertos

- El sistema debe permitir consultar una vista pública con los términos y condiciones del sitio.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
