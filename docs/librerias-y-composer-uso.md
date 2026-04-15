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

## 2026-04-15 - Exportación de productos CSV + PDF

- Tipo: dependencia backend (Composer)
- Paquete/version: `league/csv@9.18.0`
- Motivo: generar CSV de productos con delimitador compatible con Excel en español y BOM UTF-8 para evitar mojibake en acentos/ñ.
- Comando usado: `composer require league/csv:^9.18 dompdf/dompdf:^3.1 --no-interaction`
- Archivos donde se aplica:
  - `services/catalog-service/composer.json`
  - `services/catalog-service/composer.lock`
  - `services/catalog-service/routes/api.php`
  - `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/services/http.js`
- Contexto funcional:
  - `GET /api/admin/products/export/csv`: exporta productos filtrados con corrección UTF-8 on-the-fly.
  - `GET /api/admin/products/export/pdf`: exporta productos filtrados en PDF tabular.
  - La vista admin de productos reutiliza la barra de acciones existente para descargar CSV y PDF sin implementación ad hoc.

- Tipo: dependencia backend (Composer)
- Paquete/version: `dompdf/dompdf@3.1.0`
- Motivo: generar PDF de productos desde `catalog-service` para la nueva acción `Exportar PDF`.
- Comando usado: `composer require league/csv:^9.18 dompdf/dompdf:^3.1 --no-interaction`
- Archivos donde se aplica:
  - `services/catalog-service/composer.json`
  - `services/catalog-service/composer.lock`
  - `services/catalog-service/routes/api.php`
  - `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/services/http.js`
- Contexto funcional:
  - Se genera un reporte PDF de productos con columnas: ID, Nombre, Categoría, Stock, Precio Min, Precio Max y Estado.
  - El frontend descarga el archivo binario respetando `Content-Disposition` del backend.

## Plantilla para futuras entradas

- Fecha:
- Tipo: libreria frontend/backend o Composer
- Paquete/version:
- Motivo:
- Comando usado:
- Archivos donde se aplica:
- Contexto funcional:
