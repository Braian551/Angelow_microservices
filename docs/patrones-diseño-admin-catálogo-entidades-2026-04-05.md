# Patrones de diseno aplicados en categorias, colecciones, tallas e inventario

Fecha: 2026-04-05

## Objetivo
Migrar las vistas de categorias, colecciones, tallas e inventario del admin hacia el flujo funcional de Angelow legacy, manteniendo arquitectura SPA en microservicios, componentes reutilizables y consumo exclusivo del catalog-service.

## Patron 1: Facade visual para vistas admin
- Problema que resuelve:
  - Evita que cada vista implemente por separado cabeceras, tablas, estados vacios, loaders y modales con estilos inconsistentes.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminCategoriesPage.vue
  - frontend/src/modules/admin/pages/AdminCollectionsPage.vue
  - frontend/src/modules/admin/pages/AdminSizesPage.vue
  - frontend/src/modules/admin/pages/AdminInventoryPage.vue
  - frontend/src/modules/admin/components/AdminPageHeader.vue
  - frontend/src/modules/admin/components/AdminCard.vue
  - frontend/src/modules/admin/components/AdminStatsGrid.vue
  - frontend/src/modules/admin/components/AdminEmptyState.vue
  - frontend/src/modules/admin/components/AdminTableShimmer.vue
  - frontend/src/modules/admin/components/AdminModal.vue
- Como se usa:
  - Cada vista consume la misma fachada visual para construir encabezado, metricas, tabla principal y feedback sin repetir markup estructural.

## Patron 2: Template Method en flujos CRUD ligeros
- Problema que resuelve:
  - Las vistas de entidades comparten la misma secuencia: cargar, normalizar, filtrar, abrir modal, validar en tiempo real, guardar, refrescar, confirmar eliminacion.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminCategoriesPage.vue
  - frontend/src/modules/admin/pages/AdminCollectionsPage.vue
  - frontend/src/modules/admin/pages/AdminSizesPage.vue
- Como se usa:
  - Aunque cada vista cambia sus campos, todas siguen la misma plantilla operativa con funciones equivalentes de load, normalize, validate, openModal, save, toggleStatus y confirmDelete.

## Patron 3: Adapter para respuesta heterogenea de datos
- Problema que resuelve:
  - El catalog-service convive con columnas legacy y nuevas como name/nombre, description/descripcion, image/imagen, is_active/activo.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminCategoriesPage.vue
  - frontend/src/modules/admin/pages/AdminCollectionsPage.vue
  - frontend/src/modules/admin/pages/AdminSizesPage.vue
  - frontend/src/modules/admin/pages/AdminInventoryPage.vue
  - services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
- Como se usa:
  - El backend normaliza parte del contrato y el frontend termina de adaptar la forma final con funciones normalizeCategory, normalizeCollection, normalizeSize, normalizeInventoryRow y normalizeProductDetail.

## Patron 4: Command orientado a acciones de inventario
- Problema que resuelve:
  - Ajustar y transferir stock son acciones con reglas distintas y no deben mezclarse en una sola operacion ambigua.
- Aplicacion:
  - frontend/src/modules/admin/pages/AdminInventoryPage.vue
  - services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
  - services/catalog-service/routes/api.php
- Como se usa:
  - El frontend dispara comandos concretos: ajuste de stock y transferencia.
  - El backend expone endpoints separados para cada accion y registra cada movimiento en stock_history.

## Patron 5: Repository informal via controlador admin
- Problema que resuelve:
  - Las vistas no deben conocer joins, columnas opcionales ni reglas de integridad del catalogo distribuido.
- Aplicacion:
  - services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php
- Como se usa:
  - El controlador concentra consultas, conteos, validaciones de borrado, historial y transformaciones de inventario antes de responder al frontend.

## Resultado
- Las cuatro vistas quedan alineadas con el flujo legacy, pero sin romper el modelo SPA del dashboard.
- Las reglas criticas no viven solo en frontend: eliminacion de tallas y transferencias se controlan tambien en backend.
- Inventario gana trazabilidad con historial por producto y acciones separadas para ajuste y transferencia.
