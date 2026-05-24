# Patrones y deuda técnica aplicados (tablas admin + regla de mantenimiento)

Fecha: 2026-05-24

## Contexto del problema
- Las tablas del admin que usan cards `flush` dejaban una franja vacía en el borde izquierdo, por lo que la cabecera azul no ocupaba visualmente todo el ancho disponible.
- La causa estaba en el wrapper compartido `.table-responsive`, que reservaba `scrollbar-gutter: stable both-edges` y generaba un gutter fantasma para todas las tablas que reutilizan ese contenedor.
- También se reforzó la skill del proyecto para obligar la revisión y corrección de duplicación, código innecesario y deuda técnica local durante cada intervención.

## Patrón 1: Flyweight (comportamiento compartido del wrapper de tablas)
- Referencia: https://refactoring.guru/es/design-patterns/flyweight
- Problema que resuelve: evitar correcciones ad hoc por vista y centralizar en un único punto el comportamiento visual del contenedor reutilizable de tablas.
- Aplicación exacta:
  - `frontend/src/modules/admin/styles/admin.css`
- Implementación clave:
  - El wrapper compartido `.table-responsive` pasó de `scrollbar-gutter: stable both-edges` a `scrollbar-gutter: stable`.
  - Con esto se conserva la estabilidad del scroll sin reservar un gutter artificial en el borde izquierdo.
  - La corrección elimina una deuda técnica visual global sin duplicar overrides en páginas como dashboard, órdenes, facturas o clientes.

## Ajuste de mantenimiento: gobernanza de deuda técnica local
- No aplica un patrón de diseño catalogado de Refactoring Guru, porque el cambio corresponde a reglas operativas del agente y no a una estructura de software del producto.
- Aplicación exacta:
  - `.agents/skills/skill/SKILL.md`
  - `SKILL.md`
- Regla agregada:
  - Si durante una intervención se detecta código duplicado, innecesario o que incremente deuda técnica local, debe corregirse en la misma tarea cuando sea seguro y acotado.
  - Si no puede resolverse sin ampliar alcance, el hallazgo, el impacto y el siguiente paso deben quedar documentados antes de cerrar.

## Resultado esperado
- Las tablas del admin vuelven a ocupar visualmente todo el ancho útil de sus cards `flush`, sin la franja vacía lateral.
- La corrección se mantiene en un único punto compartido y evita parches repetidos por pantalla.
- La skill ahora obliga a detectar y resolver deuda técnica local o, en su defecto, a documentarla explícitamente.