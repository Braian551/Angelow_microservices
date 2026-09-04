# Gestión de Usuarios y Acceso — Registro y verificación de usuarios (parte 1)

> 5 casos de uso y 1 requerimientos internos relacionados del subproceso.

```plantuml
@startuml
title Gestión de Usuarios y Acceso — Registro y verificación de usuarios (parte 1)
left to right direction
skinparam shadowing false
skinparam packageStyle rectangle

actor "Visitante" as actor_1
actor "Usuario" as actor_2

rectangle "Angelow" {
  usecase "permitir a los visitantes registrarse creando una\ncuenta con nombre, correo electrónico, teléfono y\ncontraseña" as uc_001
  usecase "permitir avanzar y retroceder entre los pasos del\nformulario de registro" as uc_002
  usecase "exigir aceptación de términos y condiciones en el\nregistro" as uc_004
  usecase "permitir mostrar u ocultar contraseña y confirmación\ndurante el registro" as uc_005
  usecase "enviar y validar un código de cuatro dígitos antes de\npermitir continuar al paso de teléfono del registro" as uc_006
}

actor_1 --> uc_001
actor_1 --> uc_002
actor_2 --> uc_004
actor_2 --> uc_005
actor_1 --> uc_006
@enduml
```



## Casos cubiertos

- El sistema debe permitir a los visitantes registrarse creando una cuenta con nombre, correo electrónico, teléfono y contraseña.
- El sistema debe permitir avanzar y retroceder entre los pasos del formulario de registro.
- El sistema debe exigir aceptación de términos y condiciones en el registro.
- El sistema debe permitir mostrar u ocultar contraseña y confirmación durante el registro.
- El sistema debe enviar y validar un código de cuatro dígitos antes de permitir continuar al paso de teléfono del registro.


## Requerimientos internos relacionados

## Requerimientos internos relacionados

- El sistema debe validar nombre, correo, teléfono, contraseña, confirmación y términos mientras el usuario completa el registro.
  - Tipo: validación interna.
  - Disparador: el usuario modifica datos durante el registro.
  - Actor directo: no aplica.
  - Contexto: formulario de registro.
  - Representación: validación de campos previa al envío final.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
