# Validaciones numéricas reutilizables en admin y cliente

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Patrones aplicados](#patrones-aplicados)
- [Archivos impactados](#archivos-impactados)
- [Reglas de validación](#reglas-de-validación)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Objetivo

Unificar las validaciones de cantidades, inventario, carrito y precios COP para impedir decimales, ceros, negativos, campos vacíos y símbolos no válidos antes de persistir datos.

## Patrones aplicados

### Facade

Referencia: https://refactoring.guru/es/design-patterns/facade

Aplicación:
- `frontend/src/utils/numericValidation.js`
- `frontend/src/modules/admin/pages/AdminProductFormPage.vue`
- `frontend/src/modules/admin/pages/AdminInventoryPage.vue`
- `frontend/src/modules/catalog/pages/ProductDetailPage.vue`
- `frontend/src/modules/cart/pages/CartPage.vue`

Problema resuelto:
- Las vistas consumen una interfaz simple para validar enteros positivos, normalizar COP y formatear moneda sin duplicar expresiones regulares ni reglas de negocio.

### Strategy

Referencia: https://refactoring.guru/es/design-patterns/strategy

Aplicación:
- `frontend/src/utils/numericValidation.js`
- `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`
- `services/cart-service/app/Http/Controllers/CartController.php`

Problema resuelto:
- Cada tipo de dato numérico usa una estrategia explícita: cantidades como enteros físicos positivos y precios COP como enteros sin centavos. Esto evita que una conversión genérica con `Number()` o `(int)` acepte valores inválidos.

## Archivos impactados

- `frontend/src/utils/numericValidation.js`: helper reutilizable para enteros positivos, cantidades y precios COP.
- `frontend/src/modules/admin/pages/AdminProductFormPage.vue`: validación en tiempo real de precio base, precio comparativo, variantes, stock y bloqueo de guardado.
- `frontend/src/modules/admin/pages/AdminInventoryPage.vue`: validación en tiempo real de ajustes y transferencias de stock, con bloqueo de doble envío.
- `frontend/src/modules/catalog/pages/ProductDetailPage.vue`: validación de cantidad antes de agregar al carrito o comprar ahora.
- `frontend/src/modules/cart/pages/CartPage.vue`: validación de cantidad al actualizar ítems del carrito.
- `services/catalog-service/app/Http/Controllers/Admin/AdminCatalogController.php`: rechazo backend de precios y cantidades decimales, cero, negativos o inválidos.
- `services/cart-service/app/Http/Controllers/CartController.php`: mensajes backend en español para cantidades inválidas.

## Reglas de validación

- Cantidades, stock e inventario deben ser enteros positivos mayores o iguales a `1`.
- Precios COP deben guardarse como enteros, sin centavos ni separadores visuales.
- El punto se acepta solo como separador de miles válido, por ejemplo `$68.799`; entradas como `1.5`, `10.99` o `0.6` se rechazan.
- El frontend muestra errores por campo en tiempo real y bloquea el envío si existe un error.
- El backend repite la validación para evitar persistencia inválida desde clientes externos.

## Documentos relacionados

- [Índice de patrones](../README.md)
- [Manual técnico](../../operaciones/manual-tecnico.md)
- [Guía de patrones frontend](../../../frontend/docs/patrones-diseno.md)
