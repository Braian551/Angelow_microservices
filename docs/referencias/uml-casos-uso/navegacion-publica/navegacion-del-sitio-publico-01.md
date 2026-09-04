# Navegación Pública — Navegación del sitio público (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Navegación Pública — Navegación del sitio público (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir abrir el menú principal en dispositivos\nmóviles" as uc_001
  usecase "permitir cerrar el menú móvil con botón o fondo" as uc_002
  usecase "permitir abrir la cuenta desde el icono del encabezado" as uc_003
  usecase "permitir abrir favoritos desde el icono del encabezado" as uc_004
  usecase "permitir abrir las notificaciones desde el icono del\nencabezado" as uc_005
  usecase "permitir abrir el carrito desde el encabezado" as uc_006
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_1 --> uc_003
actor_1 --> uc_004
actor_1 --> uc_005
actor_1 --> uc_006
@enduml
```



## Casos cubiertos

- El sistema debe permitir abrir el menú principal en dispositivos móviles.
- El sistema debe permitir cerrar el menú móvil con botón o fondo.
- El sistema debe permitir abrir la cuenta desde el icono del encabezado.
- El sistema debe permitir abrir favoritos desde el icono del encabezado.
- El sistema debe permitir abrir las notificaciones desde el icono del encabezado.
- El sistema debe permitir abrir el carrito desde el encabezado.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
