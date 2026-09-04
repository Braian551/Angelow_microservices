# Gestión de Usuarios y Acceso — Control de acceso y autorización

> 3 requerimientos internos relacionados del subproceso.

## Requerimientos internos relacionados

- El sistema debe bloquear el acceso al panel administrativo a usuarios sin rol administrador.
  - Tipo: regla de autorización interna.
  - Disparador: un usuario sin rol administrador intenta acceder al panel administrativo.
  - Actor directo: no aplica.
  - Contexto: pantalla administrativa protegida.
  - Representación: control de acceso y redirección interna.

- El sistema debe redirigir al usuario a su área correspondiente según el rol después de iniciar sesión.
  - Tipo: control de acceso interno.
  - Disparador: finaliza un inicio de sesión válido.
  - Actor directo: no aplica.
  - Contexto: sesión autenticada.
  - Representación: resultado interno del inicio de sesión.

- El sistema debe exigir sesión iniciada para entrar a las pantallas de cuenta, pedidos, direcciones, favoritos y configuración.
  - Tipo: regla de autorización interna.
  - Disparador: se solicita una pantalla protegida.
  - Actor directo: no aplica.
  - Contexto: ruta protegida.
  - Representación: control de acceso transversal.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)

