# Repartidores y Entregas — Autenticación y autorización de repartidores

> 2 casos de uso del subproceso.

```plantuml
@startuml
title Repartidores y Entregas — Autenticación y autorización de repartidores
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Repartidor" as actor_1

rectangle "Angelow" {
  usecase "mostrar y validar en la aplicación móvil un código de\nseis dígitos mediante un campo OTP segmentado antes de\ndeterminar el siguiente paso de acceso" as uc_001
  usecase "permitir el inicio de sesión en la aplicación móvil\núnicamente a cuentas con rol de repartidor" as uc_002
}

actor_1 --> uc_001
actor_1 --> uc_002
@enduml
```



## Casos cubiertos

- El sistema debe mostrar y validar en la aplicación móvil un código de seis dígitos mediante un campo OTP segmentado antes de determinar el siguiente paso de acceso.
- El sistema debe permitir el inicio de sesión en la aplicación móvil únicamente a cuentas con rol de repartidor.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
