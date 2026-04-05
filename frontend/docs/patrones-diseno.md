# Registro de Patrones de Diseno (Refactoring Guru)

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

## Checklist para futuros cambios
- Identificar el problema de diseno antes de codificar.
- Seleccionar patron del catalogo Refactoring Guru y justificarlo.
- Documentar archivos impactados y beneficio concreto del patron.
- Si no aplica patron, dejar justificacion explicita.
