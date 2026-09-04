# Repartidores y Entregas — Navegación y seguimiento en tiempo real

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Repartidores y Entregas — Navegación y seguimiento en tiempo real
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Repartidor" as actor_1
actor "Cliente" as actor_2

rectangle "Angelow" {
  usecase "permitir al repartidor calcular e iniciar una ruta\nhacia las coordenadas de entrega" as uc_013
  usecase "solicitar al repartidor autorización para compartir su\nubicación con el cliente durante la ruta" as uc_014
  usecase "mostrar un mapa de OpenStreetMap en el detalle de la\norden cuando exista una ubicación compartida" as uc_015
}

actor_1 --> uc_013
actor_1 --> uc_014
actor_2 --> uc_015
@enduml
```



## Casos cubiertos

- El sistema debe permitir al repartidor calcular e iniciar una ruta hacia las coordenadas de entrega.
- El sistema debe solicitar al repartidor autorización para compartir su ubicación con el cliente durante la ruta.
- El sistema debe mostrar un mapa de OpenStreetMap en el detalle de la orden cuando exista una ubicación compartida.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
