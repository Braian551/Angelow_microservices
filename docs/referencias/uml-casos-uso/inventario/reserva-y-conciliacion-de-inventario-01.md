# Inventario — Reserva y conciliación de inventario (parte 1)

> 6 requerimientos internos relacionados del subproceso.

## Requerimientos internos relacionados

- El sistema debe reservar inventario en registros del sistema al crear una orden.
  - Tipo: automatismo interno.
  - Disparador: se crea una orden.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado; la orden puede originarse en más de un flujo.
- El sistema debe permitir extender el tiempo de una reserva activa.
  - Tipo: automatismo interno.
  - Disparador: una reserva activa requiere ampliar su vigencia conforme a la operación documentada.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe confirmar la reserva de stock cuando la orden se consolida.
  - Tipo: automatismo interno.
  - Disparador: una orden se consolida.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe liberar stock reservado cuando una orden se cancela manual o automáticamente.
  - Tipo: automatismo interno.
  - Disparador: una orden se cancela.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe cancelar automáticamente las órdenes con reserva vencida mediante proceso operativo.
  - Tipo: automatismo interno.
  - Disparador: vence la reserva de una orden.
  - Actor directo: no aplica.
  - Representación: proceso operativo autónomo trazado en este documento.
- El sistema debe reconciliar las reservas activas de inventario entre base de datos y Redis para evitar bloqueos de compra por claves huérfanas.
  - Tipo: sincronización interna.
  - Disparador: se ejecuta la conciliación operativa de reservas activas.
  - Actor directo: no aplica.
  - Representación: proceso operativo autónomo trazado en este documento.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
