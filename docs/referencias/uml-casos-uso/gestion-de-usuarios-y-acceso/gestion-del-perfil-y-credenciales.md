# Gestión de Usuarios y Acceso — Gestión del perfil y credenciales

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Gestión del perfil y credenciales
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Usuario" as actor_1
actor "Cliente" as actor_2

rectangle "Angelow" {
  usecase "permitir al usuario actualizar nombre, teléfono y foto\nde perfil" as uc_016
  usecase "permitir cambiar la contraseña desde la configuración\nde cuenta" as uc_017
  usecase "permitir seleccionar una nueva foto y previsualizarla\nantes de guardar el perfil" as uc_018
  usecase "mostrar el correo de la cuenta como dato informativo\nde solo lectura dentro del perfil" as uc_019
  usecase "permitir ocultar el formulario de cambio de contraseña\nsin guardar cambios" as uc_020
  usecase "permitir cerrar sesión desde la pestaña de seguridad\nde la cuenta" as uc_021
}

actor_1 --> uc_016
actor_1 --> uc_017
actor_2 --> uc_018
actor_2 --> uc_019
actor_2 --> uc_020
actor_2 --> uc_021
@enduml
```



## Casos cubiertos

- El sistema debe permitir al usuario actualizar nombre, teléfono y foto de perfil.
- El sistema debe permitir cambiar la contraseña desde la configuración de cuenta.
- El sistema debe permitir seleccionar una nueva foto y previsualizarla antes de guardar el perfil.
- El sistema debe mostrar el correo de la cuenta como dato informativo de solo lectura dentro del perfil.
- El sistema debe permitir ocultar el formulario de cambio de contraseña sin guardar cambios.
- El sistema debe permitir cerrar sesión desde la pestaña de seguridad de la cuenta.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
