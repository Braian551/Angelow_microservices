# Registro de librerías, dependencias Composer, fuentes y paleta visual

<!-- indice:auto:start -->
## Índice rápido

- [Alcance de este registro](#alcance-de-este-registro)
- [Inventario Composer por servicio](#inventario-composer-por-servicio)
- [Inventario npm y Node](#inventario-npm-y-node)
- [Fuentes usadas por el sitio](#fuentes-usadas-por-el-sitio)
- [Paleta de colores del sitio](#paleta-de-colores-del-sitio)
- [Uso funcional documentado](#uso-funcional-documentado)
- [Fuentes revisadas](#fuentes-revisadas)
- [Plantilla para futuras entradas](#plantilla-para-futuras-entradas)
<!-- indice:auto:end -->

## Alcance de este registro

Este documento centraliza las librerías declaradas directamente por el proyecto en `composer.json` y `package.json`, además de las fuentes tipográficas y colores base usados por el sitio. No enumera dependencias transitivas internas de `vendor` o `node_modules`, porque esas llegan como soporte automático de los paquetes principales y se controlan desde los archivos lock.

No se instalaron dependencias nuevas en esta revisión. La actualización se hizo por inventario documental el 2026-06-17.

## Inventario Composer por servicio

| Servicio | Paquete | Versión declarada | Versión instalada | Uso principal |
|---|---|---:|---:|---|
| Todos los servicios Laravel | `php` | `^8.2` | Entorno PHP | Lenguaje base de ejecución backend. |
| `audit-service` | `laravel/framework` | `^12.0` | `v12.52.0` | API de auditoría y estructura base del servicio. |
| `audit-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de rutas y autenticación por token cuando aplica. |
| `audit-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `auth-service` | `laravel/framework` | `^12.0` | `v12.51.0` | Registro, inicio de sesión, perfil y recuperación de cuenta. |
| `auth-service` | `laravel/sanctum` | `^4.3` | `v4.3.1` | Tokens de acceso para sesiones protegidas. |
| `auth-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `auth-service` | `phpmailer/phpmailer` | `^6.9` | `v6.12.0` | Envío de correos de bienvenida, recuperación y flujos de cuenta. |
| `cart-service` | `laravel/framework` | `^12.0` | `v12.52.0` | Gestión del carrito y recordatorios de carrito abandonado. |
| `cart-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de acciones cuando se requiere token. |
| `cart-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `catalog-service` | `laravel/framework` | `^12.0` | `v12.52.0` | Productos, categorías, colecciones, favoritos, configuración visual y reportes de catálogo. |
| `catalog-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de rutas administrativas del catálogo. |
| `catalog-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `catalog-service` | `dompdf/dompdf` | `3.1` | `v3.1.0` | Generación de PDF de productos desde backend. |
| `catalog-service` | `league/csv` | `9.18` | `9.18.0` | Exportación CSV de productos con compatibilidad para Excel. |
| `discount-service` | `laravel/framework` | `^12.0` | `v12.52.0` | Códigos de descuento, campañas y reglas por cantidad. |
| `discount-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de rutas administrativas de descuentos. |
| `discount-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `discount-service` | `dompdf/dompdf` | `3.1` | `v3.1.0` | PDF adjunto para campañas de códigos de descuento. |
| `notification-service` | `laravel/framework` | `^12.0` | `v12.52.0` | Notificaciones, preferencias, anuncios y envíos programados. |
| `notification-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de rutas administrativas de notificaciones. |
| `notification-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `order-service` | `laravel/framework` | `^12.0` | `v12.52.0` | Órdenes, estados, facturas, reservas y reportes. |
| `order-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de rutas administrativas de órdenes. |
| `order-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `order-service` | `dompdf/dompdf` | `^3.1` | `v3.1.5` | Facturas PDF, descarga y reenvío de facturas. |
| `payment-service` | `laravel/framework` | `^12.0` | `v12.52.0` | Pagos, bancos y cuenta bancaria de transferencia. |
| `payment-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de rutas administrativas de pagos. |
| `payment-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |
| `shipping-service` | `laravel/framework` | `^12.0` | `v12.52.0` | Direcciones, métodos de envío y reglas de precio. |
| `shipping-service` | `laravel/sanctum` | `^4.0` | `v4.3.1` | Protección de rutas administrativas de envíos. |
| `shipping-service` | `laravel/tinker` | `^2.10.1` | `v2.11.1` | Consola interactiva de soporte Laravel. |

### Dependencias Composer de desarrollo

Todos los servicios Laravel declaran el mismo grupo de soporte de desarrollo: `fakerphp/faker`, `laravel/pail`, `laravel/pint`, `laravel/sail`, `mockery/mockery`, `nunomaduro/collision` y `phpunit/phpunit`.

Estas dependencias se usan para datos de prueba, logs de desarrollo, formato de código, entorno auxiliar, dobles de prueba, manejo de errores en desarrollo y pruebas automatizadas.

## Inventario npm y Node

### Frontend principal

| Paquete | Versión declarada | Uso principal | Archivos de referencia |
|---|---:|---|---|
| `vue` | `^3.5.22` | Construcción de la SPA pública, cuenta de usuario y panel administrativo. | `frontend/package.json`, `frontend/src/main.js`, `frontend/src/modules/**/*.vue` |
| `vue-router` | `^4.6.3` | Navegación SPA entre tienda, checkout, cuenta y administración. | `frontend/src/router/index.js` |
| `axios` | `^1.13.5` | Comunicación HTTP con servicios de autenticación, catálogo, carrito, envíos, pagos, órdenes, descuentos y notificaciones. | `frontend/src/services/http.js`, `frontend/src/services/*.js` |
| `firebase` | `^12.11.0` | Integración de autenticación con Google desde frontend. | `frontend/src/services/firebase.js`, `frontend/src/modules/auth/pages/LoginPage.vue`, `frontend/src/modules/auth/pages/RegisterPage.vue` |
| `@fortawesome/fontawesome-free` | `^7.2.0` | Iconografía visual del sitio y panel administrativo. | `frontend/src/main.js`, componentes Vue y vistas CSS con clases `fa-*` |
| `chart.js` | `^4.4.0` | Gráficos del dashboard administrativo e informes. | `frontend/src/modules/admin/components/AdminChartPanel.vue`, `frontend/src/modules/admin/composables/useAdminReports.js` |
| `exceljs` | `^4.4.0` | Exportaciones administrativas a Excel con formato reutilizable. | `frontend/src/modules/admin/composables/useAdminDataExport.js` |
| `jspdf` | `^4.2.1` | Exportaciones administrativas a PDF desde frontend. | `frontend/src/modules/admin/composables/useAdminDataExport.js` |
| `jspdf-autotable` | `^5.0.8` | Tablas PDF administrativas con columnas declarativas. | `frontend/src/modules/admin/composables/useAdminDataExport.js` |
| `@vitejs/plugin-vue` | `^6.0.1` | Compilación de componentes Vue en Vite. | `frontend/vite.config.js` |
| `vite` | `^7.3.1` | Servidor de desarrollo y compilación del frontend. | `frontend/package.json`, `frontend/vite.config.js` |

### Paquetes Node en la raíz del proyecto

| Paquete | Versión declarada | Uso principal | Archivos de referencia |
|---|---:|---|---|
| `bcryptjs` | `^3.0.3` | Utilidad de cifrado en flujos Node de soporte o prototipos del repositorio. | `package.json` |
| `cors` | `^2.8.6` | Configuración CORS en flujos Node de soporte. | `package.json` |
| `dotenv` | `^17.3.1` | Carga de variables de entorno en flujos Node. | `package.json` |
| `express` | `^5.2.1` | Servidor HTTP Node de soporte cuando se use el paquete raíz. | `package.json` |
| `joi` | `^18.0.2` | Validación de datos en flujos Node. | `package.json` |
| `jsonwebtoken` | `^9.0.3` | Manejo de tokens JWT en flujos Node. | `package.json` |
| `knex` | `^3.1.0` | Acceso a base de datos desde scripts o servicios Node. | `package.json` |
| `mysql2` | `^3.18.0` | Conexión MySQL desde flujos Node. | `package.json` |
| `nodemon` | `^3.1.14` | Reinicio automático en desarrollo Node. | `package.json` |

### Gateway realtime

| Paquete | Versión declarada | Uso principal | Archivos de referencia |
|---|---:|---|---|
| `redis` | `^4.7.0` | Suscripción a eventos de stock publicados en Redis. | `services/realtime-gateway/package.json`, `services/realtime-gateway/server.js` |
| `ws` | `^8.18.0` | Exposición de websocket para sincronizar stock en tiempo real. | `services/realtime-gateway/package.json`, `services/realtime-gateway/server.js` |

### Assets Node de microservicios Laravel

Los servicios `audit-service`, `auth-service`, `cart-service`, `catalog-service`, `discount-service`, `notification-service`, `order-service`, `payment-service` y `shipping-service` declaran el mismo set de soporte para compilar assets Laravel:

| Paquete | Versión declarada | Uso principal |
|---|---:|---|
| `@tailwindcss/vite` | `^4.0.0` | Integración de Tailwind con Vite en assets internos de Laravel. |
| `axios` | `^1.11.0` | Cliente HTTP disponible en assets internos Laravel. |
| `concurrently` | `^9.0.1` | Ejecución simultánea de servidor, cola, logs y Vite en desarrollo. |
| `laravel-vite-plugin` | `^2.0.0` | Integración Laravel + Vite. |
| `tailwindcss` | `^4.0.0` | Utilidades CSS para assets internos Laravel. |
| `vite` | `^7.0.7` | Compilación de assets internos Laravel. |

## Fuentes usadas por el sitio

| Fuente | Origen | Uso principal | Archivo de referencia |
|---|---|---|---|
| `Nunito` pesos 400, 500, 600, 700 y 800 | Google Fonts | Tipografía principal del frontend público y cuenta de usuario. | `frontend/index.html`, `frontend/src/styles/variables.css` |
| `-apple-system`, `BlinkMacSystemFont`, `Segoe UI`, `Roboto`, `sans-serif` | Fuentes del sistema | Fallback del sistema para mantener legibilidad si Google Fonts no carga. | `frontend/src/styles/variables.css` |
| `Segoe UI`, `Tahoma`, `Geneva`, `Verdana`, `sans-serif` | Fuentes del sistema | Base tipográfica del panel administrativo. | `frontend/src/modules/admin/styles/admin.css` |
| `Segoe UI`, `Roboto`, `Helvetica Neue`, `Arial`, `sans-serif` | Fuentes del sistema | Estilos heredados del detalle de producto. | `frontend/public/legacy/producto/css/product-view.css` |
| Font Awesome | `@fortawesome/fontawesome-free` | Iconos de navegación, acciones, estados y botones. | `frontend/package.json`, `frontend/src/main.js`, componentes y vistas con clases `fas`, `far`, `fab` |

## Paleta de colores del sitio

### Paleta pública principal

| Uso | Variable o color | Valor |
|---|---|---:|
| Primario moderno | `--primary` | `#1a8fc4` |
| Primario hover | `--primary-hover` | `#167eb0` |
| Primario claro | `--primary-light` | `#e8f4fa` |
| Primario pálido | `--primary-pale` | `#f0f8fc` |
| Blanco | `--white` | `#ffffff` |
| Gris 50 | `--gray-50` | `#f9fafb` |
| Gris 100 | `--gray-100` | `#f3f4f6` |
| Gris 200 | `--gray-200` | `#e5e7eb` |
| Gris 300 | `--gray-300` | `#d1d5db` |
| Gris 400 | `--gray-400` | `#9ca3af` |
| Gris 500 | `--gray-500` | `#6b7280` |
| Gris 600 | `--gray-600` | `#4b5563` |
| Gris 700 | `--gray-700` | `#374151` |
| Gris 800 | `--gray-800` | `#1f2937` |
| Gris 900 | `--gray-900` | `#111827` |
| Éxito | `--success` | `#22c55e` |
| Error | `--error` | `#ef4444` |
| Advertencia | `--warning` | `#f59e0b` |
| Información | `--info` | `#3b82f6` |

### Paleta heredada usada por vistas migradas

| Uso | Variable o color | Valor |
|---|---|---:|
| Primario | `--primary-color` | `#0077b6` |
| Primario claro | `--primary-light` | `#90e0ef` |
| Primario oscuro | `--primary-dark` | `#6a96cf` |
| Secundario | `--secondary-color` | `#48cae4` |
| Acento | `--accent-color` | `#00b4d8` |
| Texto base | `--text-color` | `#333333` |
| Texto suave | `--text-light` | `#777777` |
| Texto fuerte | `--text-dark` | `#111111` |
| Fondo claro | `--bg-light` | `#ffffff` |
| Fondo suave | `--bg-dark` | `#f8f9fa` |
| Borde | `--border-color` | `#e0e0e0` |
| Éxito | `--success-color` | `#4bb543` |
| Error | `--error-color` | `#ff3333` |
| Advertencia | `--warning-color` | `#ffcc00` |

### Paleta administrativa

| Uso | Variable o color | Valor |
|---|---|---:|
| Primario admin | `--admin-primary` | `#0077b6` |
| Primario claro admin | `--admin-primary-light` | `#90e0ef` |
| Primario oscuro admin | `--admin-primary-dark` | `#0068a1` |
| Secundario admin | `--admin-secondary` | `#48cae4` |
| Acento admin | `--admin-accent` | `#00b4d8` |
| Texto admin | `--admin-text` | `#333333` |
| Texto suave admin | `--admin-text-light` | `#777777` |
| Texto fuerte admin | `--admin-text-dark` | `#111111` |
| Fondo admin | `--admin-bg` | `#ffffff` |
| Fondo suave admin | `--admin-bg-dark` | `#f8f9fa` |
| Fondo panel admin | `--admin-panel-bg` | `#eef2f7` |
| Borde admin | `--admin-border` | `#e0e0e0` |
| Borde claro admin | `--admin-border-light` | `#d9e8f4` |
| Éxito admin | `--admin-success` | `#4bb543` |
| Error admin | `--admin-error` | `#ff3333` |
| Advertencia admin | `--admin-warning` | `#ffcc00` |
| Información admin | `--admin-info` | `#17a2b8` |
| Estrellas/calificación | `--admin-star` | `#f2ab00` |

### Colores de interfaz compartida

| Uso | Variable o color | Valor |
|---|---|---:|
| Scrollbar pista sitio | `--site-scrollbar-track` | `#e7eef5` |
| Scrollbar sitio | `--site-scrollbar-thumb` | `#7fa7cb` |
| Scrollbar sitio hover | `--site-scrollbar-thumb-hover` | `#5f95c7` |
| Snackbar éxito | Clase `snackbar--success` | `#16a34a` |
| Snackbar error | Clase `snackbar--error` | `#dc2626` |
| Snackbar advertencia | Clase `snackbar--warning` | `#d97706` |
| Snackbar información | Clase `snackbar--info` | `#0284c7` |

## Uso funcional documentado

### Exportaciones admin reutilizables en PDF y Excel

- Fecha: 2026-05-24
- Tipo: librerías frontend npm.
- Paquetes/versiones: `exceljs@^4.4.0`, `jspdf@^4.2.1`, `jspdf-autotable@^5.0.8`.
- Motivo: generar archivos Excel y PDF administrativos con formato compartido, branding vigente, tablas declarativas e imágenes cuando la vista lo requiere.
- Comando usado originalmente: `npm install exceljs jspdf jspdf-autotable`.
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

### Gráficos administrativos

- Fecha: 2026-04-03
- Tipo: librería frontend npm.
- Paquete/version: `chart.js@^4.4.0`.
- Motivo: habilitar gráficos funcionales en dashboard e informes admin.
- Comando usado originalmente: `npm install chart.js@4.4.0`.
- Archivos donde se aplica:
  - `frontend/src/modules/admin/components/AdminChartPanel.vue`
  - `frontend/src/modules/admin/composables/useAdminReports.js`
  - `frontend/package.json`
  - `frontend/package-lock.json`

### Exportación backend de productos CSV y PDF

- Fecha: 2026-04-15
- Tipo: dependencias backend Composer.
- Paquetes/versiones: `league/csv@9.18.0`, `dompdf/dompdf@v3.1.0`.
- Motivo: generar CSV de productos compatible con Excel y PDF tabular desde `catalog-service`.
- Comando usado originalmente: `composer require league/csv:^9.18 dompdf/dompdf:^3.1 --no-interaction`.
- Archivos donde se aplica:
  - `services/catalog-service/composer.json`
  - `services/catalog-service/composer.lock`
  - `services/catalog-service/routes/api.php`
  - `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
  - `frontend/src/modules/admin/pages/AdminProductsPage.vue`
  - `frontend/src/services/http.js`

### Campañas de descuentos con PDF adjunto

- Fecha: 2026-04-17
- Tipo: dependencia backend Composer.
- Paquete/version: `dompdf/dompdf@v3.1.0`.
- Motivo: generar en memoria el PDF del código de descuento para adjuntarlo en campañas masivas y envíos a usuarios específicos.
- Comando usado originalmente: `composer require dompdf/dompdf:^3.1 --no-interaction --no-progress`.
- Archivos donde se aplica:
  - `services/discount-service/composer.json`
  - `services/discount-service/composer.lock`
  - `services/discount-service/app/Support/DiscountPdfAttachmentHelper.php`
  - `services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php`
  - `services/discount-service/routes/api.php`
  - `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`

### Facturación automática de órdenes

- Fecha: 2026-04-17
- Tipo: dependencia backend Composer.
- Paquete/version: `dompdf/dompdf@v3.1.5`.
- Motivo: generar facturas PDF, permitir descarga y reenviar factura al cliente.
- Comando usado originalmente: `composer require dompdf/dompdf:^3.1 --no-interaction --no-progress`.
- Archivos donde se aplica:
  - `services/order-service/composer.json`
  - `services/order-service/composer.lock`
  - `services/order-service/app/Services/OrderInvoiceService.php`
  - `services/order-service/app/Http/Controllers/OrderController.php`
  - `services/order-service/app/Http/Controllers/Admin/AdminInvoiceController.php`
  - `services/order-service/routes/api.php`
  - `frontend/src/services/invoiceApi.js`
  - `frontend/src/modules/admin/pages/AdminInvoicesPage.vue`
  - `frontend/src/modules/account/pages/OrderDetailPage.vue`

### Gateway websocket global para stock reservado e inventario

- Fecha: 2026-06-07
- Tipo: dependencias Node para microservicio auxiliar realtime.
- Paquetes/versiones: `redis@^4.7.0`, `ws@^8.18.0`.
- Motivo: exponer por websocket global los eventos de reservas, confirmaciones, ajustes y transferencias de stock publicados en Redis.
- Comando usado originalmente: `npm install redis@^4.7.0 ws@^8.18.0`.
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

## Fuentes revisadas

- `services/*/composer.json` y `services/*/composer.lock`: dependencias Composer directas e instaladas por microservicio.
- `package.json` y `package-lock.json`: dependencias Node declaradas en la raíz.
- `frontend/package.json` y `frontend/package-lock.json`: dependencias npm del frontend principal.
- `services/*/package.json`: dependencias Node de soporte para assets Laravel.
- `services/realtime-gateway/package.json`: dependencias del gateway websocket.
- `frontend/index.html`: carga de Google Fonts.
- `frontend/src/styles/variables.css`: fuente principal y paleta pública moderna.
- `frontend/src/styles/main.css`: estilos globales, scrollbar y estados base.
- `frontend/src/modules/admin/styles/admin.css`: fuente y paleta administrativa.
- `frontend/public/legacy/css/style.css` y `frontend/public/legacy/producto/css/product-view.css`: paleta y tipografía heredadas usadas por vistas migradas.
- `frontend/src/components/ui/UserSnackbarSystem.css` y `frontend/src/components/ui/UserAlertSystem.css`: colores de feedback visual.

## Plantilla para futuras entradas

- Fecha:
- Tipo: librería frontend, backend, Composer, Node, fuente o paleta.
- Paquete/version:
- Motivo:
- Comando usado:
- Archivos donde se aplica:
- Contexto funcional:
