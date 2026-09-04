# Ayuda y Manual de Usuario — Orientación para clientes

> 1 casos de uso del subproceso.

```plantuml
@startuml
title Ayuda y Manual de Usuario — Orientación para clientes
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Cliente" as actor_1

rectangle "Angelow" {
  usecase "permitir al cliente autenticado iniciar recorridos\nsobre todas las vistas públicas, de checkout y de su\ncuenta" as uc_003
}

actor_1 --> uc_003
@enduml
```



## Casos cubiertos

- El sistema debe permitir al cliente autenticado iniciar recorridos sobre todas las vistas públicas, de checkout y de su cuenta.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
