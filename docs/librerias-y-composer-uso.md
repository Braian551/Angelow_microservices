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

## 2026-04-17 - Campañas de descuentos con PDF adjunto

- Tipo: dependencia backend (Composer)
- Paquete/version: `dompdf/dompdf@3.1.0`
- Motivo: generar en memoria el PDF del código de descuento para adjuntarlo en correos de campañas masivas y envíos a usuarios específicos.
- Comando usado: `composer require dompdf/dompdf:^3.1 --no-interaction --no-progress`
- Archivos donde se aplica:
  - `services/discount-service/composer.json`
  - `services/discount-service/composer.lock`
  - `services/discount-service/app/Support/DiscountPdfAttachmentHelper.php`
  - `services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php`
  - `services/discount-service/routes/api.php`
  - `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`
- Contexto funcional:
  - `POST /api/admin/discount-codes/campaign/mass`: envío masivo por notificación y/o correo con adjunto PDF.
  - `POST /api/admin/discount-codes/campaign/specific`: envío a usuarios seleccionados con la misma lógica de adjunto en memoria.

## 2026-04-17 - Facturación automática de órdenes (order-service)

- Tipo: dependencia backend (Composer)
- Paquete/version: `dompdf/dompdf@3.1.x`
- Motivo: generar factura PDF en memoria y adjuntarla en correos de facturación automática al cliente cuando la orden queda entregada y el pago verificado.
- Comando usado: `composer require dompdf/dompdf:^3.1 --no-interaction --no-progress`
- Archivos donde se aplica:
  - `services/order-service/composer.json`
  - `services/order-service/composer.lock`
  - `services/order-service/app/Services/OrderInvoiceService.php`
  - `services/order-service/app/Http/Controllers/OrderController.php`
  - `services/order-service/app/Http/Controllers/Admin/AdminInvoiceController.php`
  - `services/order-service/routes/api.php`
  - `frontend/src/services/invoiceApi.js`
  - `frontend/src/modules/admin/pages/AdminInvoicesPage.vue`
- Contexto funcional:
  - `POST /api/orders/{id}/send-confirmation`: envía correo de confirmación de checkout con resumen del pedido y datos del pago reportado.
  - Trigger automático de factura al actualizar `status` y `payment_status` en órdenes: cuando pasa a entregada/completada + pago validado, se genera PDF y se envía por correo.
  - `GET /api/admin/invoices`: lista facturas generadas desde microservicio y fallback legacy.
  - `GET /api/admin/invoices/{id}/download`: descarga PDF de factura.
  - `POST /api/admin/invoices/{id}/resend`: reenvía factura al correo del cliente.

## 2026-04-20 - Ajustes UI buscador/admin/perfil (sin dependencias nuevas)

- Tipo: registro de control (sin cambios de dependencias)
- Paquete/version: no aplica
- Motivo: correcciones de UI/UX y búsqueda en frontend sin instalar ni actualizar librerías.
- Comando usado: no aplica
- Archivos donde se aplica:
  - `frontend/src/modules/admin/components/AdminHeader.vue`
  - `frontend/src/modules/admin/pages/AdminAdministratorsPage.vue`
  - `frontend/src/modules/catalog/views/ProductDetailView.css`
  - `docs/patrones-diseno-admin-buscador-avatar-producto-2026-04-20.md`
- Contexto funcional:
  - Se corrigió búsqueda tolerante a acentos/sinónimos en header admin.
  - Se corrigió visualización de Último acceso.
  - Se mejoró UI de cambio de foto en modal de administradores.
  - Se refinó el botón Volver en detalle de producto.

## Plantilla para futuras entradas

- Fecha:
- Tipo: libreria frontend/backend o Composer
- Paquete/version:
- Motivo:
- Comando usado:
- Archivos donde se aplica:
- Contexto funcional:
