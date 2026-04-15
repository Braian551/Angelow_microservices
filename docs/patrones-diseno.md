# Patrones de diseño aplicados

## 2026-04-14 - Refresco en caliente de ajustes e imágenes admin

- Patrón: Observer (Refactoring Guru)
- Aplicación: el guardado de Ajustes emite un evento global para sincronizar en caliente sidebar admin, shell público y favicon sin recargar la página completa.
- Ubicación: frontend/src/modules/admin/pages/AdminSettingsPage.vue, frontend/src/modules/admin/components/AdminSidebar.vue, frontend/src/App.vue, frontend/src/constants/siteSettingsEvents.js
- Problema resuelto: los cambios de configuración (logo/favicon) solo se reflejaban tras recarga manual del navegador.

- Patrón: Command (Refactoring Guru)
- Aplicación: el backend de configuración ejecuta comandos explícitos de reemplazo/eliminación de imagen por campo (`subir`, `reemplazar`, `eliminar`) y responde estado consolidado.
- Ubicación: services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php, frontend/src/modules/admin/pages/AdminSettingsPage.vue
- Problema resuelto: evitar acumulación de archivos huérfanos y permitir que “Quitar imagen” persista realmente en base de datos.

- Patrón: Chain of Responsibility (Refactoring Guru)
- Aplicación: resolución de URLs de media con múltiples candidatos de host/base antes de caer al fallback visual.
- Ubicación: frontend/src/utils/media.js
- Problema resuelto: reducir falsos “imagen rota” cuando la ruta existe pero cambia el host/base de uploads entre frontend y API.

## 2026-04-14 - Drag and Drop para orden de sliders

- Patrón: Command (Refactoring Guru)
- Aplicación: el reordenamiento del carrusel en admin se ejecuta como comando explícito de arrastre y persistencia, con operación única de actualización de orden.
- Ubicación: frontend/src/modules/admin/pages/AdminSlidersPage.vue
- Problema resuelto: mejorar la comodidad de ordenado de sliders evitando clicks repetidos en flechas para subir o bajar.

## 2026-04-14 - Ajustes generales proyectados al sitio

- Patrón: Facade (Refactoring Guru)
- Aplicación: la capa de shell aplica en un solo punto configuración global de marca (favicon, colores y nombre) para el sitio público.
- Ubicación: frontend/src/App.vue
- Problema resuelto: los cambios de ajustes generales no impactaban de forma inmediata y consistente elementos globales visibles del sitio.

- Patrón: Adapter (Refactoring Guru)
- Aplicación: adaptación de settings de marca para consumir logo alterno en encabezado público y menú lateral administrativo con fallback al logo principal.
- Ubicación: frontend/src/components/layout/SiteHeader.vue, frontend/src/modules/admin/components/AdminSidebar.vue, services/catalog-service/app/Support/SiteSettingsCatalog.php
- Problema resuelto: el logo secundario no tenía canal configurable y varias zonas seguían atadas a recursos estáticos.

## 2026-04-14 - Fallback legacy para envios admin

- Patrón: Chain of Responsibility (Refactoring Guru)
- Aplicación: los endpoints administrativos de métodos y reglas de envío consultan primero la base distribuida y, cuando no hay filas, continúan con lectura desde la fuente legacy sin romper el contrato del frontend.
- Ubicación: services/shipping-service/app/Http/Controllers/Admin/AdminShippingController.php
- Problema resuelto: las vistas de envíos en admin quedaban vacías durante la migración porque shipping-db no tenía datos, aunque sí existían registros en la base legacy.

# 2026-04-14 - Responsive fino para header y barra de resultados admin

- **Patrón:** Design System + Responsive Adaptation
- **Problema resuelto:** en móvil estrecho el header principal del dashboard admin dejaba la campana y la acción rápida en bloques visualmente desbalanceados, y la barra de resultados quedaba demasiado aireada para el ancho disponible.
- **Solución aplicada:** se refinó el breakpoint `max-width: 480px` para compactar el `AdminHeader` y agrupar `notification-btn` + `quick-action-btn` en una sola fila útil; además se densificó `AdminResultsBar` para reducir padding, iconografía y separación visual en móviles pequeños.
- **Archivos:** `frontend/src/modules/admin/styles/admin.css`

## 2026-04-14 - Responsive transversal para header y filtros admin

- Patrón: Design System / Template Method (Refactoring Guru)
- Aplicación: ajuste de breakpoints compartidos para header global del admin, encabezado de página y filtro reutilizable, de modo que las vistas administrativas hereden el mismo comportamiento en móvil sin overrides locales por pantalla.
- Ubicación: frontend/src/modules/admin/styles/admin.css, frontend/src/modules/admin/components/AdminHeader.vue, frontend/src/modules/admin/components/AdminPageHeader.vue, frontend/src/modules/admin/components/AdminFilterCard.vue
- Problema resuelto: evitar recortes horizontales, acciones comprimidas y controles de búsqueda/filtro mal apilados en móvil, como en la vista de tallas y el resto del dashboard admin.

- Patrón: Adapter visual (Refactoring Guru)
- Aplicación: variante de tabla ancha (`dashboard-table--sizes`) para conservar legibilidad en móvil mediante scroll horizontal controlado, evitando que las columnas de Tallas se aplasten.
- Ubicación: frontend/src/modules/admin/styles/admin.css, frontend/src/modules/admin/pages/AdminSizesPage.vue
- Problema resuelto: en pantallas pequeñas, la tabla de tallas comprimía celdas y reducía lectura; ahora mantiene ancho mínimo coherente y navegación horizontal táctil.

- Patrón: Drawer / Overlay Layout
- Aplicación: el aside del dashboard admin en móvil ahora se comporta como drawer superpuesto con overlay, bloqueo de scroll del fondo y cierre automático al navegar.
- Ubicación: frontend/src/modules/admin/layouts/AdminLayout.vue, frontend/src/modules/admin/components/AdminSidebar.vue, frontend/src/modules/admin/styles/admin.css
- Problema resuelto: el menú hamburguesa dejaba un espacio blanco lateral y mostraba el contenido como si el aside siguiera siendo una columna fija; ahora el panel móvil se monta encima del contenido sin romper el layout.

- Patrón: Workflow Rule / Operational Guardrail
- Aplicación: la skill operativa ahora exige que toda sección intervenida quede validada en los breakpoints afectados antes de cerrar la tarea, no solo el layout general.
- Ubicación: .agents/skills/skill/SKILL.md
- Problema resuelto: hacer explícita la obligación de revisar responsive por sección trabajada y evitar regresiones visuales parciales en tareas futuras.

## 2026-04-14 - Paginación reusable para vistas admin

- Patrón: Facade (Refactoring Guru)
- Aplicación: `useAdminPagination` expone una interfaz única de paginación local para listas administrativas filtradas, desacoplando a cada vista del cálculo de páginas, rangos visibles y recorte de datos.
- Ubicación: frontend/src/modules/admin/composables/useAdminPagination.js, frontend/src/modules/admin/pages/AdminProductsPage.vue, frontend/src/modules/admin/pages/AdminOrdersPage.vue, frontend/src/modules/admin/pages/AdminCategoriesPage.vue, frontend/src/modules/admin/pages/AdminCollectionsPage.vue, frontend/src/modules/admin/pages/AdminSizesPage.vue, frontend/src/modules/admin/pages/AdminSlidersPage.vue, frontend/src/modules/admin/pages/AdminAdministratorsPage.vue, frontend/src/modules/admin/pages/AdminPaymentsPage.vue, frontend/src/modules/admin/pages/AdminCustomersPage.vue, frontend/src/modules/admin/pages/AdminQuestionsPage.vue, frontend/src/modules/admin/pages/AdminReviewsPage.vue, frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue, frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue, frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue, frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue, frontend/src/modules/admin/pages/AdminShippingRulesPage.vue, frontend/src/modules/admin/pages/AdminInventoryPage.vue, frontend/src/modules/admin/pages/AdminReportsPage.vue
- Problema resuelto: evitar implementaciones aisladas o ausencia total de paginación en tablas y grids del panel, manteniendo un comportamiento homogéneo según filtros y volumen de registros.

- Patrón: Reuse Component / Composition (Refactoring Guru - composición sobre duplicación)
- Aplicación: `AdminPagination` centraliza la interfaz visual de navegación por páginas, selector de tamaño y resumen de resultados para todas las vistas admin.
- Ubicación: frontend/src/modules/admin/components/AdminPagination.vue, frontend/src/modules/admin/styles/admin.css
- Problema resuelto: unificar la experiencia visual y reducir duplicación de markup/estilos de paginación entre módulos operativos, catálogos, inventario e informes.

- Patrón: Reuse Component / Adapter visual
- Aplicación: `AdminTableImage` encapsula thumbnails de tabla con contenedor fijo, recorte consistente y fallback controlado para categorías, colecciones e inventario.
- Ubicación: frontend/src/modules/admin/components/AdminTableImage.vue, frontend/src/modules/admin/styles/admin.css, frontend/src/modules/admin/pages/AdminCategoriesPage.vue, frontend/src/modules/admin/pages/AdminCollectionsPage.vue, frontend/src/modules/admin/pages/AdminInventoryPage.vue
- Problema resuelto: evitar desbordes por imágenes de tamaño irregular dentro de tablas y mantener una jerarquía visual consistente en todas las listas administrativas.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: adopción del componente compartido `AdminFilterCard` como estructura única de búsqueda y filtros para las vistas admin que aún conservaban barras legacy.
- Ubicación: frontend/src/modules/admin/components/AdminFilterCard.vue, frontend/src/modules/admin/pages/AdminCategoriesPage.vue, frontend/src/modules/admin/pages/AdminCollectionsPage.vue, frontend/src/modules/admin/pages/AdminSizesPage.vue, frontend/src/modules/admin/pages/AdminInventoryPage.vue, frontend/src/modules/admin/pages/AdminPaymentsPage.vue
- Problema resuelto: eliminar divergencias visuales y espaciamientos inconsistentes entre vistas con búsqueda/filtros, asegurando la misma secuencia de interacción en todo el panel.

## 2026-04-14 - Redireccion inicial por rol y guard de rutas admin/cuenta

- Patrón: Strategy (Refactoring Guru)
- Aplicación: estrategia de resolución de rol de sesión (role, rol, user_role, tipo_usuario y arreglo roles) para decidir la navegación inicial y los accesos por dominio de ruta.
- Ubicación: frontend/src/router/index.js
- Problema resuelto: la raíz pública no enviaba automáticamente al panel cuando la sesión era admin y se permitían cruces inconsistentes entre rutas de cliente y administración.

- Patrón: Guard Clause (Refactoring Guru - simplificación de flujo condicional)
- Aplicación: salidas tempranas en el guard global del router para redirección desde `/`, protección de `/admin` y bloqueo de `/mi-cuenta` para sesiones admin.
- Ubicación: frontend/src/router/index.js
- Problema resuelto: eliminar ramas ambiguas en autorización y asegurar comportamiento consistente de rutas según rol autenticado.

## 2026-04-12 - Unificación visual de filtros admin + protocolo anti-caché frontend

- Patrón: Template Method (Refactoring Guru)
- Aplicación: estructura compartida para filtros admin con barra principal, búsqueda, contenido contextual y filtros avanzados reusables.
- Ubicación: frontend/src/modules/admin/components/AdminFilterCard.vue, frontend/src/modules/admin/styles/admin.css, frontend/src/modules/admin/pages/AdminReportsPage.vue
- Problema resuelto: eliminar implementaciones duplicadas de filtros en vistas de administración y asegurar una misma secuencia visual/interactiva en órdenes, informes y demás módulos.

- Patrón: Observer (Refactoring Guru)
- Aplicación: transiciones reactivas de aparición/colapso del contenido del filtro según el estado local `expanded` del componente.
- Ubicación: frontend/src/modules/admin/components/AdminFilterCard.vue, frontend/src/modules/admin/styles/admin.css
- Problema resuelto: evitar cambios bruscos de layout al mostrar u ocultar bloques del filtro y dar continuidad visual al componente compartido.

- Patrón: Workflow Rule / Operational Guardrail
- Aplicación: protocolo explícito de invalidación de caché Vite/HMR y hard refresh cuando un cambio frontend no se refleja en navegador.
- Ubicación: .agents/skills/skill/SKILL.md
- Problema resuelto: evitar falsos negativos de despliegue local donde el código sí cambió pero la UI sigue mostrando una versión cacheada del frontend.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: encabezado compartido de administración con la misma estructura visual tipo card usada por Angelow en las vistas legacy.
- Ubicación: frontend/src/modules/admin/components/AdminPageHeader.vue, frontend/src/modules/admin/styles/admin.css
- Problema resuelto: evitar divergencia visual entre el encabezado del dashboard SPA y el encabezado operativo de Angelow en todas las vistas de administración.

- Patrón: Chain of Responsibility (Refactoring Guru)
- Aplicación: resolución de órdenes admin priorizando base distribuida y complementando con legacy durante migración, con soporte explícito por fuente (`microservice` o `legacy`) en detalle y acciones.
- Ubicación: services/order-service/app/Http/Controllers/Admin/AdminOrderController.php, services/order-service/app/Http/Controllers/OrderController.php, frontend/src/modules/admin/pages/AdminOrdersPage.vue, frontend/src/modules/admin/pages/AdminOrderDetailPage.vue
- Problema resuelto: mostrar todas las órdenes disponibles durante una migración parcial sin perder trazabilidad de la fuente real de cada pedido ni romper acciones administrativas por colisión de IDs.

- Patrón: Adapter (Refactoring Guru)
- Aplicación: enriquecimiento de órdenes admin con identidad del cliente (`name`, `email`, `phone`) a partir de `user_id` cuando la tabla `orders` no guarda esos campos de forma denormalizada.
- Ubicación: services/order-service/app/Http/Controllers/Admin/AdminOrderController.php, services/order-service/app/Http/Controllers/OrderController.php
- Problema resuelto: las órdenes mostraban `Cliente` y `Sin email` aunque existía información real del usuario en las fuentes disponibles durante la migración.

- Patrón: Design System / Template Method (Refactoring Guru)
- Aplicación: rebalanceo del lenguaje visual compartido del admin para filtros, tablas, barra de resultados, botones de acción y acciones rápidas con colores sólidos suaves y glass sutil aplicados desde estilos globales.
- Ubicación: frontend/src/modules/admin/styles/admin.css, frontend/src/modules/admin/components/AdminHeader.vue, frontend/src/modules/admin/components/AdminFilterCard.vue, frontend/src/modules/admin/components/AdminResultsBar.vue
- Problema resuelto: había desproporción entre controles del filtro, tablas con acciones muy duras/pequeñas y botones rápidos demasiado planos, generando inconsistencia visual entre vistas administrativas.

- Patrón: State (Refactoring Guru)
- Aplicación: el filtro admin compartido ahora separa el estado base de búsqueda del estado expandido de filtros avanzados, dejando visible siempre el buscador y ocultando por defecto solo los bloques adicionales.
- Ubicación: frontend/src/modules/admin/components/AdminFilterCard.vue, frontend/src/modules/admin/styles/admin.css
- Problema resuelto: al colapsar el componente se ocultaba también el buscador, rompiendo la paridad visual y funcional con Angelow y dificultando el acceso rápido a la búsqueda.

- Patrón: Design System / Adapter visual
- Aplicación: los checkboxes de selección dentro de tablas admin se normalizan con un estilo único inspirado en Angelow, sin depender del checkbox nativo del navegador ni de overrides locales por vista.
- Ubicación: frontend/src/modules/admin/styles/admin.css, frontend/src/modules/admin/pages/AdminOrdersPage.vue
- Problema resuelto: la selección masiva en tablas se veía pequeña e inconsistente respecto a Angelow, especialmente en órdenes.

- Patrón: Adapter (Refactoring Guru)
- Aplicación: capa de presentación para traducir estados, métodos de pago y transiciones crudas provenientes de base de datos al español antes de pintarlas en la IU de órdenes admin.
- Ubicación: frontend/src/modules/admin/utils/orderPresentation.js, frontend/src/modules/admin/pages/AdminOrdersPage.vue, frontend/src/modules/admin/pages/AdminOrderDetailPage.vue
- Problema resuelto: el usuario veía valores técnicos en inglés como `pending`, `paid` o `transfer` dentro de tablas, historial y detalle de órdenes.

- Patrón: Facade (Refactoring Guru)
- Aplicación: el detalle de órdenes consulta el dominio de pagos mediante un acceso encapsulado para recuperar comprobante y metadatos del pago asociado por `order_id`.
- Ubicación: services/payment-service/app/Http/Controllers/Admin/AdminPaymentController.php, frontend/src/modules/admin/pages/AdminOrderDetailPage.vue
- Problema resuelto: el detalle admin de microservicios no mostraba el comprobante de pago ni la referencia asociada, a diferencia de Angelow.

- Patrón: Adapter (Refactoring Guru)
- Aplicación: adaptación de la navegación pública de detalle de órdenes para ocultar términos internos de migración en la URL, manteniendo internamente la resolución de fuente real (`legacy` o distribuida).
- Ubicación: frontend/src/modules/admin/pages/AdminOrdersPage.vue, frontend/src/modules/admin/pages/AdminOrderDetailPage.vue, .agents/skills/skill/SKILL.md
- Problema resuelto: la URL del admin exponía `microservice` al usuario final, rompiendo la regla de no mostrar detalles de implementación en la interfaz.

- Patrón: Facade (Refactoring Guru)
- Aplicación: flujo unificado de carga de comprobantes entre checkout, servicio de pagos y detalle admin para persistir la ruta física real del archivo y resolver su acceso público desde `/uploads`.
- Ubicación: frontend/src/modules/checkout/pages/PaymentPage.vue, frontend/src/services/paymentApi.js, frontend/src/utils/media.js, services/payment-service/app/Http/Controllers/PaymentController.php, services/payment-service/app/Http/Controllers/Admin/AdminPaymentController.php, frontend/src/modules/admin/pages/AdminOrderDetailPage.vue
- Problema resuelto: se estaba guardando solo el nombre original del comprobante en la BD, lo que impedía encontrar el archivo real en uploads y terminaba en imágenes rotas en el detalle administrativo.

- Patrón: Chain of Responsibility (Refactoring Guru)
- Aplicación: resolución escalonada de comprobantes admin probando ruta persistida, variantes relativas y búsqueda por basename antes de declarar el adjunto como no disponible.
- Ubicación: services/payment-service/app/Http/Controllers/Admin/AdminPaymentController.php, frontend/src/modules/admin/pages/AdminOrderDetailPage.vue, .agents/skills/skill/SKILL.md
- Problema resuelto: algunos registros heredados podían traer nombres parciales o rutas incompletas, generando falsos negativos al validar el comprobante y mensajes demasiado técnicos en la IU.

- Patrón: Template Method (Refactoring Guru)
- Aplicación: reconstrucción del historial de cambios del detalle de órdenes siguiendo la misma secuencia visual de Angelow legacy: sección independiente de ancho completo, timeline vertical, tarjeta de evento, actor/rol, valores anterior y nuevo, y expansión progresiva del listado.
- Ubicación: frontend/src/modules/admin/pages/AdminOrderDetailPage.vue, angelow/admin/order/detail.php
- Problema resuelto: el historial en SPA se veía como una tabla genérica y no mantenía la jerarquía, lectura ni la narrativa visual del módulo legacy de órdenes.

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
