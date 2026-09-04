# Repartidores y Entregas — Vinculación de repartidores

> 3 casos de uso del subproceso.

```plantuml
@startuml
title Repartidores y Entregas — Vinculación de repartidores
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Repartidor" as actor_1

rectangle "Angelow" {
  usecase "permitir crear la identidad de un nuevo repartidor\ndespués de verificar su correo" as uc_003
  usecase "permitir al repartidor indicar si realiza entregas a\npie, en bicicleta, moto, automóvil o camión" as uc_004
  usecase "permitir adjuntar desde la aplicación móvil los\ndocumentos necesarios para revisar la solicitud de\nvinculación" as uc_005
}

actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_005
@enduml
```



## Casos cubiertos

- El sistema debe permitir crear la identidad de un nuevo repartidor después de verificar su correo.
- El sistema debe permitir al repartidor indicar si realiza entregas a pie, en bicicleta, moto, automóvil o camión.
- El sistema debe permitir adjuntar desde la aplicación móvil los documentos necesarios para revisar la solicitud de vinculación.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
