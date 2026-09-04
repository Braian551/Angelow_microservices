# Gestión de Usuarios y Acceso — Recuperación de acceso (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Recuperación de acceso (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1

rectangle "Angelow" {
  usecase "permitir solicitar un código de recuperación de\ncontraseña" as uc_022
  usecase "permitir reenviar el código de recuperación cuando el\nusuario lo solicite" as uc_023
  usecase "permitir verificar el código de recuperación ingresado\npor el usuario" as uc_024
  usecase "permitir restablecer la contraseña después de\nverificar el código" as uc_025
  usecase "mostrar tiempo restante y enfriamiento para solicitar\no reenviar códigos" as uc_026
  usecase "permitir regresar al paso de identificación desde la\nverificación de código" as uc_027
}

actor_1 --> uc_022
actor_1 --> uc_023
actor_1 --> uc_024
actor_1 --> uc_025
actor_1 --> uc_026
actor_1 --> uc_027
@enduml
```



## Casos cubiertos

- El sistema debe permitir solicitar un código de recuperación de contraseña.
- El sistema debe permitir reenviar el código de recuperación cuando el usuario lo solicite.
- El sistema debe permitir verificar el código de recuperación ingresado por el usuario.
- El sistema debe permitir restablecer la contraseña después de verificar el código.
- El sistema debe mostrar tiempo restante y enfriamiento para solicitar o reenviar códigos.
- El sistema debe permitir regresar al paso de identificación desde la verificación de código.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
