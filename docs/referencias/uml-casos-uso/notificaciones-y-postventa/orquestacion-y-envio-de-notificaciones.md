# Notificaciones y Postventa — Orquestación y envío de notificaciones

> 5 requerimientos internos relacionados del subproceso.

## Requerimientos internos relacionados

- El sistema debe permitir crear notificaciones del sistema desde procesos del sistema.
  - Tipo: orquestación interna.
  - Disparador: un proceso interno produce una notificación.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe permitir disparar notificaciones por eventos operativos.
  - Tipo: orquestación interna.
  - Disparador: ocurre un evento operativo.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe crear registros en cola de envío para notificaciones del sistema o correos.
  - Tipo: orquestación interna.
  - Disparador: se crea una notificación o correo pendiente de envío.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe respetar preferencias de notificación antes de enviar alertas o correos.
  - Tipo: validación interna.
  - Disparador: se prepara el envío de una alerta o correo.
  - Actor directo: no aplica.
  - Representación: regla interna trazada en este documento.
- El sistema debe enviar correo para eventos de producto, promoción, carrito, pedidos o reembolsos cuando el canal esté permitido.
  - Tipo: automatismo interno.
  - Disparador: un evento habilitado requiere notificar por correo y el canal está permitido.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
