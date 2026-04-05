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
