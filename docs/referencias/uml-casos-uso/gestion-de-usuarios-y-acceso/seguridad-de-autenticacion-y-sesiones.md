# Gestión de Usuarios y Acceso — Seguridad de autenticación y sesiones

> 6 requerimientos internos relacionados del subproceso.

## Requerimientos internos relacionados

- El sistema debe registrar intentos de inicio de sesión para proteger las cuentas ante accesos repetidos fallidos.
  - Tipo: control de seguridad interno.
  - Disparador: se intenta iniciar sesión.
  - Actor directo: no aplica.
  - Representación: regla interna trazada en este documento.
- El sistema debe exigir verificación de seguridad antes de crear una cuenta con correo y contraseña.
  - Tipo: validación interna.
  - Disparador: se solicita crear una cuenta con correo y contraseña.
  - Actor directo: no aplica.
  - Representación: control interno previo al registro.
- El sistema debe solicitar verificación adicional en el inicio de sesión nativo después de intentos fallidos repetidos.
  - Tipo: validación interna.
  - Disparador: se repiten intentos fallidos de inicio de sesión nativo.
  - Actor directo: no aplica.
  - Representación: control interno del acceso.
- El sistema debe aplicar bloqueo temporal cuando una misma combinación de credencial e IP supera el límite de fallos permitido.
  - Tipo: control de seguridad interno.
  - Disparador: una combinación de credencial e IP supera el límite de fallos.
  - Actor directo: no aplica.
  - Representación: regla interna trazada en este documento.
- El sistema debe exigir verificación de seguridad antes de enviar o reenviar códigos de recuperación de contraseña.
  - Tipo: validación interna.
  - Disparador: se solicita enviar o reenviar un código de recuperación.
  - Actor directo: no aplica.
  - Representación: control interno previo a la recuperación.
- El sistema debe administrar las sesiones activas para permitir el acceso seguro a la tienda y al panel administrativo.
  - Tipo: control de seguridad interno.
  - Disparador: existe una sesión autenticada que debe validarse o mantenerse.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
