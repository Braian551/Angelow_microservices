# Plan operativo anual de semillero 2026 - GIAITEQ SOFT

## Información general

| Campo | Información para diligenciar |
|---|---|
| Nombre del grupo o semillero | GIAITEQ - SOFT |
| Regional | Antioquia |
| Centro de formación | Centro Textil y de Gestión Industrial |
| Grupo al cual está adscrito el semillero | GIAITEQ - Grupo de investigación aplicada a la industria, textil y a la química |
| Proyecto | Desarrollo de un Sistema de Gestión de Ventas para Ropa Infantil (ANGELOW) |
| Sector productivo | Tienda virtual de ropa infantil con causa: venta al detal y mayorista |
| Programa de formación | Análisis y Desarrollo de Software (ADSO) |
| Ficha | 3147208 |
| Instructor titular | Edilfredo Pineda |
| Aprendiz responsable | Braian Andrés Oquendo Durango |
| Documento | 1023526011 |
| Correo | braianoquen@gmail.com |
| Teléfono | 302 2613326 |
| Vigencia simulada del cronograma | Enero a agosto de 2026 |
| Modalidad de trabajo | Proyecto formativo desarrollado por un integrante, con acompañamiento de instructores y revisión por fases |

## Resumen del proyecto

ANGELOW es una plataforma de comercio electrónico para la venta minorista y mayorista de ropa infantil. El proyecto se planificó desde el inicio con una arquitectura de microservicios, frontend SPA y APIs separadas por dominio, buscando una solución escalable, mantenible y segura para gestionar usuarios, catálogo, inventario, carrito de compras, pedidos, pagos, envíos, notificaciones, reportes y administración.

El sistema responde a la necesidad de digitalizar una tienda especializada en ropa infantil, con funcionalidades adaptadas al sector: variantes por talla y color, control de stock, validación de comprobantes de pago, pago contra entrega en Medellín, seguimiento de pedidos con geolocalización y panel administrativo para toma de decisiones.

## Objetivo general

Desarrollar una plataforma de E-commerce integral para la comercialización mayorista y minorista de ropa infantil, incorporando gestión especializada de productos, seguimiento de pedidos, pagos seguros, administración operativa y reportes para apoyar la transformación digital de ANGELOW.

## Objetivos específicos del cronograma

| Objetivo específico | Enfoque dentro del cronograma 2026 |
|---|---|
| Planificar los requerimientos del software | Levantamiento de información, matriz de requerimientos, historias de usuario, alcance, riesgos y selección tecnológica. |
| Diseñar la base de datos y las interfaces gráficas | Modelado por dominios, diagramas, mockups, diseño de experiencia de usuario y arquitectura técnica. |
| Construir y verificar los módulos del sistema | Desarrollo iterativo de microservicios, frontend SPA, validaciones, APIs, integración entre módulos y pruebas funcionales. |
| Implementar, probar y capacitar | Despliegue con Docker, pruebas finales, documentación, capacitación de usuario final y ajustes según retroalimentación. |

## Tecnologías propuestas y justificadas

| Categoría | Tecnología / herramienta | Uso dentro del proyecto |
|---|---|---|
| Control de versiones | Git + Git Flow | Organización del trabajo por ramas `feature`, `fix`, `develop` y entregas controladas. |
| Contenedores | Docker + Docker Compose | Levantamiento reproducible de frontend, APIs, bases de datos, Redis y workers. |
| Backend | PHP 8.2 + Laravel 12 | Construcción de APIs REST por dominio, validaciones, controladores, modelos y servicios. |
| Frontend | Vue 3 + Vite | Interfaz SPA para clientes y administradores, con navegación fluida y componentes reutilizables. |
| Bases de datos | PostgreSQL 17 | Persistencia independiente por dominio de microservicio. |
| Cache y colas | Redis 7 | Procesamiento asíncrono, notificaciones, tareas de pedidos y optimización de rendimiento. |
| Autenticación | Laravel Sanctum | Gestión de tokens, sesiones, roles y permisos. |
| Cliente HTTP | Axios | Consumo de APIs desde el frontend. |
| Mapas | OpenStreetMap + Leaflet | Geolocalización, direcciones y seguimiento de pedidos. |
| Seguridad en formularios | reCAPTCHA API | Prevención de spam y abuso en registro/contacto. |
| Reportes | Chart.js, PDF y exportaciones | Visualización de métricas, reportes administrativos y documentos descargables. |
| Documentación | Markdown, diagramas PlantUML/Mermaid | Evidencias técnicas, arquitectura, patrones, módulos y pruebas. |

## Módulos priorizados para 2026

| Módulo | Propósito | Periodo principal de trabajo |
|---|---|---|
| Gestión de usuarios | Registro, inicio de sesión, perfiles, roles y permisos. | Marzo - abril |
| Gestión de productos | Productos, categorías, colecciones, imágenes, tallas, colores y filtros. | Marzo - mayo |
| Inventario | Stock, variantes, alertas de reposición y actualización tras compras. | Abril - mayo |
| Carrito de compras | Agregar, modificar, eliminar productos y calcular totales. | Abril - mayo |
| Pagos | Transferencia bancaria, comprobantes y pago contra entrega en Medellín. | Mayo - junio |
| Envíos y seguimiento | Direcciones, costos de envío, estados y geolocalización. | Mayo - junio |
| Administración | Panel de control, gestión de productos, pedidos, usuarios y configuración. | Junio - julio |
| Reportes y análisis | Métricas de ventas, inventario, entregas y exportaciones. | Julio - agosto |
| Notificaciones y auditoría | Alertas operativas, trazabilidad de eventos y soporte administrativo. | Julio - agosto |

## Cronograma de actividades del grupo - Vigencia 2026

Responsable único del desarrollo: Braian Andrés Oquendo Durango.

| Actividad | Tarea | Resultado | Producto / evidencia | Responsable | Enero | Febrero | Marzo | Abril | Mayo | Junio | Julio | Agosto |
|---|---|---|---|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Inicio y contextualización del proyecto | Definir problemática, justificación, sector productivo, alcance general y objetivos del sistema de ventas para ropa infantil. | Proyecto delimitado con enfoque en E-commerce especializado, venta al detal y mayorista. | Ficha de proyecto actualizada, descripción del problema, justificación y alcance inicial. | Braian Andrés Oquendo Durango | X |  |  |  |  |  |  |  |
| Levantamiento de información | Identificar actores, necesidades del cliente, procesos de venta, gestión de productos, pagos, envíos y administración. | Necesidades funcionales y operativas documentadas. | Documento de levantamiento de información, entrevistas simuladas, mapa de actores y procesos. | Braian Andrés Oquendo Durango | X | X |  |  |  |  |  |  |
| Análisis de requerimientos | Construir matriz de requerimientos funcionales, no funcionales, restricciones, prioridades y criterios de aceptación. | Requerimientos completos, verificables y trazables. | Matriz de requerimientos, historias de usuario, criterios de aceptación y backlog inicial. | Braian Andrés Oquendo Durango |  | X |  |  |  |  |  |  |
| Planeación metodológica y tecnológica | Definir metodología iterativa, Git Flow, repositorio, estándares de código, documentación y tecnologías base. | Ruta técnica de desarrollo aprobada para el semillero. | Documento de propuesta tecnológica: PHP, Laravel, Vue, Docker, PostgreSQL, Redis, Git Flow, OpenStreetMap y reCAPTCHA. | Braian Andrés Oquendo Durango |  | X |  |  |  |  |  |  |
| Diseño de arquitectura | Diseñar arquitectura basada en microservicios por dominio, frontend SPA, bases de datos independientes, colas y comunicación por APIs. | Arquitectura escalable y modular para el sistema. | Diagrama de arquitectura, mapa de servicios, definición de APIs y responsabilidades por módulo. | Braian Andrés Oquendo Durango |  | X | X |  |  |  |  |  |
| Diseño de base de datos | Modelar entidades principales: usuarios, roles, productos, variantes, inventario, carritos, pedidos, pagos, envíos, notificaciones y auditoría. | Modelo de datos preparado para soportar el sistema. | Modelo relacional, diccionario de datos, diagramas ER y definición de bases por dominio. | Braian Andrés Oquendo Durango |  |  | X |  |  |  |  |  |
| Diseño de experiencia de usuario | Crear mockups para tienda, catálogo, detalle de producto, carrito, checkout, perfil, seguimiento de pedidos y panel administrativo. | Interfaces planeadas con enfoque en usabilidad y navegación clara. | Mockups, mapa de navegación, prototipo de pantallas y guía visual inicial. | Braian Andrés Oquendo Durango |  |  | X | X |  |  |  |  |
| Preparación del entorno de desarrollo | Configurar repositorio, ramas, Docker Compose, servicios base, bases PostgreSQL, Redis, variables de entorno y flujo local. | Ambiente reproducible para desarrollo y pruebas. | Repositorio organizado, contenedores levantados, README técnico y comandos de instalación. | Braian Andrés Oquendo Durango |  |  | X | X |  |  |  |  |
| Desarrollo de autenticación y usuarios | Implementar registro, inicio de sesión con email, perfiles, roles, permisos, validaciones y protección de rutas. | Módulo de usuarios funcional y seguro. | API de autenticación, vistas de login/registro, pruebas de sesión y control de acceso. | Braian Andrés Oquendo Durango |  |  | X | X |  |  |  |  |
| Desarrollo de catálogo y productos | Implementar administración de productos, categorías, colecciones, imágenes, detalles, filtros, tallas, colores y búsqueda. | Catálogo navegable y administrable. | API de catálogo, vistas de productos, panel de administración y evidencias de CRUD. | Braian Andrés Oquendo Durango |  |  |  | X | X |  |  |  |
| Desarrollo de inventario | Implementar variantes, control de stock, actualización automática tras compra y alertas de bajo inventario. | Inventario controlado para evitar ventas de productos agotados. | Modelo de inventario, endpoints, validaciones de stock y pruebas funcionales. | Braian Andrés Oquendo Durango |  |  |  | X | X |  |  |  |
| Desarrollo de carrito de compras | Implementar agregar, modificar, eliminar productos, cálculo de subtotales, descuentos, impuestos, envío y persistencia de carrito. | Flujo de compra inicial completo y usable. | API de carrito, vistas de carrito, validaciones y pruebas de cálculo de totales. | Braian Andrés Oquendo Durango |  |  |  | X | X |  |  |  |
| Desarrollo de pedidos y checkout | Implementar flujo de checkout, creación de pedido, estados, confirmación y relación con inventario, pagos y envíos. | Proceso de compra integrado entre módulos. | API de pedidos, vista de checkout, pruebas de integración y evidencia de orden generada. | Braian Andrés Oquendo Durango |  |  |  |  | X | X |  |  |
| Desarrollo de pagos | Implementar transferencia bancaria con carga de comprobante, validación administrativa y pago contra entrega para Medellín. | Pagos gestionados de forma segura y trazable. | API de pagos, formularios, carga de comprobantes, estados de pago y validaciones. | Braian Andrés Oquendo Durango |  |  |  |  | X | X |  |  |
| Desarrollo de envíos y geolocalización | Implementar direcciones, cálculo de envío, integración con OpenStreetMap/Leaflet y seguimiento de estado del pedido. | Sistema de entregas con información clara para el cliente. | API de envíos, mapa interactivo, estados de entrega y pruebas de direcciones. | Braian Andrés Oquendo Durango |  |  |  |  | X | X |  |  |
| Desarrollo de notificaciones | Implementar alertas para pedidos, pagos, inventario, cambios de estado y comunicaciones administrativas. | Usuarios y administradores informados de eventos relevantes. | Servicio de notificaciones, cola Redis, mensajes de estado y pruebas de eventos. | Braian Andrés Oquendo Durango |  |  |  |  |  | X | X |  |
| Desarrollo del panel administrativo | Construir dashboard, gestión de usuarios, productos, inventario, pedidos, pagos, envíos, configuración y métricas. | Herramienta central para operación diaria de la tienda. | Panel administrativo funcional, tablas, filtros, formularios, modales y evidencias visuales. | Braian Andrés Oquendo Durango |  |  |  |  |  | X | X |  |
| Reportes y análisis | Generar reportes de ventas, inventario, entregas, clientes, comportamiento de compra y exportaciones. | Información útil para toma de decisiones. | Dashboard de métricas, gráficas, reportes PDF/Excel y documentación de indicadores. | Braian Andrés Oquendo Durango |  |  |  |  |  |  | X | X |
| Seguridad, calidad y accesibilidad | Revisar validaciones, mensajes, roles, protección de rutas, reCAPTCHA, consistencia visual, responsive y textos en español. | Sistema más seguro, comprensible y usable. | Lista de verificación de seguridad, pruebas responsive, revisión de formularios y evidencias. | Braian Andrés Oquendo Durango |  |  |  | X | X | X | X |  |
| Pruebas funcionales e integración | Ejecutar pruebas por módulo e integración entre catálogo, carrito, inventario, pedidos, pagos, envíos y notificaciones. | Funcionalidades verificadas frente a requerimientos. | Plan de pruebas, casos de prueba, resultados, incidencias y correcciones aplicadas. | Braian Andrés Oquendo Durango |  |  |  |  | X | X | X | X |
| Dockerización y despliegue local | Consolidar contenedores, variables de entorno, redes, bases de datos, Redis, workers y documentación de ejecución. | Plataforma ejecutable en entorno controlado. | `docker-compose.yml`, guía de instalación, evidencias de contenedores y comandos de despliegue. | Braian Andrés Oquendo Durango |  |  |  | X | X | X | X | X |
| Documentación técnica y académica | Elaborar documentación de arquitectura, patrones, base de datos, APIs, instalación, pruebas, manual técnico y manual de usuario. | Evidencias completas para revisión del semillero. | Documentos Markdown, diagramas, manual de usuario, manual técnico y bitácora del proyecto. | Braian Andrés Oquendo Durango | X | X | X | X | X | X | X | X |
| Capacitación y apropiación | Preparar guía de uso para administrador y cliente, socializar funcionalidades y recoger retroalimentación. | Usuarios finales orientados en el uso del sistema. | Presentación, guía rápida, acta de capacitación y listado de ajustes sugeridos. | Braian Andrés Oquendo Durango |  |  |  |  |  |  | X | X |
| Ajustes finales y cierre | Corregir hallazgos, optimizar rendimiento, revisar ortografía, consolidar evidencias y preparar entrega final. | Proyecto listo para sustentación y entrega académica. | Informe final, repositorio organizado, evidencias, conclusiones y recomendaciones. | Braian Andrés Oquendo Durango |  |  |  |  |  |  | X | X |

## Distribución mensual sugerida

| Mes 2026 | Enfoque principal | Actividades clave | Entregables sugeridos |
|---|---|---|---|
| Enero | Formulación del proyecto | Problema, justificación, objetivos, alcance, identificación de actores y contexto del sector de ropa infantil. | Ficha de proyecto, descripción del problema, justificación, objetivo general y objetivos específicos. |
| Febrero | Análisis y planificación | Requerimientos, matriz de trazabilidad, historias de usuario, riesgos, metodología, Git Flow y propuesta tecnológica. | Matriz de requerimientos, backlog, historias de usuario, plan de trabajo y documento de tecnologías. |
| Marzo | Diseño técnico y funcional | Arquitectura de microservicios, modelo de datos, mockups, mapa de navegación y preparación del entorno. | Diagramas de arquitectura, modelo ER, diccionario de datos, mockups y entorno Docker inicial. |
| Abril | Construcción base | Autenticación, usuarios, catálogo, productos, inventario, carrito, validaciones y primeros servicios API. | APIs iniciales, frontend SPA base, CRUD de productos, módulo de usuarios y pruebas unitarias/funcionales iniciales. |
| Mayo | Flujo de compra | Checkout, pedidos, pagos, comprobantes, pago contra entrega, envíos, direcciones y geolocalización. | Flujo de compra integrado, módulo de pagos, módulo de envíos y pruebas de integración. |
| Junio | Administración e integración | Panel administrativo, notificaciones, métricas iniciales, workers, colas Redis e integración completa de módulos. | Dashboard administrativo, notificaciones, integración entre servicios y evidencias de funcionamiento. |
| Julio | Calidad, reportes y validación | Reportes, análisis, pruebas funcionales, seguridad, responsive, accesibilidad, documentación técnica y manuales. | Reportes, plan de pruebas, resultados, manual técnico, manual de usuario y ajustes documentados. |
| Agosto | Implementación y cierre | Despliegue local controlado, capacitación, retroalimentación, correcciones finales, informe y sustentación. | Informe final, guía de instalación, acta de capacitación, presentación de sustentación y entrega final. |

## Productos esperados por fase

| Fase | Producto esperado | Evidencia para anexar |
|---|---|---|
| Planeación | Documento de requerimientos completo y propuesta tecnológica. | Matriz de requerimientos, historias de usuario, backlog, alcance y cronograma. |
| Diseño | Arquitectura, modelo de datos e interfaces planeadas. | Diagramas, mockups, diccionario de datos y mapa de navegación. |
| Construcción | Módulos funcionales desarrollados por iteraciones. | Código fuente, APIs, frontend, capturas de pantalla y commits por módulo. |
| Verificación | Pruebas funcionales e integración aprobadas. | Casos de prueba, resultados, incidencias corregidas y validación de requerimientos. |
| Implementación | Sistema ejecutable y documentado. | Guía Docker, manual técnico, manual de usuario, evidencias de despliegue y capacitación. |
| Cierre | Proyecto listo para socialización académica. | Informe final, presentación, conclusiones, recomendaciones y acta de entrega. |

## Indicadores de cumplimiento

| Indicador | Meta para agosto de 2026 |
|---|---|
| Requerimientos documentados | 100 % de funcionalidades esenciales y deseables priorizadas. |
| Módulos principales construidos | Usuarios, productos, inventario, carrito, pedidos, pagos, envíos, administración y reportes. |
| Pruebas funcionales ejecutadas | Casos de prueba por módulo e integración entre flujo de compra completo. |
| Documentación generada | Manual técnico, manual de usuario, arquitectura, base de datos, instalación y evidencias. |
| Despliegue local con Docker | Servicios ejecutables con Docker Compose, PostgreSQL por dominio y Redis. |
| Capacitación | Socialización del uso del sistema para usuario administrador y cliente final. |

## Riesgos y acciones de control

| Riesgo | Impacto | Acción preventiva |
|---|---|---|
| Alcance muy amplio para un solo integrante | Retrasos en entregas y pruebas. | Priorizar módulos esenciales y dejar mejoras como deseables. |
| Integración compleja entre servicios | Fallos en checkout, inventario, pedidos o pagos. | Probar por módulo y luego ejecutar pruebas de integración por flujo completo. |
| Errores en inventario o variantes | Ventas de productos agotados o datos inconsistentes. | Validar stock antes de crear pedidos y actualizar inventario tras confirmación. |
| Problemas de usabilidad | Dificultad para comprar o administrar productos. | Usar mockups, pruebas con usuario y ajustes de navegación. |
| Falta de evidencias académicas | Dificultad para sustentar avances. | Mantener bitácora, capturas, documentación y commits por fase. |
| Seguridad insuficiente | Riesgo en sesiones, formularios y datos personales. | Implementar roles, validaciones, tokens, reCAPTCHA y revisión de permisos. |

## Eventos y divulgación sugerida

| Tipo | Información sugerida |
|---|---|
| Evento interno CTI | Socialización de avances del proyecto ANGELOW en el semillero GIAITEQ - SOFT. |
| Fecha tentativa | Julio o agosto de 2026. |
| Participante / ponente | Braian Andrés Oquendo Durango. |
| Lugar | Centro Textil y de Gestión Industrial, Regional Antioquia. |
| Producto de divulgación | Presentación del sistema, demostración funcional, póster o informe técnico corto. |
| Enfoque de apropiación | Aplicación de microservicios, Docker, Laravel, Vue y geolocalización en un E-commerce especializado. |

## Texto sugerido para "Nombres de proyectos del grupo o semillero"

| Campo | Información |
|---|---|
| Nombre del proyecto | Desarrollo de un Sistema de Gestión de Ventas para Ropa Infantil (ANGELOW) |
| Fecha de inicio | Enero de 2026 |
| Fecha de fin | Agosto de 2026 |
| Responsable | Braian Andrés Oquendo Durango |
| Línea asociada | Desarrollo tecnológico aplicado a soluciones de software para el sector productivo y comercial. |
| Resultado esperado | Plataforma de E-commerce funcional, documentada, probada y sustentable como proyecto formativo ADSO. |

## Observaciones para diligenciar el formato POAIDI

- En la columna `Actividad`, escribir el bloque general del ciclo de software: planeación, análisis, diseño, construcción, pruebas, implementación o cierre.
- En la columna `Tarea`, describir la acción concreta realizada durante el mes.
- En la columna `Resultado`, indicar el logro verificable obtenido.
- En la columna `Producto`, colocar la evidencia que se puede anexar: documento, diagrama, mockup, API, módulo, prueba, manual o informe.
- En la columna `Responsable`, usar el nombre del aprendiz porque el proyecto cuenta con un solo integrante.
- En los meses, marcar con `X` donde la actividad tenga ejecución real o seguimiento.
- Para agosto, concentrar cierre, pruebas finales, capacitación, informe final y sustentación.
