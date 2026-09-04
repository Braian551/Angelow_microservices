# Gestión de Usuarios y Acceso — Recuperación de acceso (parte 2)

> 5 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Recuperación de acceso (parte 2)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Administrador" as actor_1
actor "Visitante" as actor_2

rectangle "Angelow" {
  usecase "permitir solicitar un código de recuperación desde la\npantalla administrativa" as uc_028
  usecase "permitir reenviar el código de recuperación\nadministrativa" as uc_029
  usecase "permitir verificar el código recibido para\nrecuperación administrativa" as uc_030
  usecase "permitir restablecer la contraseña administrativa\ndespués de validar el código" as uc_031
  usecase "permitir mostrar u ocultar la contraseña y su\nconfirmación en recuperación administrativa" as uc_032
}

actor_1 --> uc_028
actor_1 --> uc_029
actor_1 --> uc_030
actor_1 --> uc_031
actor_1 --> uc_032
@enduml
```



## Casos cubiertos

- El sistema debe permitir solicitar un código de recuperación desde la pantalla administrativa.
- El sistema debe permitir reenviar el código de recuperación administrativa.
- El sistema debe permitir verificar el código recibido para recuperación administrativa.
- El sistema debe permitir restablecer la contraseña administrativa después de validar el código.
- El sistema debe permitir mostrar u ocultar la contraseña y su confirmación en recuperación administrativa.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe usar el mismo componente visual para ingresar y reenviar códigos en registro y recuperación de contraseña.
  - Tipo: regla de interfaz interna.
  - Disparador: se muestran los flujos de ingreso o reenvío de códigos.
  - Actor directo: no aplica.
  - Contexto: registro o recuperación de acceso.
  - Representación: reutilización interna de componente visual.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
