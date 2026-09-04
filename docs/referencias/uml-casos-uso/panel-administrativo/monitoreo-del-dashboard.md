# Panel Administrativo — Monitoreo del dashboard

> 4 casos de uso del subproceso.

```plantuml
@startuml
title Panel Administrativo — Monitoreo del dashboard
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1

rectangle "Angelow" {
  usecase "mostrar métricas principales del negocio en el\ndashboard" as uc_011
  usecase "mostrar alertas de productos con stock bajo o agotado" as uc_012
  usecase "mostrar accesos a órdenes recientes desde el dashboard" as uc_013
  usecase "mostrar gráficos de ventas, productos y tendencias" as uc_014
}

actor_1 --> uc_011
actor_1 --> uc_012
actor_1 --> uc_013
actor_1 --> uc_014
@enduml
```



## Casos cubiertos

- El sistema debe mostrar métricas principales del negocio en el dashboard.
- El sistema debe mostrar alertas de productos con stock bajo o agotado.
- El sistema debe mostrar accesos a órdenes recientes desde el dashboard.
- El sistema debe mostrar gráficos de ventas, productos y tendencias.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
