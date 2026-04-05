# Registro de librerias y dependencias Composer

Este archivo centraliza las dependencias agregadas/usadas para tareas funcionales y donde quedaron aplicadas.

## 2026-04-03 - Chart.js 4.4.0

- Tipo: libreria frontend (npm)
- Paquete/version: `chart.js@4.4.0`
- Motivo: habilitar graficos funcionales en dashboard e informes admin con paridad funcional respecto a Angelow legacy.
- Comando usado: `npm install chart.js@4.4.0`
- Archivos donde se aplica:
  - `frontend/src/modules/admin/pages/AdminDashboardPage.vue`
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
  - `frontend/package.json`
  - `frontend/package-lock.json`
- Contexto funcional:
  - Dashboard: grafico de rendimiento de ventas (linea doble eje) y grafico de estado de ordenes (doughnut).
  - Informes: evolucion de ventas, comparativa mensual, top productos, cantidad vendida, distribucion de clientes y top clientes por valor.

## Plantilla para futuras entradas

- Fecha:
- Tipo: libreria frontend/backend o Composer
- Paquete/version:
- Motivo:
- Comando usado:
- Archivos donde se aplica:
- Contexto funcional:
