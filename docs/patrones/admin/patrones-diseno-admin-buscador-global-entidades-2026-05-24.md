# Patrones de diseño aplicados: buscador global admin para órdenes, facturas y clientes (2026-05-24)

<!-- indice:auto:start -->
## Índice rápido

- [Contexto](#contexto)
- [Patrón 1: Facade (orquestador único de búsqueda admin)](#patrón-1-facade-orquestador-único-de-búsqueda-admin)
- [Patrón 2: Adapter (contrato homogéneo de resultados)](#patrón-2-adapter-contrato-homogéneo-de-resultados)
- [Patrón 3: Observer (sincronización por query para foco contextual)](#patrón-3-observer-sincronización-por-query-para-foco-contextual)
- [Resultado esperado](#resultado-esperado)
<!-- indice:auto:end -->

Fecha: 2026-05-24

## Contexto
- El buscador del header admin solo resolvía módulos estáticos.
- Términos reales como números de orden, factura, nombres de cliente o correos no devolvían resultados navegables.
- Al hacer clic en una coincidencia de factura o cliente, la vista destino necesitaba llegar ya filtrada y enfocada en el registro correcto.

## Patrón 1: Facade (orquestador único de búsqueda admin)
- Referencia: https://refactoring.guru/es/design-patterns/facade
- Problema que resuelve: unificar en un solo punto la búsqueda de módulos, órdenes, facturas y clientes sin repartir la lógica entre varios componentes.
- Archivos aplicados:
  - frontend/src/modules/admin/components/AdminHeader.vue
- Implementación:
  - `runSearch()` centraliza la búsqueda estática y las consultas paralelas a `/admin/orders`, `/admin/invoices` y `/admin/customers`.
  - El header decide ranking, límite y navegación del resultado prioritario con un único contrato de uso.

## Patrón 2: Adapter (contrato homogéneo de resultados)
- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: cada endpoint devuelve payloads distintos y el dropdown necesita renderizar una lista uniforme.
- Archivos aplicados:
  - frontend/src/modules/admin/components/AdminHeader.vue
- Implementación:
  - `mapOrderResult()`, `mapInvoiceResult()` y `mapCustomerResult()` adaptan cada respuesta al formato común `id`, `title`, `subtitle`, `url` e `icon`.
  - La normalización del texto mantiene coincidencias tolerantes a acentos y diferencias menores de escritura.

## Patrón 3: Observer (sincronización por query para foco contextual)
- Referencia: https://refactoring.guru/es/design-patterns/observer
- Problema que resuelve: cuando el buscador navega a facturas o clientes, la pantalla debe reaccionar al contexto recibido por URL para abrir el foco correcto sin pasos manuales extra.
- Archivos aplicados:
  - frontend/src/modules/admin/pages/AdminInvoicesPage.vue
  - frontend/src/modules/admin/pages/AdminCustomersPage.vue
- Implementación:
  - Ambas vistas observan `route.fullPath` para hidratar filtros desde query params.
  - Facturas abre la vista rápida del registro solicitado y clientes enfoca el perfil del usuario enviado por el buscador.

## Resultado esperado
- El admin puede buscar órdenes, facturas, clientes y módulos desde el header con resultados útiles.
- Enter y clic sobre una coincidencia redirigen al contexto correcto.
- Facturas y clientes reciben el estado inicial desde la URL y quedan listas para revisión inmediata.