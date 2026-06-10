# Registro de Patrones de Diseno (Refactoring Guru)

<!-- indice:auto:start -->
## Índice rápido

- [Cambio: Direcciones + selector de ubicacion + snackbar global](#cambio-direcciones-selector-de-ubicacion-snackbar-global)
  - [1) Facade](#1-facade)
  - [2) Mediator](#2-mediator)
  - [3) Singleton (estado compartido de UI)](#3-singleton-estado-compartido-de-ui)
- [Cambio: Validaciones numéricas reutilizables](#cambio-validaciones-numéricas-reutilizables)
  - [1) Facade](#1-facade-1)
  - [2) Strategy](#2-strategy)
- [Checklist para futuros cambios](#checklist-para-futuros-cambios)
<!-- indice:auto:end -->

## Cambio: Direcciones + selector de ubicacion + snackbar global
Fecha: 2026-04-02

### 1) Facade
Referencia: https://refactoring.guru/es/design-patterns/facade

Aplicacion:
- `src/composables/useSnackbarSystem.js`
- `src/composables/useAlertSystem.js`

Problema resuelto:
- Se evita que cada vista implemente su propia logica de notificaciones.
- El frontend usa una interfaz simple (`showSnackbar`, `showAlert`) en vez de manipular estado UI complejo en cada pagina.

### 2) Mediator
Referencia: https://refactoring.guru/es/design-patterns/mediator

Aplicacion:
- `src/modules/account/pages/AddressesPage.vue` (orquestador)
- `src/modules/account/components/AddressLocationPickerModal.vue` (componente especializado)

Problema resuelto:
- Se desacopla la vista de direcciones de la logica de mapa.
- La comunicacion se centraliza por eventos (`confirm`, `update:modelValue`) evitando dependencias directas entre bloques UI.

### 3) Singleton (estado compartido de UI)
Referencia: https://refactoring.guru/es/design-patterns/singleton

Aplicacion:
- Estado reactivo de modulo en `useSnackbarSystem.js` y `useAlertSystem.js`.

Problema resuelto:
- Un unico estado global de feedback para toda la SPA, con comportamiento consistente.

## Cambio: Validaciones numéricas reutilizables
Fecha: 2026-06-10

### 1) Facade
Referencia: https://refactoring.guru/es/design-patterns/facade

Aplicación:
- `src/utils/numericValidation.js`
- `src/modules/admin/pages/AdminProductFormPage.vue`
- `src/modules/admin/pages/AdminInventoryPage.vue`
- `src/modules/catalog/pages/ProductDetailPage.vue`
- `src/modules/cart/pages/CartPage.vue`

Problema resuelto:
- Las vistas usan una interfaz única para validar cantidades, stock y precios COP sin duplicar reglas.
- El precio visual con separador de miles se normaliza antes de enviar al backend.

### 2) Strategy
Referencia: https://refactoring.guru/es/design-patterns/strategy

Aplicación:
- `src/utils/numericValidation.js`

Problema resuelto:
- Cada regla numérica tiene una estrategia clara: enteros positivos para unidades físicas y enteros COP sin centavos para precios.
- Se rechazan decimales como `1.5`, `0.6` o `10.99` antes del submit.

## Checklist para futuros cambios
- Identificar el problema de diseno antes de codificar.
- Seleccionar patron del catalogo Refactoring Guru y justificarlo.
- Documentar archivos impactados y beneficio concreto del patron.
- Si no aplica patron, dejar justificacion explicita.
