# Componentes compartidos del admin

## Regla

Antes de crear una tarjeta, encabezado, filtro, modal, paginación, estado vacío, loader, toggle o imagen de tabla, buscar y reutilizar el componente compartido existente. Si falta una capacidad, ampliar su API de forma compatible; no copiarlo dentro de una vista.

## Catálogo conocido

- `AdminStatsGrid`
- `AdminCard`
- `AdminPageHeader`
- `AdminFilterCard`
- `AdminResultsBar`
- `AdminPagination`
- `AdminModal`
- `AdminEmptyState`
- `AdminShimmer`
- `AdminTableShimmer`
- `AdminTableImage`
- `AdminToggleSwitch`

## Criterios de ampliación

1. Confirmar que el requisito no pueda resolverse mediante props, slots o composición existente.
2. Añadir la capacidad al componente canónico.
3. Mantener compatibilidad con consumidores actuales.
4. Validar desktop, tablet y móvil.
5. Actualizar el patrón o guía aplicable sin crear documentación redundante.
