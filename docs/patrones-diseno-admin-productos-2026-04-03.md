# Patrón aplicado: admin productos SPA alineado con Angelow legacy

Fecha: 2026-04-03

Objetivo:
- Dejar `frontend/src/modules/admin/pages/AdminProductsPage.vue` visual y funcionalmente alineado con `angelow/admin/products.php`.
- Mantener el aislamiento de estilos dentro del módulo SPA, sin tocar CSS global ni contaminar otras pantallas.

Referencias legacy usadas:
- `angelow/admin/products.php`
- `angelow/js/admin/products/productsManager.js`
- `angelow/css/admin/products-grid.css`
- `angelow/css/admin/style-admin.css`

Cambios aplicados en SPA:
- Cabecera y bloque de filtros llevados al lenguaje visual de Angelow admin: iconografía, radios, sombras, jerarquía tipográfica y CTA de búsqueda.
- Grilla de “Todos los productos” ajustada al patrón legacy: tarjetas con hover lift, overlay oscuro, botón de vista rápida centrado, chips de categoría/variantes y footer de acciones.
- Modal “Detalles del Producto” compactado al layout legacy: galería izquierda, meta-info a la derecha, pills de color, botón de zoom, resumen de precios y variantes con tabla condicional.
- Lógica del quick view reforzada:
  - fallback a `data.variants` además de `data.size_variants`
  - fallback de imagen principal si no llegan imágenes de galería
  - contador de variantes más estable
  - navegación de miniaturas solo cuando realmente hace falta
- Limpieza de copy en UTF-8 para evitar texto roto en la vista.

Reglas de aislamiento respetadas:
- No se importó CSS legacy global en la SPA.
- Todo quedó dentro de estilos `scoped` del archivo `frontend/src/modules/admin/pages/AdminProductsPage.vue`.
- No se tocaron estilos compartidos de cards públicas ni del storefront.

Extension 2026-04-05: formulario `Nuevo producto` con paridad funcional parcial frente a `subproducto.php`

Objetivo:
- Llevar `frontend/src/modules/admin/pages/AdminProductFormPage.vue` al flujo rico de Angelow legacy sin romper la arquitectura SPA ni acoplarse al PHP antiguo.
- Persistir variantes, tallas, precios e imagenes mediante `catalog-service` y las tablas nativas del microservicio.

Patrones aplicados:
- `Composicion por tabs`: la pantalla separa `Informacion general` y `Variantes y precios` para reducir carga cognitiva y mantener el mismo recorrido mental del admin legacy.
- `Modal reutilizable`: se creo `frontend/src/modules/admin/components/AdminModal.vue` como contenedor comun para configuraciones de alto detalle. El formulario lo usa para la matriz de tallas, stock, SKU y codigo de barras.
- `Agregado raiz con reemplazo consistente`: `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php` trata el producto como agregado principal y sincroniza en bloque `product_color_variants`, `product_size_variants`, `product_images` y `variant_images` dentro de transacciones.
- `Compatibilidad de esquema`: el backend detecta columnas `name/nombre`, `price/precio`, `is_active/activo` para convivir con diferencias de legado o migracion.
- `Carga mixta JSON/multipart`: el admin envia JSON puro cuando no hay archivos y `FormData` cuando sube imagen principal o imagen por variante, evitando duplicar endpoints.

Decisiones de diseno relevantes:
- Se mantiene `precio base` en el producto como referencia transversal y se replica hacia tallas solo cuando el usuario no define uno distinto.
- La variante principal se fuerza a una sola seleccion mediante radio, con fallback automatico a la primera variante si ninguna viene marcada.
- Las imagenes nuevas se guardan en `uploads/productos` compartido por Docker para que frontend y microservicios resuelvan la misma ruta publica `/uploads/...`.

Limites actuales del patron:
- El flujo soporta una imagen principal por producto y una imagen principal por variante; no implementa aun galeria multiple por variante.
- La experiencia replica la logica central de Angelow legacy, pero no copia su HTML ni su CSS: conserva el lenguaje visual del admin SPA actual.

Extension 2026-04-05: vista `Todos los productos` refactorizada con componentes reutilizables

Objetivo:
- Mantener `frontend/src/modules/admin/pages/AdminProductsPage.vue` fiel al flujo legacy de `angelow/admin/products.php`, pero reduciendo acoplamiento y markup monolitico en la SPA.

Patrones aplicados:
- `Facade visual reutilizable`:
  - Archivos: `frontend/src/modules/admin/pages/AdminProductsPage.vue`, `frontend/src/modules/admin/components/AdminPageHeader.vue`, `frontend/src/modules/admin/components/AdminCard.vue`, `frontend/src/modules/admin/components/AdminModal.vue`, `frontend/src/modules/admin/components/AdminEmptyState.vue`.
  - Problema que resuelve: evita rehacer manualmente encabezado, contenedores, modales y estados vacios para la pantalla de productos.
- `Component`:
  - Archivo: `frontend/src/modules/admin/components/AdminProductCard.vue`.
  - Problema que resuelve: encapsula la tarjeta de producto con checkbox, badge de estado, imagen, meta y acciones, evitando repetir estructura compleja dentro del listado.
- `Adapter`:
  - Archivo: `frontend/src/modules/admin/pages/AdminProductsPage.vue`.
  - Problema que resuelve: normaliza productos, variantes e imagenes provenientes del `catalog-service` para mantener compatibilidad con columnas legacy y actuales.

Cambios relevantes:
- El breadcrumb se corrigio para no duplicar `Dashboard` en la cabecera SPA.
- La vista rapida y el zoom ahora usan `AdminModal`, alineando el manejo de modales con el resto del admin.
- El boton de busqueda y el bloque de filtros se ajustaron al gesto visual del legacy mostrado en Angelow.
- El listado sigue usando alertas y snackbars reutilizables para activar/desactivar y exportar.

Extension 2026-04-05: modulos `Ordenes` y `Clientes` con paridad operativa legacy

Objetivo:
- Llevar `frontend/src/modules/admin/pages/AdminOrdersPage.vue`, `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue` y `frontend/src/modules/admin/pages/AdminCustomersPage.vue` al mismo flujo operativo de Angelow legacy, manteniendo la separacion por dominios entre `order-service` y `auth-service`.

Patrones aplicados:
- `Facade visual reutilizable`:
  - Archivos: `frontend/src/modules/admin/pages/AdminOrdersPage.vue`, `frontend/src/modules/admin/pages/AdminCustomersPage.vue`, `frontend/src/modules/admin/components/AdminPageHeader.vue`, `frontend/src/modules/admin/components/AdminCard.vue`, `frontend/src/modules/admin/components/AdminModal.vue`, `frontend/src/modules/admin/components/AdminStatsGrid.vue`, `frontend/src/modules/admin/components/AdminTableShimmer.vue`, `frontend/src/modules/admin/components/AdminEmptyState.vue`.
  - Problema que resuelve: unifica cabeceras, bloques de filtros, modales, estadisticas y estados vacios para que ordenes y clientes conserven la misma experiencia del admin legacy sin duplicar infraestructura visual.
- `Adapter`:
  - Archivos: `frontend/src/modules/admin/pages/AdminOrdersPage.vue`, `frontend/src/modules/admin/pages/AdminCustomersPage.vue`.
  - Problema que resuelve: normaliza respuestas heterogeneas de `order-service` y `auth-service` en estructuras consistentes (`status/order_status`, `user_email/customer_email`, identidad por `id` o `email`) para sostener compatibilidad durante la migracion.
- `Template Method` para listados administrativos:
  - Archivos: `frontend/src/modules/admin/pages/AdminOrdersPage.vue`, `frontend/src/modules/admin/pages/AdminCustomersPage.vue`.
  - Problema que resuelve: fija un flujo repetible de carga -> metricas -> filtros -> tabla -> modal -> accion confirmada, de modo que ambas vistas sigan la misma secuencia operativa y de feedback.
- `Orquestacion de servicios`:
  - Archivos: `frontend/src/modules/admin/pages/AdminCustomersPage.vue`, `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php`.
  - Problema que resuelve: permite enriquecer clientes con pedidos, recompra y valor acumulado sin romper limites de microservicios; `auth-service` conserva el perfil y `order-service` aporta el historial comercial.

Cambios relevantes:
- `frontend/src/modules/admin/pages/AdminOrdersPage.vue` ahora concentra filtros avanzados, metricas, exportacion CSV, modal de detalle y flujo de cambio de estado con alerta reutilizable.
- `frontend/src/modules/admin/pages/AdminOrderDetailPage.vue` corrige el contrato de cambio de estado enviando `status`, que es la llave validada por `services/order-service/app/Http/Controllers/OrderController.php`.
- `frontend/src/modules/admin/pages/AdminCustomersPage.vue` integra filtros por segmento, metricas reales derivadas de pedidos, modal de perfil comercial y bloqueo/desbloqueo con confirmacion centralizada.
- `services/order-service/app/Http/Controllers/Admin/AdminOrderController.php` amplifica el contrato admin para soportar filtros por texto, estado, pago, rango de fechas y estadisticas agregadas consumidas por la SPA.

Extension 2026-04-05: modulos `Reseñas` y `Preguntas` con paridad operativa legacy

Objetivo:
- Llevar `frontend/src/modules/admin/pages/AdminReviewsPage.vue` y `frontend/src/modules/admin/pages/AdminQuestionsPage.vue` al mismo flujo de moderacion de Angelow legacy, pero consumiendo `catalog-service` y enriqueciendo identidades desde `auth-service` sin romper limites de microservicios.

Patrones aplicados:
- `Facade visual reutilizable`:
  - Archivos: `frontend/src/modules/admin/pages/AdminReviewsPage.vue`, `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`, `frontend/src/modules/admin/components/AdminPageHeader.vue`, `frontend/src/modules/admin/components/AdminCard.vue`, `frontend/src/modules/admin/components/AdminModal.vue`, `frontend/src/modules/admin/components/AdminStatsGrid.vue`, `frontend/src/modules/admin/components/AdminTableShimmer.vue`, `frontend/src/modules/admin/components/AdminEmptyState.vue`.
  - Problema que resuelve: mantiene una misma experiencia de admin para cabeceras, filtros, tarjetas, modales, estados vacios y loaders sin rehacer infraestructura visual por cada modulo.
- `Adapter`:
  - Archivos: `frontend/src/modules/admin/pages/AdminReviewsPage.vue`, `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`, `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`.
  - Problema que resuelve: normaliza diferencias entre legacy y microservicios (`is_approved` vs `status`, `answer` vs `answer_text`) para que la SPA trabaje con contratos consistentes aunque el esquema fisico no sea identico.
- `Orquestacion de servicios`:
  - Archivos: `frontend/src/modules/admin/composables/useAdminCustomerProfiles.js`, `frontend/src/modules/admin/pages/AdminReviewsPage.vue`, `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`, `services/auth-service/app/Http/Controllers/Api/Admin/AdminUserController.php`.
  - Problema que resuelve: permite enriquecer reseñas y preguntas con nombre/avatar del cliente usando `auth-service` mientras `catalog-service` conserva la propiedad del dominio de contenido.
- `Command` para acciones de moderacion:
  - Archivos: `frontend/src/modules/admin/pages/AdminReviewsPage.vue`, `frontend/src/modules/admin/pages/AdminQuestionsPage.vue`, `services/catalog-service/routes/api.php`, `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`.
  - Problema que resuelve: encapsula aprobar, volver a revision, verificar, responder y eliminar como acciones explicitas confirmadas con alertas, reduciendo errores operativos y manteniendo trazabilidad del flujo.

Cambios relevantes:
- `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php` ahora ajusta reseñas al esquema real de `product_reviews` (`is_approved`, `is_verified`) y preguntas al de `question_answers` (`answer`, `is_seller`), ademas de soportar eliminacion de reseñas y preguntas.
- `services/catalog-service/routes/api.php` expone `DELETE /admin/reviews/{id}` y `DELETE /admin/questions/{id}` para paridad de moderacion.
- `services/auth-service/app/Http/Controllers/Api/Admin/AdminUserController.php` acepta `ids` en `GET /api/admin/customers` para resolver perfiles concretos desde la SPA sin cargar listados innecesarios.
- `frontend/src/modules/admin/composables/useAdminCustomerProfiles.js` centraliza la carga de perfiles para no duplicar logica entre modulos.
- `frontend/src/modules/admin/pages/AdminReviewsPage.vue` replica la bandeja legacy con filtros, distribucion de rating, reseñas recientes, detalle modal y acciones confirmadas de publicar, verificar y eliminar.
- `frontend/src/modules/admin/pages/AdminQuestionsPage.vue` replica la bandeja legacy con filtros, resumen de respondidas, detalle modal, timeline de respuestas, validacion en tiempo real del formulario y eliminacion confirmada.

Extension 2026-04-05: modulos `Anuncios`, `Definir envios`, `Reglas por precio`, `Descuentos por cantidad` y `Codigos de descuento`

Objetivo:
- Llevar `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`, `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`, `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`, `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue` y `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue` al mismo flujo operativo de Angelow legacy, pero consumiendo `notification-service`, `shipping-service` y `discount-service` con componentes reutilizables y contratos estables.

Patrones aplicados:
- `Facade visual reutilizable`:
  - Archivos: `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`, `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`, `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`, `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue`, `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`, `frontend/src/modules/admin/components/AdminPageHeader.vue`, `frontend/src/modules/admin/components/AdminCard.vue`, `frontend/src/modules/admin/components/AdminModal.vue`, `frontend/src/modules/admin/components/AdminStatsGrid.vue`, `frontend/src/modules/admin/components/AdminTableShimmer.vue`, `frontend/src/modules/admin/components/AdminEmptyState.vue`.
  - Problema que resuelve: conserva una experiencia administrativa coherente para filtros, estadisticas, bandejas, modales, estados vacios y loaders sin duplicar infraestructura visual por modulo.
- `Adapter`:
  - Archivos: `services/shipping-service/app/Http/Controllers/Admin/AdminShippingController.php`, `services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php`, `services/notification-service/app/Http/Controllers/Admin/AdminNotificationController.php`, `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`, `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`, `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue`, `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`, `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`.
  - Problema que resuelve: normaliza diferencias entre el esquema real de microservicios y los nombres que esperaba el frontend (`discount_value/value`, `start_date/expires_at`, `is_active/active`, `message/content`, `button_link/url`) para sostener compatibilidad durante la migracion.
- `Command` para operaciones administrativas:
  - Archivos: `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue`, `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue`, `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue`, `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue`, `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue`.
  - Problema que resuelve: encapsula crear, editar, eliminar y exportar como acciones explicitas confirmadas por alertas reutilizables, reduciendo errores operativos y evitando `confirm()` dispersos.
- `Active Record` con Eloquent:
  - Archivos: `services/shipping-service/app/Models/ShippingMethod.php`, `services/shipping-service/app/Models/ShippingPriceRule.php`, `services/discount-service/app/Models/DiscountCode.php`, `services/discount-service/app/Models/DiscountType.php`, `services/discount-service/app/Models/BulkDiscountRule.php`.
  - Problema que resuelve: mueve los dominios de envios y descuentos desde Query Builder puro a modelos de dominio simples para mantener payloads consistentes, casts claros y menor acoplamiento en controladores admin.
- `Fallback de fuente de datos`:
  - Archivo: `services/notification-service/app/Http/Controllers/Admin/AdminNotificationController.php`.
  - Problema que resuelve: permite que `Anuncios` siga operando aunque la tabla no exista en la base distribuida local, usando `legacy_mysql` cuando corresponde y manteniendo continuidad operativa en la migracion.

Cambios relevantes:
- `frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue` replica la bandeja legacy de anuncios con filtros, metricas, preview, detalle modal, editor con validacion por campo, exportacion CSV y confirmacion centralizada al eliminar.
- `frontend/src/modules/admin/pages/AdminShippingMethodsPage.vue` replica la gestion de metodos de envio con filtros por cobertura, metricas, detalle comercial y formulario con validacion en tiempo real de costos, dias y umbral gratis.
- `frontend/src/modules/admin/pages/AdminShippingRulesPage.vue` replica la gestion de tarifas por rangos con resumen comercial, detalle modal, exportacion y validacion de minimos/maximos/costo.
- `frontend/src/modules/admin/pages/AdminBulkDiscountsPage.vue` adapta el flujo legacy al esquema real de `bulk_discount_rules`, mostrando reglas globales por volumen sin inventar scopes inexistentes en la base distribuida.
- `frontend/src/modules/admin/pages/AdminDiscountCodesPage.vue` replica la gestion de codigos promocionales con filtros, resumen de vigencia, modal detalle y formulario validado por campo.
- `services/shipping-service/app/Http/Controllers/Admin/AdminShippingController.php` ahora expone y persiste `description`, `base_cost`, `delivery_time`, `estimated_days_min`, `estimated_days_max`, `free_shipping_minimum`, `city`, `icon` y estado normalizado.
- `services/discount-service/app/Http/Controllers/Admin/AdminDiscountController.php` ahora soporta codigos y descuentos por cantidad usando Eloquent y normaliza tipos, valores, fechas y estados para la SPA.
- `services/notification-service/app/Http/Controllers/Admin/AdminNotificationController.php` amplifica `announcements` con normalizacion de tipos, prioridad, upload de imagen y compatibilidad `message/content` y `button_link/url`.
- `docker-compose.yml` agrega `AUTH_SERVICE_URL` y `AUTH_INTERNAL_TOKEN` en `shipping-service`, `discount-service` y `notification-service` para eliminar los `401` por middleware admin durante el consumo desde la SPA.

Extension 2026-04-07: correccion de galeria y color en `Detalles del Producto`

Objetivo:
- Corregir en `frontend/src/modules/admin/pages/AdminProductsPage.vue` el caso donde el modal mostraba fallback de imagen (`No image`) aun existiendo imagen real, y recuperar visualizacion de color siguiendo el flujo de Angelow.

Patron aplicado:
- `Adapter` de payload heterogeneo:
  - Archivo: `frontend/src/modules/admin/pages/AdminProductsPage.vue`.
  - Problema que resuelve: unifica datos de color e imagen que llegan por rutas distintas (`variants`, `size_variants`, `images`, `variant_images`) en una sola estructura estable para la vista rapida.

Decisiones de implementacion:
- Se evita promover como imagen principal una URL fallback cuando existen imagenes reales.
- Se prioriza imagen primaria valida y luego primera imagen no-fallback para alinear comportamiento con legacy.
- Se normaliza el nombre de color desde campos alternos (`color_name`, `color`, `color_label`) y desde relacion por `color_variant_id`.
- Se prioriza una sola fuente de imagenes por apertura de modal (`variant_images` -> `images` -> `variants.images`) para evitar mezclar fuentes redundantes.
- Se deduplican miniaturas por URL resuelta para impedir repeticion visual de la misma imagen en la galeria.

Extension 2026-04-15: exportación de `Todos los productos` en CSV y PDF con corrección UTF-8

Objetivo:
- Corregir problemas de codificación en la exportación CSV de productos (mojibake en acentos/ñ) y agregar exportación PDF en la misma vista admin sin romper la paridad visual.

Patrones aplicados:
- `Template Method`:
  - Archivo: `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`.
  - Problema que resuelve: centraliza en `productRowsForExport` la obtención y normalización de datos para que CSV y PDF compartan exactamente la misma fuente y criterios.
- `Adapter`:
  - Archivo: `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`.
  - Problema que resuelve: adapta payloads heterogéneos (`name/nombre`, `is_active/activo`, rangos de precio) y corrige UTF-8 on-the-fly antes de exportar.
- `Command`:
  - Archivo: `frontend/src/modules/admin/pages/AdminProductsPage.vue`.
  - Problema que resuelve: encapsula acciones de usuario `exportProductsCsv` y `exportProductsPdf` como comandos explícitos con feedback consistente por snackbar.

Archivos impactados en esta extensión:
- `services/catalog-service/routes/api.php`
- `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
- `frontend/src/modules/admin/pages/AdminProductsPage.vue`
- `frontend/src/services/http.js`
- `services/catalog-service/composer.json`
- `services/catalog-service/composer.lock`
