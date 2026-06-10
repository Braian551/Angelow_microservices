# Registro de librerias y dependencias Composer

<!-- indice:auto:start -->
## Índice rápido

- [2026-05-24 - Exportaciones admin reutilizables en PDF y Excel](#2026-05-24---exportaciones-admin-reutilizables-en-pdf-y-excel)
- [2026-04-03 - Chart.js 4.4.0](#2026-04-03---chartjs-440)
- [2026-04-15 - Exportación de productos CSV + PDF](#2026-04-15---exportación-de-productos-csv-pdf)
- [2026-04-17 - Campañas de descuentos con PDF adjunto](#2026-04-17---campañas-de-descuentos-con-pdf-adjunto)
- [2026-04-17 - Facturación automática de órdenes (order-service)](#2026-04-17---facturación-automática-de-órdenes-order-service)
- [2026-04-20 - Ajustes UI buscador/admin/perfil (sin dependencias nuevas)](#2026-04-20---ajustes-ui-buscador-admin-perfil-sin-dependencias-nuevas)
- [Plantilla para futuras entradas](#plantilla-para-futuras-entradas)
<!-- indice:auto:end -->

Este archivo centraliza las dependencias agregadas/usadas para tareas funcionales y donde quedaron aplicadas.

## 2026-05-24 - Exportaciones admin reutilizables en PDF y Excel

- Tipo: librería frontend (npm)
- Paquete/version: `exceljs@4.4.0`
- Motivo: generar archivos Excel reales con estilos, anchos, tipos numéricos y formato monetario compartido para las exportaciones administrativas.
- Comando usado: `npm install exceljs jspdf jspdf-autotable`
- Archivos donde se aplica:
  - `frontend/package.json`
  - `frontend/package-lock.json`
  - `frontend/src/modules/admin/composables/useAdminDataExport.js`
  - `frontend/src/modules/admin/components/AdminExportActions.vue`
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
  - `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
  - `frontend/src/modules/admin/pages/AdminCustomersPage.vue`
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
  - `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`
  - `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue`
  - `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`
  - `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
- Contexto funcional:
  - Se reemplazaron exportaciones CSV ad hoc por un libro Excel unificado con tipado y estilo compartidos.
  - El mismo workbook compartido ahora usa un encabezado más compacto, con bloque institucional blanco y logo anclado en una sola columna y centrado dentro de su celda para reducir espacio sobrante.

- Tipo: librería frontend (npm)
- Paquete/version: `jspdf@4.2.1`
- Motivo: generar PDF desde el frontend con branding vigente, logo actual del sitio y soporte de plantillas compartidas para el módulo admin.
- Comando usado: `npm install exceljs jspdf jspdf-autotable`
- Archivos donde se aplica:
  - `frontend/package.json`
  - `frontend/package-lock.json`
  - `frontend/src/modules/admin/composables/useAdminDataExport.js`
  - `frontend/src/modules/admin/components/AdminExportActions.vue`
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
  - `frontend/src/modules/admin/pages/AdminCustomersPage.vue`
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
  - `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`
  - `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
- Contexto funcional:
  - El PDF comparte cabecera, pie, metadatos y branding para todas las vistas administrativas migradas.
  - La cabecera PDF se refinó para separar bloque institucional, contacto y título del reporte sin duplicar plantillas por vista.
  - El bloque superior del PDF ahora sale sobre superficie blanca, sin línea azul horizontal encima del logo, y con altura suficiente para que contacto y fecha de generación no se sobrepongan.

- Tipo: librería frontend (npm)
- Paquete/version: `jspdf-autotable@5.0.8`
- Motivo: renderizar tablas PDF reutilizables con soporte de columnas declarativas e imágenes por fila cuando la vista lo requiere.
- Comando usado: `npm install exceljs jspdf jspdf-autotable`
- Archivos donde se aplica:
  - `frontend/package.json`
  - `frontend/package-lock.json`
  - `frontend/src/modules/admin/composables/useAdminDataExport.js`
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
  - `frontend/src/modules/admin/pages/AdminOrdersPage.vue`
  - `frontend/src/modules/admin/pages/AdminCustomersPage.vue`
  - `frontend/src/modules/admin/pages/AdminReviewsPage.vue`
  - `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`
  - `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`
  - `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue`
  - `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`
  - `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`
  - `frontend/src/modules/admin/pages/AdminReportsPage.vue`
- Contexto funcional:
  - La tabla PDF ahora sale de un único contrato declarativo que comparte columnas, layout y soporte visual entre productos, inventario, órdenes, clientes, reseñas, preguntas, envíos, descuentos, anuncios e informes.
  - Las imágenes del PDF respetan proporción y reutilizan la misma infraestructura del encabezado y de las celdas visuales.

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
  - `docs/patrones/admin/patrones-diseno-admin-buscador-avatar-producto-2026-04-20.md`
- Contexto funcional:
  - Se corrigió búsqueda tolerante a acentos/sinónimos en header admin.
  - Se corrigió visualización de Último acceso.
  - Se mejoró UI de cambio de foto en modal de administradores.
  - Se refinó el botón Volver en detalle de producto.

## 2026-06-07 - Gateway websocket global para stock reservado e inventario

- Tipo: dependencias Node para microservicio auxiliar realtime
- Paquete/version: `redis@^4.7.0`, `ws@^8.18.0`
- Motivo: exponer por websocket global los eventos de reservas, confirmaciones, ajustes y transferencias de stock publicados en Redis para sincronizar carrito, producto y admin en tiempo real.
- Comando usado: `npm install redis@^4.7.0 ws@^8.18.0`
- Archivos donde se aplica:
  - `services/realtime-gateway/package.json`
  - `services/realtime-gateway/Dockerfile`
  - `services/realtime-gateway/server.js`
  - `docker-compose.yml`
  - `services/catalog-service/app/Services/StockRealtimePublisher.php`
  - `services/catalog-service/config/services.php`
  - `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
  - `services/catalog-service/app/Http/Controllers/InternalCatalogController.php`
  - `frontend/src/composables/useStockRealtime.js`
  - `frontend/src/services/catalogApi.js`
  - `frontend/src/modules/catalog/pages/ProductDetailPage.vue`
  - `frontend/src/modules/cart/pages/CartPage.vue`
  - `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
- Contexto funcional:
  - `order-service` ya emitía eventos de reserva/liberación/confirmación hacia Redis; ahora `catalog-service` también publica ajustes, transferencias y commits del inventario al mismo canal base `ws:orders:stock`.
  - `realtime-gateway` se suscribe a Redis y retransmite el stream a `ws://localhost:8090`, dejando un punto único de escucha para todo el frontend.
  - El frontend usa `WebSocket` nativo y un composable compartido para refrescar stock en detalle de producto, carrito y admin inventario sin agregar una librería cliente extra.

## Plantilla para futuras entradas

- Fecha:
- Tipo: libreria frontend/backend o Composer
- Paquete/version:
- Motivo:
- Comando usado:
- Archivos donde se aplica:
- Contexto funcional:
