# Diagramas UML de casos de uso de Angelow

Esta colección modela objetivos funcionales iniciados por actores externos y sus dependencias UML justificadas. La matriz también contiene automatismos, validaciones, sincronizaciones y reglas internas: estos se mantienen trazados como requerimientos internos relacionados, sin convertirlos artificialmente en óvalos ni en actores del sistema.

## Criterio de representación

- Un óvalo representa un objetivo de un actor externo o un caso incluido cuya ejecución es obligatoria para un caso base concreto.
- `<<include>>` se usa únicamente cuando el caso base debe ejecutar el comportamiento incluido. No se emplea por afinidad temática.
- Las reglas, validaciones transversales, automatismos temporizados y colaboraciones internas se documentan en la sección `Requerimientos internos relacionados` del subproceso correspondiente.
- La cobertura se mide por requerimientos funcionales trazados, no por cantidad de óvalos.

## Cobertura

- Requerimientos funcionales trazados: **420 de 420**.
- Módulos: **17**.
- Subprocesos: **86**.
- Diagramas PlantUML: **102**.
- Documentos de trazabilidad interna sin diagrama: **10**.
- Fuente de verdad: [matriz de requerimientos funcionales](../matriz-requerimientos-funcionales-actualizada.md).
- [Trazabilidad completa de requerimientos](trazabilidad-requerimientos.md).
- [Especificación funcional](../casos-uso-angelow.md).

## Índice por módulo

- [Gestión de Usuarios y Acceso](gestion-de-usuarios-y-acceso/)
- [Navegación Pública](navegacion-publica/)
- [Catálogo y Productos](catalogo-y-productos/)
- [Reseñas, Preguntas y Favoritos](resenas-preguntas-y-favoritos/)
- [Direcciones y Envíos](direcciones-y-envios/)
- [Carrito de Compras](carrito-de-compras/)
- [Pagos y Facturación](pagos-y-facturacion/)
- [Órdenes](ordenes/)
- [Inventario](inventario/)
- [Promociones y Descuentos](promociones-y-descuentos/)
- [Contenido y Configuración del Sitio](contenido-y-configuracion-del-sitio/)
- [Panel Administrativo](panel-administrativo/)
- [Repartidores y Entregas](repartidores-y-entregas/)
- [Notificaciones y Postventa](notificaciones-y-postventa/)
- [Informes y Auditoría](informes-y-auditoria/)
- [Ayuda y Manual de Usuario](ayuda-y-manual-de-usuario/)
- [Operación e Integración del Sistema](operacion-e-integracion-del-sistema/)

Cada documento enlaza a la matriz y a la especificación funcional. Los archivos de subprocesos puramente internos conservan la misma trazabilidad, aunque no contienen un bloque PlantUML porque no existe un caso de uso externo que diagramar.
