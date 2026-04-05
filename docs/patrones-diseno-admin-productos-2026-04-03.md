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
