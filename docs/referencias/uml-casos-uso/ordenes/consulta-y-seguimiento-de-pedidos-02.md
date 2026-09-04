# Órdenes — Consulta y seguimiento de pedidos (parte 2)

> 1 requerimientos internos relacionados del subproceso.

## Requerimientos internos relacionados

- El sistema debe actualizar el badge y el componente de progreso del pedido cuando reciba eventos websocket de cambio de estado o pago.
  - Tipo: sincronización interna por evento.
  - Disparador: se recibe un evento WebSocket de cambio de estado o pago.
  - Actor directo: no aplica.
  - Contexto: pedido perteneciente al usuario.
  - Representación: actualización interna de la vista y de los datos derivados.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)

