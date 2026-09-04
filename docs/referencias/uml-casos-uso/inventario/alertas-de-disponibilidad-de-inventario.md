# Inventario — Alertas de disponibilidad de inventario

> 3 requerimientos internos relacionados del subproceso.

## Requerimientos internos relacionados

- El sistema debe sincronizar el estado de alerta cuando una variante queda sin stock o recupera unidades.
  - Tipo: sincronización interna.
  - Disparador: una variante queda sin stock o recupera unidades.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe enviar correo inicial cuando una variante llega a cero unidades.
  - Tipo: automatismo interno.
  - Disparador: una variante llega a cero unidades.
  - Actor directo: no aplica.
  - Representación: comportamiento interno trazado en este documento.
- El sistema debe enviar recordatorios de reposición para variantes que siguen agotadas.
  - Tipo: automatismo interno.
  - Disparador: una variante continúa agotada conforme al proceso operativo.
  - Actor directo: no aplica.
  - Representación: proceso operativo autónomo trazado en este documento.

## Referencias

- [Índice de diagramas](../README.md)
- [Matriz de requerimientos funcionales](../../matriz-requerimientos-funcionales-actualizada.md)
- [Especificación de casos de uso](../../casos-uso-angelow.md)
