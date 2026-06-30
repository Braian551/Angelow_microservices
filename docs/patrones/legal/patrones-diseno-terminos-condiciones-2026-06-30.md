# Patrones de diseño - Términos y condiciones

<!-- indice:auto:start -->
## Índice rápido

- [Contexto](#contexto)
- [Patrones aplicados](#patrones-aplicados)
- [Archivos intervenidos](#archivos-intervenidos)
- [Problema resuelto](#problema-resuelto)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Contexto

Se agregó una vista pública de términos y condiciones para que visitantes y clientes puedan revisar reglas de uso, compras, pagos, envíos, postventa y tratamiento de datos personales antes de registrarse o confirmar un pago.

## Patrones aplicados

- **Facade**: `frontend/src/modules/legal/pages/TermsAndConditionsPage.vue` presenta una interfaz simple y pública para consultar el documento, sin exponer cómo se organiza el contenido internamente.
- **Single Responsibility**: `frontend/src/modules/legal/content/termsAndConditions.js` concentra el texto legal, metadatos y referencias normativas, mientras la página Vue se limita a renderizarlo.
- **Template Method**: `frontend/src/modules/legal/pages/TermsAndConditionsPage.vue` mantiene una secuencia estable de encabezado, índice, secciones y referencias, lo que permite actualizar el contenido sin rediseñar la vista.

## Archivos intervenidos

- `frontend/src/modules/legal/content/termsAndConditions.js`
- `frontend/src/modules/legal/pages/TermsAndConditionsPage.vue`
- `frontend/src/modules/legal/views/TermsAndConditionsView.css`
- `frontend/src/router/index.js`
- `frontend/src/components/layout/SiteFooter.vue`
- `frontend/src/components/layout/Footer.css`
- `frontend/src/modules/auth/pages/RegisterPage.vue`
- `frontend/src/modules/auth/views/RegisterView.css`
- `frontend/src/modules/checkout/pages/PaymentPage.vue`
- `docs/referencias/matriz-requerimientos-funcionales-actualizada.md`
- `docs/referencias/requisitos-no-funcionales-angelow.md`

## Problema resuelto

Antes existía aceptación de términos en registro y pago, pero no había una ruta pública clara para leer el contenido legal. La solución agrega una vista consultable y enlaces azules desde los puntos donde el usuario decide registrarse, pagar o revisar información del sitio.

## Documentos relacionados

- [Matriz de requerimientos funcionales](../../referencias/matriz-requerimientos-funcionales-actualizada.md)
- [Requisitos no funcionales](../../referencias/requisitos-no-funcionales-angelow.md)
- [Índice de patrones](../README.md)
