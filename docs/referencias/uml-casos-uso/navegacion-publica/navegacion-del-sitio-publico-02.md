# Navegación Pública — Navegación del sitio público (parte 2)

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Navegación Pública — Navegación del sitio público (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir navegar desde el pie de página hacia niñas,\nniños, bebés y ofertas" as uc_007
}

actor_1 --> uc_007
@enduml
```



## Casos cubiertos

- El sistema debe permitir navegar desde el pie de página hacia niñas, niños, bebés y ofertas.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
