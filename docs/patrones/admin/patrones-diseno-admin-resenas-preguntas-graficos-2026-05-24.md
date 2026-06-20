# Patrones de diseño aplicados (reseñas y preguntas admin + gráficos)

<!-- indice:auto:start -->
## Índice rápido

- [Contexto del problema](#contexto-del-problema)
- [Patrón 1: Template Method + Strategy](#patrón-1-template-method-strategy)
- [Patrón 2: Composition](#patrón-2-composition)
- [Resultado esperado](#resultado-esperado)
- [Extensión 2026-06-07: redistribución de acciones del modal de reseñas](#extensión-2026-06-07-redistribución-de-acciones-del-modal-de-reseñas)
  - [Patrón 3: Composition + Design System](#patrón-3-composition-design-system)
<!-- indice:auto:end -->

Fecha: 2026-05-24

## Contexto del problema

- Las tarjetas `Distribución de rating` y `Estado de preguntas` usaban barras manuales dentro de las vistas, aunque el proyecto ya tenía `chart.js` integrado y reutilizado en dashboard/reportes.
- El buscador de reseñas y preguntas aparecía por encima de los insights, lo que debilitaba la jerarquía visual de los indicadores operativos.
- El lifecycle de `Chart.js` amenazaba con duplicarse si cada vista resolvía por separado el alta, reciclaje y destrucción de instancias.

## Patrón 1: Template Method + Strategy

- Referencia: https://refactoring.guru/es/design-patterns/template-method
- Referencia complementaria: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: evitar duplicación del lifecycle de `Chart.js` y permitir que cada vista admin cambie tipo, datasets y opciones sin reescribir el flujo de render.
- Aplicación exacta:
  - `frontend/src/modules/admin/components/AdminChartPanel.vue`
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
  - `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`
- Implementación clave:
  - `AdminChartPanel.vue` fija la secuencia `destruir -> esperar canvas -> renderizar -> reciclar instancia`.
  - Cada vista inyecta su estrategia de visualización mediante `type`, `labels`, `datasets` y `options`.
  - Reseñas usa estrategia `bar` horizontal para distribución de rating.
  - Preguntas usa estrategia `doughnut` para el estado de atención.

## Patrón 2: Composition

- Referencia: https://refactoring.guru/es/design-patterns/composite
- Problema que resuelve: mantener la jerarquía visual del admin reutilizando piezas existentes en vez de reconstruir bloques ad hoc.
- Aplicación exacta:
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
  - `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`
  - `frontend/src/modules/admin/styles/admin.css`
- Implementación clave:
  - Las vistas combinan `AdminCard`, `AdminChartPanel`, `AdminFilterCard` y `AdminResultsBar` sin alterar el shell compartido del admin.
  - El buscador se reubica debajo de los insights para priorizar primero el resumen visual y luego la segmentación operativa.
  - La clase compartida `admin-insights-search-panel` evita repetir ajustes de separación visual entre vistas.

## Resultado esperado

- Reseñas y preguntas muestran gráficos reales con la librería ya integrada en el proyecto.
- El layout prioriza primero los insights y después el buscador/filtros.
- El lifecycle de gráficos queda centralizado en un componente reusable del módulo admin.

## Extensión 2026-06-07: redistribución de acciones del modal de reseñas

### Patrón 3: Composition + Design System

- Referencia: https://refactoring.guru/es/design-patterns/composite
- Problema que resuelve: la tarjeta `Acciones` del modal de detalle mostraba una pila de botones muy larga y poco balanceada, lo que debilitaba la lectura del bloque de moderación y empeoraba el reparto visual en anchos intermedios.
- Aplicación exacta:
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
- Implementación clave:
  - la sección se reorganiza como una cuadrícula responsiva de acciones reutilizando la misma tarjeta `AdminCard` y la semántica de comandos existente, sin crear un modal paralelo ni componentes ad hoc fuera del flujo del admin;
  - cada acción añade una breve descripción operativa para mejorar escaneo visual y dejar más clara la intención de publicar, devolver a revisión, verificar o eliminar.
