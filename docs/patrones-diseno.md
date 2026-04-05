# Patrones de diseño aplicados

## 2026-04-02 - Favoritos (CORS) + Carrito (paridad legacy)

- Patrón: Strategy (Refactoring Guru)
- Aplicación: búsqueda de proveedores en el selector de ubicación del frontend.
- Ubicación: frontend/src/modules/account/components/AddressLocationPickerModal.vue
- Problema resuelto: evitar caída total cuando falla un proveedor de geocodificación; se prueban estrategias en secuencia.

- Patrón: Facade (Refactoring Guru)
- Aplicación: cliente HTTP centralizado para microservicios.
- Ubicación: frontend/src/services/http.js
- Problema resuelto: unificar autenticación, normalización UTF-8 y manejo homogéneo de respuestas para todos los dominios.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: flujo de checkout por pasos (carrito, envío, pago, confirmación).
- Ubicación: frontend/src/modules/cart/pages/CartPage.vue, frontend/src/modules/checkout/pages/ShippingPage.vue, frontend/src/modules/checkout/pages/PaymentPage.vue, frontend/src/modules/checkout/pages/ConfirmationPage.vue
- Problema resuelto: mantener secuencia funcional consistente y extensible, con variación controlada por etapa.

## 2026-04-03 - Resumen/Favoritos + Ajuste visual configuración

- Patrón: Strategy (Refactoring Guru)
- Aplicación: selección de fuente de cards en resumen de cuenta.
- Ubicación: frontend/src/modules/account/pages/DashboardPage.vue
- Problema resuelto: usar primero productos favoritos reales del usuario y, solo si no existen, usar recomendaciones generales.

- Patrón: Reuse Component / Composition (Refactoring Guru - composición sobre duplicación)
- Aplicación: renderizado de productos en resumen con el mismo ProductCard del catálogo.
- Ubicación: frontend/src/modules/account/pages/DashboardPage.vue, frontend/src/modules/catalog/components/ProductCard.vue
- Problema resuelto: eliminar divergencia visual/funcional entre cards de catálogo y cards del módulo de cuenta.

## 2026-04-03 - Reintegración de recomendaciones + paridad visual pedidos/carrito

- Patrón: Strategy (Refactoring Guru)
- Aplicación: selección de bloques de productos para Resumen (favoritos y recomendaciones coexistiendo).
- Ubicación: frontend/src/modules/account/pages/DashboardPage.vue
- Problema resuelto: conservar lógica de recomendaciones sin perder prioridad de mostrar favoritos reales del usuario.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: visualización por etapas del estado de pedidos (pendiente, proceso, envío, entrega).
- Ubicación: frontend/src/modules/account/pages/OrdersPage.vue
- Problema resuelto: homologar representación de avance del pedido con flujo visual de Angelow legacy manteniendo una secuencia común.

- Patrón: Facade (Refactoring Guru)
- Aplicación: acceso centralizado a endpoints de dominio para resumen y pedidos.
- Ubicación: frontend/src/services/orderApi.js, frontend/src/services/wishlistApi.js, frontend/src/services/catalogApi.js
- Problema resuelto: mantener separación limpia entre UI y acceso a datos, evitando lógica de red dispersa en componentes.

## 2026-04-03 - Buscador global + detalle de pedido funcional

- Patrón: Observer (Refactoring Guru)
- Aplicación: respuesta reactiva del buscador a cambios de entrada y estado de ruta.
- Ubicación: frontend/src/components/layout/SiteHeader.vue
- Problema resuelto: habilitar búsqueda funcional con sugerencias en vivo y navegación consistente a catálogo/producto.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: estructura estándar de detalle de pedido (cabecera, productos, progreso, historial).
- Ubicación: frontend/src/modules/account/pages/OrderDetailPage.vue
- Problema resuelto: replicar el flujo visual/funcional de detalle de pedido de Angelow legacy con datos reales del microservicio.

- Patrón: Strategy (Refactoring Guru)
- Aplicación: cálculo de etapa activa según estado de pedido.
- Ubicación: frontend/src/modules/account/pages/OrdersPage.vue, frontend/src/modules/account/pages/OrderDetailPage.vue
- Problema resuelto: mapear distintos estados backend a una línea de progreso única y mantenible.

- Patrón: Chain of Responsibility (Refactoring Guru)
- Aplicación: resolución progresiva de identidad user_id/user_email y fallback legacy en controladores de servicios.
- Ubicación: services/catalog-service, services/order-service, services/notification-service
- Problema resuelto: mantener compatibilidad durante migración sin romper el contrato de datos.

## 2026-04-03 - Paridad carrito/checkout + feedback visual de compra

- Patrón: Observer (Refactoring Guru)
- Aplicación: feedback global de eventos de compra/favoritos mediante snackbar centralizado.
- Ubicación: frontend/src/modules/catalog/pages/ProductDetailPage.vue, frontend/src/composables/useSnackbarSystem.js, frontend/src/components/ui/UserSnackbarSystem.vue
- Problema resuelto: mostrar confirmación consistente al agregar al carrito y evitar mensajes dispersos por vista.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: secuencia visual unificada de 4 pasos (Carrito, Envío, Pago, Confirmación) en checkout.
- Ubicación: frontend/src/modules/cart/pages/CartPage.vue, frontend/src/modules/checkout/pages/ShippingPage.vue, frontend/src/modules/checkout/pages/PaymentPage.vue, frontend/src/modules/checkout/pages/ConfirmationPage.vue
- Problema resuelto: conservar la misma narrativa de flujo del legacy y eliminar divergencias entre pantallas.

- Patrón: Facade (Refactoring Guru)
- Aplicación: operaciones de carrito encapsuladas por servicios y consumidas por la vista con controles reutilizables de cantidad.
- Ubicación: frontend/src/modules/cart/pages/CartPage.vue, frontend/src/services/cartApi.js
- Problema resuelto: mantener UI de carrito alineada al legacy sin acoplar la vista a detalles de red ni duplicar lógica de actualización/eliminación.

## 2026-04-03 - Graficos dashboard e informes admin (paridad legacy)

- Patrón: Facade (Refactoring Guru)
- Aplicación: consumo unificado de reportes desde microservicios para construir dashboard e informes.
- Ubicación: frontend/src/services/http.js, frontend/src/modules/admin/pages/AdminDashboardPage.vue, frontend/src/modules/admin/pages/AdminReportsPage.vue
- Problema resuelto: evitar llamadas de red dispersas y mantener contrato homogéneo de datos para gráficos/tablas.

- Patrón: Observer (Refactoring Guru)
- Aplicación: reacción automática de gráficos a cambios de rango, pestaña y filtros.
- Ubicación: frontend/src/modules/admin/pages/AdminDashboardPage.vue, frontend/src/modules/admin/pages/AdminReportsPage.vue
- Problema resuelto: refrescar visualizaciones en tiempo real sin recargar la SPA y sin duplicar lógica de render.

- Patrón: Strategy (Refactoring Guru)
- Aplicación: estrategia de agrupación temporal (día/semana/mes/año) para reporte de ventas.
- Ubicación: frontend/src/modules/admin/pages/AdminReportsPage.vue
- Problema resuelto: permitir múltiples vistas analíticas sobre la misma fuente de datos sin cambiar endpoint backend.

## 2026-04-03 - Alineación de endpoints admin con BD distribuida

- Patrón: Adapter (Refactoring Guru)
- Aplicación: adaptación de columnas heterogéneas en controladores admin (name/nombre, stock/quantity, size_label/name, etc.).
- Ubicación: services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php, services/auth-service/app/Http/Controllers/Api/Admin/AdminUserController.php
- Problema resuelto: evitar respuestas vacías o errores por diferencias de esquema entre bases durante migración.

- Patrón: Facade (Refactoring Guru)
- Aplicación: integración uniforme de endpoints admin en vistas SPA de catálogo (tallas, inventario, reseñas, preguntas, categorías, colecciones y formulario de producto).
- Ubicación: frontend/src/modules/admin/pages/AdminSizesPage.vue, frontend/src/modules/admin/pages/AdminInventoryPage.vue, frontend/src/modules/admin/pages/AdminReviewsPage.vue, frontend/src/modules/admin/pages/AdminQuestionsPage.vue, frontend/src/modules/admin/pages/AdminCategoriesPage.vue, frontend/src/modules/admin/pages/AdminCollectionsPage.vue, frontend/src/modules/admin/pages/AdminProductFormPage.vue
- Problema resuelto: centralizar consumo coherente de APIs admin y eliminar placeholders que ocultaban datos reales existentes en BD.

## 2026-04-03 - Paridad visual productos admin + datos reales dashboard

- Patrón: Adapter (Refactoring Guru)
- Aplicación: adaptación de estructura de imagen y stock en respuesta de catálogo admin para soportar columnas/tablas mixtas durante migración (`primary_image`, `product_image`, `total_stock`).
- Ubicación: services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
- Problema resuelto: productos admin sin imagen/stock por diferencias de esquema entre `products` y `product_images`.

- Patrón: Facade (Refactoring Guru)
- Aplicación: endpoint admin único de órdenes recientes para consumo del dashboard SPA (`/api/admin/orders`) sin depender del endpoint de órdenes por usuario.
- Ubicación: services/order-service/app/Http/Controllers/Admin/AdminOrderController.php, services/order-service/routes/api.php
- Problema resuelto: tarjetas y métricas del dashboard quedaban vacías al consultar endpoints no globales para administración.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: flujo visual de catálogo admin en Vue replicando legacy (filtros, resultados, grid de tarjetas, quick-view modal, zoom modal) con el mismo orden de interacción.
- Ubicación: frontend/src/modules/admin/pages/AdminProductsPage.vue
- Problema resuelto: divergencia funcional y visual entre `products.php` legacy y la pantalla de productos en microservicios.

## 2026-04-03 - Paridad del carrito microservicios con Angelow

- Patrón: Adapter (Refactoring Guru)
- Aplicación: adaptación del contrato interno de variante para exponer y priorizar la imagen primaria de color en el carrito distribuido (`variant_image` -> `product_image`).
- Ubicación: services/catalog-service/app/Http/Controllers/InternalCatalogController.php, services/cart-service/app/Services/CartService.php
- Problema resuelto: el carrito SPA mostraba la imagen base del producto en vez de la foto correcta de la variante seleccionada.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: reconstrucción de la vista SPA del carrito siguiendo la misma secuencia visual de legacy (encabezado, pasos, grid de items, hover de tarjeta, chips de variante y resumen sticky).
- Ubicación: frontend/src/modules/cart/pages/CartPage.vue
- Problema resuelto: divergencia de diseño y microinteracciones entre `angelow/tienda/pagos/cart.php` y el carrito de microservicios.

## 2026-04-03 - Corrección de aside admin (estado activo + avatar fallback)

- Patrón: Strategy (Refactoring Guru)
- Aplicación: estrategia explícita para determinar cuándo Dashboard está activo (`isDashboardActive`) sin colisión con rutas hijas.
- Ubicación: frontend/src/modules/admin/components/AdminSidebar.vue
- Problema resuelto: el menú lateral mantenía Dashboard seleccionado al navegar a otras vistas de admin.

- Patrón: Facade (Refactoring Guru)
- Aplicación: reutilización del util central de media (`resolveMediaUrl` + `handleMediaError`) para avatar de administrador.
- Ubicación: frontend/src/modules/admin/components/AdminSidebar.vue, frontend/src/utils/media.js
- Problema resuelto: cuando faltaba imagen de perfil no siempre se resolvía correctamente el fallback; ahora usa el default central en `assets/foundnotimages/default-avatar.png`.

## 2026-04-03 - Paridad fina de Productos admin (paginación + modales + filtros)

- Patrón: Template Method (Refactoring Guru)
- Aplicación: secuencia de interacción del módulo de productos replicada como en legacy (filtros -> resultados -> grid -> modal rápido -> zoom -> paginación).
- Ubicación: frontend/src/modules/admin/pages/AdminProductsPage.vue
- Problema resuelto: diferencia de comportamiento/flujo visual frente a `angelow/admin/products.php` y `angelow/js/admin/products/productsManager.js`.

- Patrón: Strategy (Refactoring Guru)
- Aplicación: estrategias de ordenamiento y recorte de datos para paginación local sin cambiar contratos de endpoint.
- Ubicación: frontend/src/modules/admin/pages/AdminProductsPage.vue
- Problema resuelto: mantener la UI idéntica a legacy (navegación por páginas y orden dinámico) con datos provenientes de microservicios.

## 2026-04-03 - Sugerencias de búsqueda del header con paridad Angelow

- Patrón: Adapter (Refactoring Guru)
- Aplicación: adaptación del endpoint SPA para consumir primero el procedimiento almacenado legacy `SearchProductsAndTerms` y exponer una respuesta homogénea (`suggestions` + `terms`) al frontend.
- Ubicación: services/catalog-service/app/Http/Controllers/SearchController.php
- Problema resuelto: en microservicios no aparecían o llegaban incompletas las sugerencias del header respecto a Angelow legacy.

- Patrón: Facade (Refactoring Guru)
- Aplicación: el header mantiene una interfaz única hacia catálogo a través de `getSearchSuggestions`, sin conocer si la fuente real es procedimiento almacenado o fallback por consultas.
- Ubicación: frontend/src/services/catalogApi.js, frontend/src/components/layout/SiteHeader.vue
- Problema resuelto: conservar el mismo comportamiento visual/funcional del autocompletado sin acoplar la UI a la fuente de datos.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: el header SPA replica la misma secuencia estructural del legacy para autocompletado (input -> debounce -> dropdown inline -> navegación), manteniendo el panel dentro de la barra de búsqueda.
- Ubicación: frontend/src/components/layout/SiteHeader.vue, frontend/src/components/layout/Header.css
- Problema resuelto: el dropdown del buscador no persistía visualmente en checkout/confirmación; al volver al mismo patrón estructural del legacy se elimina la interferencia del overlay fijo y se asegura paridad de comportamiento.

- Patrón: Observer (Refactoring Guru)
- Aplicación: la apertura y carga del autocompletado del header ahora observa directamente `searchValue` mediante `watch`, en lugar de depender exclusivamente del evento DOM `input`.
- Ubicación: frontend/src/components/layout/SiteHeader.vue
- Problema resuelto: en checkout/confirmación el usuario escribía en el buscador pero el dropdown no siempre se montaba; al reaccionar al estado del modelo se garantiza consistencia aunque el navegador o la recomposición del input no disparen el handler esperado.

- Patrón: State (Refactoring Guru)
- Aplicación: el dropdown del header permanece visible mientras el término siga siendo válido, y solo cambia a estado cerrado al limpiar el input, presionar `Esc` o navegar por una selección.
- Ubicación: frontend/src/components/layout/SiteHeader.vue
- Problema resuelto: el panel de sugerencias se destruía prematuramente al perder foco o al inspeccionar la página, lo que impedía validar y usar el autocompletado en checkout/confirmación.

- Patrón: Adapter (Refactoring Guru)
- Aplicación: el buscador del header usa un contrato visual propio con clases exclusivas (`header-search-*`) y un selector acotado al botón submit, en vez de reutilizar clases globales compartidas por otros módulos.
- Ubicación: frontend/src/components/layout/SiteHeader.vue, frontend/src/components/layout/Header.css
- Problema resuelto: reglas globales como `.search-results`, `.search-loading` y `.search-bar button` interferían con el dropdown del header y deformaban/ocultaban las sugerencias en checkout/confirmación.

- Patrón: Observer (Refactoring Guru)
- Aplicación: el header observa y sincroniza el historial de búsqueda del usuario para decidir dinámicamente si cada término sugerido usa icono de reloj o de búsqueda.
- Ubicación: frontend/src/components/layout/SiteHeader.vue, frontend/src/services/catalogApi.js
- Problema resuelto: en microservicios no existía la señal visual de historial que sí tiene Angelow legacy para búsquedas ya realizadas.

- Patrón: Repository / Facade (Refactoring Guru)
- Aplicación: el microservicio de catálogo expone endpoints dedicados para leer y guardar historial (`/search/history`) y encapsula la persistencia de `search_history` + `popular_searches`.
- Ubicación: services/catalog-service/app/Http/Controllers/SearchController.php, services/catalog-service/app/Models/SearchHistory.php, services/catalog-service/app/Models/PopularSearch.php, services/catalog-service/routes/api.php
- Problema resuelto: replicar la lógica legacy de historial real del buscador sin dispersar consultas SQL y guardado en el frontend.

- Patrón: Strategy (Refactoring Guru)
- Aplicación: el header público define reglas adaptativas por breakpoint para redistribuir logo, buscador, iconos, navegación y dropdown de sugerencias sin cambiar la lógica del componente.
- Ubicación: frontend/src/components/layout/Header.css
- Problema resuelto: evitar solapes y roturas visuales del buscador en microservicios, especialmente en anchos intermedios y móviles donde el dropdown se deformaba respecto a Angelow legacy.

- Patrón: Composite Layout (composición de UI)
- Aplicación: el dropdown del buscador se divide en bloques explícitos de sugerencia destacada y lista de términos, cada uno con layout propio basado en grid para estabilizar imagen, icono y texto.
- Ubicación: frontend/src/components/layout/SiteHeader.vue, frontend/src/components/layout/Header.css
- Problema resuelto: el contenido del dropdown podía solaparse en móvil al mezclar producto destacado e historial en una misma superficie sin una composición estructural rígida.
