# Gestión de Usuarios y Acceso — Autenticación y gestión de sesiones (parte 1)

> 6 casos de uso del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Autenticación y gestión de sesiones (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1
actor "Usuario" as actor_2

rectangle "Angelow" {
  usecase "permitir iniciar sesión con correo electrónico y\ncontraseña" as uc_008
  usecase "permitir iniciar sesión o crear cuenta usando Google" as uc_009
  usecase "permitir cerrar sesión desde la tienda, cuenta de\nusuario o panel administrativo" as uc_010
  usecase "permitir consultar los datos de la sesión activa para\nmostrar perfil, rol y permisos" as uc_011
  usecase "permitir validar primero el correo o usuario antes de\nmostrar el campo de contraseña" as uc_012
  usecase "permitir volver desde el paso de contraseña al paso de\ncorreo o usuario" as uc_013
}

actor_1 --> uc_008
actor_1 --> uc_009
actor_2 --> uc_010
actor_2 --> uc_011
actor_1 --> uc_012
actor_1 --> uc_013
@enduml
```



## Casos cubiertos

- El sistema debe permitir iniciar sesión con correo electrónico y contraseña.
- El sistema debe permitir iniciar sesión o crear cuenta usando Google.
- El sistema debe permitir cerrar sesión desde la tienda, cuenta de usuario o panel administrativo.
- El sistema debe permitir consultar los datos de la sesión activa para mostrar perfil, rol y permisos.
- El sistema debe permitir validar primero el correo o usuario antes de mostrar el campo de contraseña.
- El sistema debe permitir volver desde el paso de contraseña al paso de correo o usuario.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
