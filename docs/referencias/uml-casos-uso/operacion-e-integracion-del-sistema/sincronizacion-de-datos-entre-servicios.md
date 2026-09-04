# Operación e Integración del Sistema — Sincronización de datos entre servicios

> 4 requerimientos internos relacionados del subproceso.

## Requerimientos internos relacionados

- El sistema debe permitir consultar perfiles de usuarios para completar pedidos, reportes y atención al cliente.
  - Tipo: sincronización interna.
  - Disparador: una operación de pedidos, reportes o atención necesita completar datos de un usuario.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe permitir consultar información de un producto para usarla en pedidos, carrito, pagos o reportes.
  - Tipo: sincronización interna.
  - Disparador: una operación de pedidos, carrito, pagos o reportes necesita datos del producto.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe permitir consultar una variante para validar disponibilidad antes de vender.
  - Tipo: validación interna.
  - Disparador: una operación de venta necesita comprobar la disponibilidad de una variante.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe confirmar el inventario vendido cuando una orden queda creada.
  - Tipo: sincronización interna.
  - Disparador: una orden queda creada.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento; no se asocia a un único caso base porque la creación puede originarse por flujos distintos.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
