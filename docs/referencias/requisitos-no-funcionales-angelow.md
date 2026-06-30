# Requisitos no funcionales de Angelow

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Estándar usado](#estándar-usado)
- [Matriz de requisitos no funcionales](#matriz-de-requisitos-no-funcionales)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Este documento reúne los requisitos no funcionales de Angelow en lenguaje claro, orientado a usuarios, administradores, soporte y evaluadores del proyecto. La matriz toma como base el estándar ISO/IEC 25010 y aterriza sus atributos a la tienda, el panel administrativo, los microservicios, las notificaciones, los pagos, el inventario y la operación diaria.

No describe cómo programar cada requisito. Su propósito es dejar claro qué calidad debe tener el software para que sea rápido, seguro, confiable, fácil de usar, mantenible y adaptable.

## Estándar usado

**ESTÁNDAR ISO/IEC 25010**

Los atributos se agrupan en: adecuación funcional, rendimiento, compatibilidad, usabilidad, fiabilidad, seguridad, mantenibilidad y portabilidad.

## Matriz de requisitos no funcionales

| Nro. | Descripción | Atributo | Criterios |
|---|---|---|---|
| RNF-001 | La tienda debe permitir consultar productos, carrito, pedidos, pagos, envíos, notificaciones y cuenta de usuario de forma completa según las funciones definidas para Angelow. | ADECUACIÓN FUNCIONAL | • Completitud funcional<br>• Corrección funcional<br>• Pertinencia funcional |
| RNF-002 | El visitante debe poder navegar el catálogo, buscar productos y ver detalles sin crear cuenta, dejando el registro solo para acciones como comprar, guardar favoritos o consultar pedidos. |  |  |
| RNF-003 | El sistema debe permitir completar una compra desde la selección del producto hasta la confirmación del pedido sin depender de pasos externos no explicados al cliente. |  |  |
| RNF-004 | Las áreas de administración deben ofrecer las funciones necesarias para gestionar productos, inventario, pedidos, pagos, descuentos, contenido, clientes, informes y auditoría. |  |  |
| RNF-005 | Cada función visible debe responder con información coherente con su propósito; por ejemplo, el carrito debe mostrar productos seleccionados y los pedidos deben mostrar estados reales. |  |  |
| RNF-006 | Las reglas de negocio importantes, como stock disponible, límites de descuento, datos obligatorios y permisos de usuario, deben aplicarse antes de guardar cambios. |  |  |
| RNF-007 | La aplicación debe cargar las pantallas principales de tienda, carrito, checkout, cuenta y panel administrativo en menos de 4 segundos en condiciones normales de conexión. | RENDIMIENTO | • Comportamiento en el tiempo<br>• Utilización de recursos<br>• Capacidad |
| RNF-008 | El sistema debe soportar al menos 100 usuarios navegando al mismo tiempo sin que la experiencia de compra se vuelva lenta o inestable. |  |  |
| RNF-009 | Las imágenes de productos, sliders, categorías y comprobantes deben cargarse de forma controlada, mostrando alternativas cuando todavía están cargando o no están disponibles. |  |  |
| RNF-010 | Las búsquedas de productos, filtros de catálogo y listados administrativos deben responder rápidamente aun cuando existan muchos productos, pedidos o clientes. |  |  |
| RNF-011 | El sistema debe usar información frecuente, índices, vistas o caché cuando sea necesario para mostrar más rápido productos, búsquedas, reportes y notificaciones. |  |  |
| RNF-012 | Los procesos pesados, como correos, notificaciones, reservas de inventario o tareas programadas, deben ejecutarse en segundo plano cuando no sea necesario bloquear al usuario. |  |  |
| RNF-013 | El panel administrativo debe mantener una navegación fluida entre secciones sin recargar toda la aplicación ni perder el contexto del administrador. |  |  |
| RNF-014 | Los cambios importantes de pedidos, pagos e inventario deben reflejarse oportunamente en las pantallas abiertas mediante actualización automática o consulta periódica. |  |  |
| RNF-015 | Angelow debe poder convivir con servicios separados de usuarios, catálogo, carrito, pedidos, pagos, descuentos, envíos, notificaciones, auditoría y tiempo real sin mezclar responsabilidades. | COMPATIBILIDAD | • Coexistencia<br>• Interoperabilidad |
| RNF-016 | Los microservicios deben compartir solo la información necesaria para completar una operación, evitando duplicar o exponer datos que no sean requeridos. |  |  |
| RNF-017 | La aplicación debe poder integrarse con servicios externos como Google, Firebase, Cloudflare Turnstile, correo electrónico y futuros servicios de pago o mensajería. |  |  |
| RNF-018 | Las APIs deben responder con formatos claros y estables para que el frontend pueda consumirlas sin cambios innecesarios en cada módulo. |  |  |
| RNF-019 | El sistema debe permitir que futuras aplicaciones móviles o canales externos consulten productos, pedidos y notificaciones sin rediseñar toda la plataforma. |  |  |
| RNF-020 | Durante la migración, el sistema debe poder consultar información distribuida o heredada sin mostrar diferencias confusas al usuario final. |  |  |
| RNF-021 | La interfaz debe ser fácil de entender para visitantes, clientes y administradores, usando textos claros en español y evitando palabras internas o códigos técnicos. | USABILIDAD | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| RNF-022 | Un usuario nuevo debe poder aprender a buscar productos, agregarlos al carrito y avanzar al pago en menos de 5 minutos. |  |  |
| RNF-023 | Todos los formularios deben mostrar errores claros antes de guardar cuando falten datos o exista un formato incorrecto. |  |  |
| RNF-024 | Las acciones importantes, como eliminar, cancelar, aprobar pagos, modificar inventario o resolver reembolsos, deben pedir confirmación o mostrar un estado de carga visible. |  |  |
| RNF-025 | Los botones, tablas, filtros, modales y mensajes del panel administrativo deben mantener un diseño consistente para que el equipo trabaje sin confusión. |  |  |
| RNF-026 | La aplicación debe mostrar estados vacíos, errores y cargas con mensajes comprensibles, sin dejar pantallas en blanco ni mostrar detalles internos. |  |  |
| RNF-027 | Los valores internos como estados de pedido, métodos de pago o motivos de reembolso deben mostrarse al usuario con palabras naturales en español. |  |  |
| RNF-028 | La tienda y el panel deben tener contraste, tamaños de texto y botones adecuados para facilitar la lectura y el uso en computador, tablet y celular. |  |  |
| RNF-029 | La experiencia visual debe conservar una apariencia profesional, ordenada y coherente con la identidad de Angelow en todas las pantallas. |  |  |
| RNF-030 | La plataforma debe estar disponible todos los días para permitir compras, consultas y administración cuando el negocio lo necesite. | FIABILIDAD | • Madurez<br>• Disponibilidad<br>• Tolerancia a fallos<br>• Capacidad de recuperación |
| RNF-031 | Si un servicio no responde, la aplicación debe mostrar un mensaje claro y permitir reintentar sin perder la información ya ingresada cuando sea posible. |  |  |
| RNF-032 | El carrito debe conservar productos por usuario o sesión para que una interrupción de conexión o cierre del navegador no obligue a empezar desde cero. |  |  |
| RNF-033 | Las reservas de inventario deben evitar sobreventas y liberar productos cuando una compra no se complete dentro del tiempo permitido. |  |  |
| RNF-034 | Los pedidos, pagos, reembolsos e inventario deben conservar trazabilidad para revisar qué ocurrió si hay un error o reclamo. |  |  |
| RNF-035 | Las notificaciones y correos importantes deben poder reintentarse o quedar registrados cuando no sea posible enviarlos en el primer intento. |  |  |
| RNF-036 | La información guardada en bases de datos debe persistir aunque se reinicie un contenedor o servicio. |  |  |
| RNF-037 | El sistema debe contar con consultas de disponibilidad o salud para que soporte pueda revisar rápidamente si las áreas principales están operando. |  |  |
| RNF-038 | La aplicación debe proteger el acceso contra intentos no autorizados, ataques comunes y abuso de formularios públicos. | SEGURIDAD | • Confidencialidad<br>• Integridad<br>• No repudio<br>• Responsabilidad<br>• Autenticidad |
| RNF-039 | Las contraseñas y códigos de recuperación deben guardarse protegidos, de forma que nadie pueda leerlos directamente después de almacenarlos. |  |  |
| RNF-040 | El sistema debe aplicar roles y permisos para separar lo que pueden hacer visitantes, clientes y administradores. |  |  |
| RNF-041 | Los datos personales, direcciones, pagos, comprobantes, facturas y solicitudes de reembolso deben mostrarse solo a usuarios autorizados. |  |  |
| RNF-042 | El inicio de sesión debe controlar intentos fallidos, pedir verificación adicional cuando sea necesario y bloquear temporalmente accesos repetidos sospechosos. |  |  |
| RNF-043 | El registro y la recuperación de contraseña deben usar verificación de seguridad para reducir solicitudes automáticas o malintencionadas. |  |  |
| RNF-044 | Las comunicaciones internas entre servicios deben usar tokens o mecanismos equivalentes para evitar consultas no autorizadas. |  |  |
| RNF-045 | Los archivos cargados, como imágenes y comprobantes, deben validarse antes de guardarse y resolverse con rutas públicas seguras. |  |  |
| RNF-046 | El sistema debe registrar acciones importantes como accesos, cambios de pedidos, pagos, inventario, usuarios, productos y reembolsos. |  |  |
| RNF-047 | La información sensible usada para correo, verificación y servicios externos debe configurarse como variable de entorno y no quedar expuesta en pantallas públicas. |  |  |
| RNF-061 | Los términos y condiciones deben estar visibles desde registro, pago y footer, explicando de forma clara el tratamiento de datos personales, compras, pagos, envíos y postventa. |  |  |
| RNF-048 | El software debe estar organizado por módulos y servicios independientes para que sea posible corregir o mejorar una parte sin afectar toda la tienda. | MANTENIBILIDAD | • Modularidad<br>• Reusabilidad<br>• Analizabilidad<br>• Capacidad de modificación<br>• Capacidad de prueba |
| RNF-049 | Los cambios en frontend y backend deben probarse antes de considerarse listos, especialmente en compras, pagos, autenticación, inventario y notificaciones. |  |  |
| RNF-050 | Los componentes repetidos de administración, formularios, tablas, modales, estados de carga y exportaciones deben reutilizarse para mantener consistencia. |  |  |
| RNF-051 | La documentación debe mantenerse actualizada cuando cambien arquitectura, datos, operaciones, dependencias, pruebas o flujos principales. |  |  |
| RNF-052 | El sistema debe permitir analizar errores mediante logs, historial, auditoría y respuestas controladas sin depender solo de revisión manual del código. |  |  |
| RNF-053 | Las reglas compartidas, como validaciones numéricas, traducción de estados y resolución de archivos, deben mantenerse centralizadas cuando se usen en varias pantallas. |  |  |
| RNF-054 | Las nuevas funciones deben agregarse siguiendo la separación por dominio: usuarios, catálogo, carrito, pedidos, pagos, envíos, descuentos, notificaciones y auditoría. |  |  |
| RNF-055 | La aplicación debe funcionar correctamente en navegadores modernos como Chrome, Firefox, Safari y Edge. | PORTABILIDAD | • Adaptabilidad<br>• Facilidad de instalación<br>• Intercambiabilidad |
| RNF-056 | La tienda y el panel administrativo deben adaptarse automáticamente a pantallas de computador, tablet y teléfono móvil. |  |  |
| RNF-057 | Los botones, imágenes, menús y formularios deben tener tamaños adecuados para usarse cómodamente con dedo en dispositivos móviles. |  |  |
| RNF-058 | El sistema debe poder instalarse y levantarse en un entorno Docker siguiendo pasos documentados y repetibles. |  |  |
| RNF-059 | Cada microservicio debe poder ejecutarse con su propia base de datos sin impedir que los demás servicios sigan operando según su responsabilidad. |  |  |
| RNF-060 | Los administradores deben poder exportar productos, pedidos, clientes, informes, reseñas o preguntas para revisarlos fuera del sistema cuando lo necesiten. |  |  |

## Fuentes revisadas

- `README.md`: servicios, puertos, comandos base, documentación y operación local.
- `docker-compose.yml`: microservicios, bases PostgreSQL, Redis, colas, workers, scheduler, frontend y gateway de tiempo real.
- `docs/operaciones/manual-tecnico.md`: instalación, migraciones, validaciones, seguridad de autenticación, correo, pruebas y operación.
- `docs/referencias/matriz-requerimientos-funcionales-actualizada.md`: módulos funcionales, reglas de negocio y requisitos de información.
- `docs/referencias/historias-usuario-angelow.md`: necesidades de visitantes, clientes, administradores, soporte y sistema.
- `docs/referencias/casos-uso-angelow.md`: flujos principales de tienda, cuenta, compra, postventa, administración e integración.
- `frontend/package.json`: tecnologías principales del frontend, exportaciones, gráficos, rutas y consumo de APIs.
- `frontend/src/modules/legal/pages/TermsAndConditionsPage.vue`: vista pública de términos y condiciones y referencias legales visibles.
- `services/*/routes/api.php` y controladores revisados por búsqueda: autenticación, catálogo, carrito, pedidos, pagos, envíos, descuentos, notificaciones y auditoría.

## Documentos relacionados

- [Matriz de requerimientos funcionales](matriz-requerimientos-funcionales-actualizada.md)
- [Historias de usuario](historias-usuario-angelow.md)
- [Casos de uso del sistema](casos-uso-angelow.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
- [Índice general de documentación](../README.md)
- [Ficha del proyecto](../proyecto/FICHA_PROYECTO_ANGELOW.md)
