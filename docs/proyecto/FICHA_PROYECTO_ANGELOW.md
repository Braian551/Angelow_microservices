# Ficha del Proyecto: ANGELOW

**Índice navegable**

- [1. Información general](#1-información-general)
- [2. Instructores participantes en la formación](#2-instructores-participantes-en-la-formación)
- [3. Información de aprendices que desarrollarán el proyecto](#3-información-de-aprendices-que-desarrollarán-el-proyecto)
- [4. Descripción del proyecto](#4-descripción-del-proyecto)
- [5. Justificación](#5-justificación)
- [6. APIs a utilizar](#6-apis-a-utilizar)
- [7. Objetivos del proyecto](#7-objetivos-del-proyecto)
- [8. Antecedentes](#8-antecedentes)
- [9. Resultados esperados](#9-resultados-esperados)
- [10. Módulos del software](#10-módulos-del-software)
- [11. Alcance](#11-alcance)
- [12. Fechas del proyecto](#12-fechas-del-proyecto)
- [13. Resultados de comité](#13-resultados-de-comité)
- [14. Control del documento](#14-control-del-documento)
- [15. Control de cambios](#15-control-de-cambios)

---

## 1. Información general

| Campo | Información |
|---|---|
| **Título del proyecto** | Desarrollo de un Sistema de Gestión de Ventas para Ropa Infantil (ANGELOW) |
| **Sector productivo objetivo del proyecto** | Tienda Virtual de Ropa Infantil con Causa: Venta al Detal y Mayorista (ANGELOW) |
| **Entidad o Centro del SENA asociado** | Centro Textil y de Gestión Industrial (proyecto formativo) |
| **Programa de formación asociado** | ADSO |
| **Nro. de ficha** | 3147208 |
| **Instructor titular** | Edilfredo Pineda |

---

## 2. Instructores participantes en la formación

| Nombres y apellidos | Competencia que imparte | Observaciones | Fecha |
|---|---|---|---|
| Edilfredo Pineda | Diseñar la solución de software de acuerdo con procedimientos y requisitos técnicos. |  | Segundo trimestre |
| Héctor Maya | Evaluar requisitos de la solución de software de acuerdo con metodologías de análisis y estándares. |  | Segundo trimestre |
| Juan David Carvajal | Evaluar requisitos de la solución de software de acuerdo con metodologías de análisis y estándares. |  | Segundo trimestre |

---

## 3. Información de aprendices que desarrollarán el proyecto

| Documento de identidad | Nombres y apellidos | E-mail | Teléfono |
|---|---|---|---|
| 1023526011 | Braian Andrés Oquendo Durango | braianoquen@gmail.com | 302 2613326 |

---

## 4. Descripción del proyecto

Angelow es una plataforma de comercio electrónico para la venta de ropa infantil al detal y al por mayor. El proyecto responde a la necesidad de contar con una tienda virtual especializada que permita publicar productos, administrar inventario por variantes, recibir pedidos, validar pagos por transferencia, coordinar envíos, generar facturas y mantener comunicación clara con clientes y administradores.

La versión actual del sistema se desarrolla como una migración desde una solución monolítica hacia una arquitectura de microservicios. El frontend funciona como una aplicación SPA en Vue 3 con Vite, mientras que el backend se organiza en servicios Laravel independientes para autenticación, catálogo, carrito, pedidos, pagos, descuentos, envíos, notificaciones y auditoría. Cada dominio conserva su propia base de datos PostgreSQL y se apoya en Redis para colas, tareas programadas, reservas de inventario y eventos en tiempo real.

Desde la experiencia del cliente, el sistema permite navegar por inicio, tienda, colecciones y detalle de producto; buscar productos; consultar tallas, colores, imágenes, reseñas y preguntas; agregar productos al carrito; seleccionar productos específicos para pagar; registrar dirección con apoyo de mapa; cargar comprobante de pago; consultar pedidos, facturas, favoritos, notificaciones y configuración de cuenta. También incluye recuperación de contraseña, verificación de correo en registro e inicio de sesión con Google.

Desde la experiencia administrativa, Angelow incorpora un panel para gestionar clientes, administradores, productos, categorías, colecciones, tallas, inventario, órdenes, pagos, reembolsos, facturas, métodos y reglas de envío, descuentos, campañas, anuncios, sliders, configuración general, reportes y alertas operativas. El sistema mantiene trazabilidad de acciones relevantes mediante historial de pedidos, auditoría y notificaciones internas.

El desafío principal del proyecto consiste en consolidar una plataforma funcional, segura y escalable, capaz de mantener la experiencia visual de Angelow, distribuir responsabilidades por dominio, evitar sobreventas mediante reservas de stock, controlar pagos manuales con comprobantes y ofrecer información actualizada a clientes y administradores.

Esta ficha se alinea con la documentación técnica actual del repositorio: [manual técnico](../operaciones/manual-tecnico.md), [arquitectura web](../arquitectura/arquitectura-web-plantuml.md), [modelo relacional completo](../arquitectura/modelo-relacional-completo-plantuml.md), [historias de usuario](../referencias/historias-usuario-angelow.md), [casos de uso](../referencias/casos-uso-angelow.md) y [matriz de requerimientos funcionales](../referencias/matriz-requerimientos-funcionales-actualizada.md).

---

## 5. Justificación

En Colombia existen diversas plataformas de comercio electrónico y servicios en línea que buscan ofrecer soluciones de compra y venta de productos, como ropa, tecnología o alimentos. Sin embargo, el sector de ropa infantil requiere experiencias más especializadas, con manejo claro de tallas, colores, variantes, disponibilidad, promociones, envíos y comunicación postventa.

Aunque existen tiendas en línea generalistas como Éxito, Falabella o MercadoLibre, estas plataformas suelen manejar catálogos amplios que no siempre responden a necesidades específicas de una tienda infantil, como control de stock por talla y color, favoritos, alertas de inventario, preguntas y reseñas por producto, validación manual de pagos por transferencia y seguimiento cercano del pedido.

Angelow aprovecha esa oportunidad al ofrecer una plataforma enfocada en ropa infantil, con una experiencia de compra guiada y una operación administrativa centralizada. El cliente puede descubrir productos, guardar favoritos, comprar de forma ordenada y consultar el estado de sus pedidos, mientras que el administrador puede controlar inventario, pagos, reembolsos, facturas, promociones, contenido visible y reportes del negocio.

La arquitectura actual por microservicios fortalece la justificación del proyecto porque permite separar responsabilidades, escalar módulos de forma independiente, mantener bases de datos por dominio y mejorar la trazabilidad de la operación. Esta organización reduce el acoplamiento del sistema y facilita que el proyecto continúe creciendo sin depender de una sola aplicación monolítica.

En este contexto, la implementación de una tienda virtual especializada en ropa infantil no solo satisface una necesidad comercial, sino que también contribuye a la transformación digital de un negocio retail, mejora la atención a clientes y brinda herramientas reales para tomar decisiones sobre ventas, inventario, promociones y servicio postventa.

Por lo tanto, Angelow tiene impacto comercial y operativo: permite vender en línea, organizar procesos internos, reducir errores manuales, mantener informados a los usuarios y fortalecer la presencia digital de la marca.

---

## 6. APIs a utilizar

### 6.1 Inicio de sesión

El sistema utiliza `auth-service` para registro, inicio de sesión, cierre de sesión, perfil, cambio de contraseña, recuperación de cuenta y verificación de correo durante el registro. El ingreso con Google se integra desde el frontend mediante Firebase Authentication y se valida contra el servicio de autenticación para crear o reutilizar la cuenta del usuario.

### 6.2 Pagos

El sistema utiliza `payment-service` para consultar bancos colombianos, mostrar la cuenta bancaria activa, registrar pagos por transferencia, cargar comprobantes y permitir la verificación administrativa del pago. La facturación se complementa desde `order-service`, que genera, descarga y reenvía facturas asociadas a las órdenes.

### 6.3 Geolocalización

**OpenStreetMap y Nominatim:** apoyan la búsqueda, referencia y selección de ubicación de entrega. En el frontend se usa Leaflet para mostrar mapas interactivos en la gestión de direcciones y en el proceso de envío.

### 6.4 Seguridad

**Cloudflare Turnstile:** protege formularios sensibles como registro, inicio de sesión condicionado y recuperación de contraseña. Además, el sistema usa Laravel Sanctum para tokens de acceso, control de sesión, roles de cliente y administrador, verificación de intentos fallidos y bloqueo temporal ante accesos repetidos incorrectos.

---

## 7. Objetivos del proyecto

### 7.1 Objetivo general

Desarrollar y consolidar una plataforma de E-commerce integral para la comercialización mayorista y minorista de ropa infantil, basada en frontend SPA y microservicios Laravel, que permita gestionar catálogo, carrito, pedidos, pagos, envíos, descuentos, notificaciones, inventario, facturación, reportes y administración del negocio de forma segura, escalable y organizada por dominios.

### 7.2 Objetivos específicos

1. Planificar los requerimientos del software, identificando funcionalidades esenciales y deseables para clientes, administradores y procesos automáticos, con soporte en historias de usuario, casos de uso y matriz de requerimientos funcionales.

2. Diseñar e implementar la base de datos distribuida por microservicio y las interfaces gráficas del sistema, asegurando una estructura eficiente para usuarios, catálogo, carrito, pedidos, pagos, descuentos, envíos, notificaciones y auditoría.

3. Verificar que los requerimientos del software estén completos, sean correctos y se encuentren alineados con la arquitectura actual, realizando pruebas funcionales sobre APIs, frontend, flujos de compra, administración, inventario, pagos y notificaciones.

4. Implementar, validar y documentar el sistema, incluyendo despliegue local con Docker Compose, importación de datos, revisión de logs, capacitación de usuarios finales y ajustes derivados de pruebas o retroalimentación.

---

## 8. Antecedentes

El comercio electrónico en Medellín, al igual que a nivel global, ha experimentado una transformación significativa gracias a la adopción de tecnologías como APIs, aplicaciones web, sistemas de geolocalización, automatización de notificaciones y herramientas de análisis para la operación comercial.

En Medellín, diversas empresas de E-commerce han integrado soluciones digitales para mejorar la atención al cliente y personalizar la experiencia de compra. Plataformas como TiendaMIA han implementado soluciones automatizadas de atención al cliente, facilitando transacciones y soporte en tiempo real. Esta tendencia se relaciona con la necesidad de que las tiendas virtuales no solo publiquen productos, sino que también administren inventario, pagos, envíos y comunicación con el cliente.

La Cámara de Comercio de Medellín para Antioquia ha promovido la digitalización empresarial a través de programas de transformación digital, apoyando a empresas locales en la adopción de tecnologías que facilitan la interacción con consumidores y optimizan operaciones comerciales.

En respuesta a una demanda creciente de eficiencia en el comercio electrónico, algunas tiendas virtuales han integrado sistemas de geolocalización, seguimiento de entregas y comunicación automatizada. Estas herramientas fortalecen la confianza porque permiten al cliente conocer mejor el proceso de compra y entrega.

Grandes plataformas de E-commerce, como Mercado Libre, también han incorporado opciones de ubicación, seguimiento, métodos de pago y comunicación de estados. Sin embargo, un proyecto especializado como Angelow puede adaptar estas capacidades a un nicho concreto: ropa infantil con variantes de talla y color, control de stock, pagos por transferencia, favoritos, preguntas, reseñas y atención postventa.

### 8.1 Conclusión de antecedentes

La integración de APIs, geolocalización, notificaciones, reportes y arquitectura modular representa una tendencia necesaria para el comercio electrónico actual. Angelow aplica estas capacidades en un contexto formativo y productivo, orientado a una tienda infantil que necesita vender en línea, organizar su operación y mantener comunicación clara con clientes y administradores.

---

## 9. Resultados esperados

### 9.1 Planificación de requerimientos del software

**Resultado esperado:** una documentación funcional y técnica que describa los módulos del sistema, sus usuarios, reglas de negocio, datos requeridos, casos de uso, historias de usuario y criterios de validación.

También se espera mantener una matriz de requerimientos funcionales actualizada, organizada por módulos principales de negocio, con subprocesos, reglas del negocio y requisitos de información para orientar el desarrollo y la validación del proyecto.

Además, se espera una arquitectura definida por dominios, con frontend SPA, microservicios Laravel, PostgreSQL por servicio, Redis para colas/eventos y documentación navegable que permita sostener el crecimiento del sistema.

### 9.2 Diseño de la base de datos y mockups

**Resultado esperado:** bases de datos independientes y coherentes para `auth-service`, `catalog-service`, `cart-service`, `order-service`, `payment-service`, `discount-service`, `shipping-service`, `notification-service` y `audit-service`, con relaciones internas por dominio y referencias lógicas entre servicios.

Se esperan interfaces funcionales y responsivas para tienda pública, detalle de producto, carrito, checkout, cuenta de cliente y panel administrativo, conservando la identidad visual de Angelow y priorizando claridad en formularios, tablas, filtros, modales, estados vacíos, alertas y reportes.

También se esperan flujos gráficos completos para registro, inicio de sesión, recuperación de contraseña, gestión de direcciones, pagos con comprobante, seguimiento de pedidos, solicitudes de reembolso, administración de catálogo, inventario, pagos, envíos, descuentos, anuncios, sliders, facturas e informes.

### 9.3 Verificación de requerimientos y pruebas de funcionalidad

**Resultado esperado:** confirmación de que las funcionalidades principales se implementan correctamente en cada dominio: usuarios y acceso, navegación pública, catálogo, favoritos, carrito, checkout, pedidos, pagos, facturación, inventario, descuentos, envíos, notificaciones, reembolsos, administración, reportes y auditoría.

Se espera ejecutar pruebas de APIs y validaciones funcionales sobre los servicios Laravel, además de revisar la integración desde el frontend, los endpoints de salud, los logs de contenedores y los flujos críticos de compra y administración.

También se espera verificar reglas sensibles como validaciones en tiempo real, bloqueo de doble envío, control de roles, protección de formularios con Turnstile, disponibilidad de archivos subidos, resolución de imágenes, reservas de stock, actualización de estados y notificaciones.

### 9.4 Implementación, pruebas de funcionalidad y capacitación de usuarios finales

**Resultado esperado:** el sistema implementado y operando en entorno local con Docker Compose, frontend disponible en Vite, APIs por dominio activas, bases PostgreSQL separadas, Redis operativo, workers de cola y gateway de tiempo real para eventos de stock.

Se espera realizar pruebas finales de usabilidad, seguridad básica, rendimiento funcional y consistencia de datos, verificando que los usuarios puedan navegar, comprar, pagar, consultar pedidos y gestionar su cuenta, y que los administradores puedan operar catálogo, inventario, pagos, pedidos, reembolsos, facturas, descuentos, envíos, contenido y reportes.

También se espera una capacitación basada en la documentación del proyecto, explicando el uso del panel administrativo, el proceso de compra, la validación de pagos, la gestión de inventario, la publicación de contenido y la consulta de reportes.

Finalmente, se espera que la retroalimentación permita ajustar detalles de experiencia, operación y documentación, manteniendo la plataforma alineada con las necesidades reales del negocio y del proceso formativo.

---

## 10. Módulos del software

### 10.1 Módulo de gestión de usuarios

**Funcionalidad:** gestiona la creación, autenticación, recuperación, perfil, roles y administración de usuarios, incluyendo clientes y administradores.

**Características:**

- Registro de usuarios con validación de correo, contraseña, teléfono y aceptación de términos.
- Inicio de sesión con correo o teléfono, contraseña y opción de ingreso con Google.
- Recuperación de contraseña con código enviado al correo.
- Perfil de usuario con edición de datos, foto y cambio de contraseña.
- Roles y permisos para clientes y administradores.
- Bloqueo, reactivación y administración de clientes y administradores desde el panel.
- Protección con tokens de acceso, Turnstile, control de intentos fallidos y bloqueo temporal.

**Resultado esperado:** un sistema seguro y escalable que permita a los usuarios registrarse, autenticarse, recuperar acceso y gestionar sus cuentas, manteniendo control administrativo sobre clientes y equipo interno.

### 10.2 Módulo de gestión de productos

**Funcionalidad:** administra la información comercial y visual de los productos ofrecidos en la tienda.

**Características:**

- Creación, edición, activación, desactivación y eliminación de productos.
- Gestión de categorías, colecciones, tallas, colores, variantes e imágenes.
- Detalles de producto con descripción, precio, género, disponibilidad, imágenes, reseñas y preguntas.
- Búsqueda, sugerencias, historial de búsqueda y productos destacados.
- Administración de reseñas, preguntas y respuestas desde el panel.
- Configuración de sliders, productos visibles y contenido asociado al catálogo.

**Resultado esperado:** un catálogo estructurado que permita a los administradores mantener productos actualizados y a los clientes encontrar prendas por categoría, colección, talla, color, precio, disponibilidad o búsqueda.

### 10.3 Módulo de inventario

**Funcionalidad:** controla el stock de productos por variante y evita inconsistencias durante la compra.

**Características:**

- Visualización de inventario por producto, color y talla.
- Ajuste y transferencia de stock entre variantes.
- Historial de movimientos de inventario.
- Alertas de stock bajo, crítico o agotado.
- Reservas temporales de stock al crear pedidos.
- Confirmación o liberación de reservas según pago, cancelación o vencimiento.
- Actualización de eventos de stock mediante Redis y gateway WebSocket.

**Resultado esperado:** un sistema que evite ventas de productos agotados, reduzca sobreventas y mantenga trazabilidad sobre los cambios de inventario.

### 10.4 Módulo de carrito de compras

**Funcionalidad:** permite a los usuarios preparar, modificar y seleccionar productos antes de finalizar la compra.

**Características:**

- Agregar, actualizar y eliminar productos del carrito.
- Validar disponibilidad, cantidad, producto y variante antes de continuar.
- Calcular totales y subtotales según productos seleccionados.
- Seleccionar productos específicos del carrito para pagar.
- Conservar carrito por usuario o sesión.
- Enviar recordatorios controlados de carritos abandonados cuando aplique.

**Resultado esperado:** una experiencia de compra fluida que permita ajustar la compra antes del checkout y avanzar solo con productos válidos y disponibles.

### 10.5 Módulo de pagos

**Funcionalidad:** gestiona pagos por transferencia bancaria, comprobantes y facturación asociada a pedidos.

**Características:**

- Consulta de bancos colombianos y cuenta bancaria activa.
- Registro de pagos con referencia y comprobante.
- Soporte de comprobantes en archivo para revisión administrativa.
- Validación manual de pagos por parte del administrador.
- Sincronización del estado de pago con la orden.
- Generación, descarga y reenvío de facturas en PDF.
- Configuración administrativa de la cuenta bancaria usada por los clientes.

**Resultado esperado:** un sistema de pagos confiable que permita registrar transferencias, revisar comprobantes, actualizar pedidos y entregar soporte documental mediante facturas.

### 10.6 Módulo de envío y seguimiento de pedidos

**Funcionalidad:** gestiona direcciones, métodos de envío, creación de órdenes, estados, seguimiento y postventa.

**Características:**

- Creación, edición, eliminación y selección de direcciones del cliente.
- Dirección predeterminada y ubicación de entrega con apoyo de mapa.
- Métodos y reglas de envío administrables.
- Estimación de costo de envío durante el checkout.
- Creación de pedidos con productos, dirección, método de envío, descuentos y pago.
- Historial de estados, cancelaciones, actualizaciones de pago y seguimiento del pedido.
- Solicitudes de reembolso con motivo y evidencia.
- Actualización de cambios relevantes mediante notificaciones y eventos en tiempo real.

**Resultado esperado:** un sistema que brinde transparencia a los clientes sobre sus pedidos y permita al administrador coordinar la operación logística, estados de orden, cancelaciones y solicitudes postventa.

### 10.7 Módulo de administración

**Funcionalidad:** permite a los administradores operar y controlar los procesos principales de la plataforma.

**Características:**

- Panel de control con métricas, gráficos, alertas y accesos rápidos.
- Búsqueda global de órdenes, productos, clientes y accesos administrativos.
- Gestión de clientes, administradores, productos, categorías, colecciones, tallas e inventario.
- Gestión de pedidos, pagos, reembolsos, facturas, comprobantes y estados.
- Gestión de métodos de envío, reglas de envío, códigos de descuento y descuentos por cantidad.
- Publicación y control de anuncios, sliders y configuración general del sitio.
- Moderación de reseñas y preguntas.
- Exportaciones administrativas en PDF y Excel según la vista.

**Resultado esperado:** una herramienta integral que facilite la gestión diaria de la tienda y permita operar desde un solo panel los dominios principales del negocio.

### 10.8 Módulo de reportes y análisis

**Funcionalidad:** genera informes, métricas y trazabilidad para apoyar la toma de decisiones.

**Características:**

- Reportes de ventas por período, ingresos, órdenes, descuentos y métodos de pago.
- Reportes de productos populares, productos con bajo stock y rendimiento de catálogo.
- Reportes de clientes recurrentes, valor acumulado y comportamiento de compra.
- Gráficos administrativos y métricas principales del dashboard.
- Exportación de información en PDF y Excel.
- Auditoría de acciones sobre pedidos, usuarios y productos.

**Resultado esperado:** información clara y accesible que permita analizar la operación, detectar prioridades y tomar decisiones sobre ventas, inventario, clientes y control interno.

### 10.9 Módulo de wishlist y alertas personalizadas

**Funcionalidad:** acompaña al cliente durante el descubrimiento de productos y mantiene comunicación sobre eventos relevantes.

**Características:**

- Guardar y quitar productos favoritos.
- Consultar lista personal de favoritos desde la cuenta de usuario.
- Recibir notificaciones sobre pedidos, pagos, inventario, descuentos o eventos postventa.
- Configurar preferencias de notificación por evento o canal.
- Mostrar anuncios activos y campañas promocionales cuando correspondan.
- Enviar alertas administrativas sobre pagos, inventario, pedidos o reembolsos pendientes.

**Resultado esperado:** mayor fidelidad y retorno de clientes gracias a recordatorios, favoritos, mensajes oportunos y comunicación clara entre sistema, cliente y administración.

---

## 11. Alcance

### 11.1 Planificación de requerimientos del software

- Definir las funcionalidades esenciales y deseables de cada módulo del sistema, incluyendo usuarios, catálogo, carrito, checkout, pedidos, pagos, facturación, inventario, envíos, descuentos, notificaciones, administración, reportes y auditoría.
- Mantener historias de usuario, casos de uso y matriz de requerimientos funcionales como base para desarrollo, validación y seguimiento.
- Determinar recursos técnicos del proyecto: frontend Vue, APIs Laravel, PostgreSQL por servicio, Redis, Docker Compose, almacenamiento compartido de uploads y documentación navegable.
- Establecer una arquitectura escalable por microservicios, con integración entre dominios mediante APIs, eventos, colas y referencias lógicas.

### 11.2 Diseño de la base de datos y mockups

- Desarrollar bases de datos por dominio para manejar usuarios, productos, carrito, inventario, pedidos, pagos, descuentos, direcciones, notificaciones y auditoría.
- Crear interfaces gráficas para tienda pública, cuenta de cliente, checkout y panel administrativo, con diseño coherente, validaciones claras y navegación SPA.
- Garantizar que las pantallas sean fáciles de usar, responsivas y consistentes con la identidad visual de Angelow.
- Diseñar flujos específicos para registro, inicio de sesión, recuperación, catálogo, carrito, pago por transferencia, dirección con mapa, seguimiento de pedidos, reembolsos, reportes y administración.

### 11.3 Verificación de requerimientos y pruebas de funcionalidad

- Comprobar que los requerimientos del software sean completos y correctos, asegurando que cada módulo esté alineado con los objetivos del sistema.
- Validar integraciones entre servicios, como carrito con catálogo, órdenes con catálogo e inventario, pagos con órdenes, descuentos con checkout, envíos con órdenes y notificaciones con eventos del sistema.
- Realizar pruebas de funcionalidad sobre formularios, APIs, endpoints de salud, flujos de compra, administración de productos, gestión de pagos, reportes, facturas, reembolsos y notificaciones.
- Verificar reglas críticas de seguridad, roles, sesiones, archivos subidos, reservas de stock, estados de pedido, disponibilidad de productos y mensajes al usuario.

### 11.4 Implementación, pruebas de funcionalidad y capacitación de usuarios finales

- Implementar el sistema de forma integral usando Docker Compose para levantar frontend, APIs, bases de datos, Redis, workers y gateway en tiempo real.
- Realizar pruebas de usabilidad, rendimiento funcional y consistencia de datos en los flujos principales de cliente y administración.
- Capacitar a usuarios finales y administradores en navegación, compra, consulta de pedidos, validación de pagos, gestión de productos, inventario, envíos, descuentos, contenido, reportes y soporte postventa.
- Ajustar el sistema según los resultados de pruebas y retroalimentación, manteniendo documentación técnica, funcional y operativa actualizada.

---

## 12. Fechas del proyecto

| Campo | Fecha |
|---|---|
| **Fecha de inicio** | 14-11-2024 |
| **Fecha de terminación** | Por definir |

---

## 13. Resultados de comité

> Para uso exclusivo del comité.

| Entrega | Aprobado | Por ajustar | Rechazado | Fecha |
|---|---|---|---|---|
| Primera entrega |  |  |  | DD / MM / AA |
| Segunda entrega |  |  |  | DD / MM / AA |
| Tercera entrega |  |  |  | DD / MM / AA |

| Campo | Información |
|---|---|
| **Coordinador de proyecto** |  |
| **Asesor técnico** |  |
| **Observaciones** |  |

---

## 14. Control del documento

| Rol | Nombre | Cargo | Área | Fecha |
|---|---|---|---|---|
| Autores | Lilliana Uribe | Instructora | ADSO | 14 noviembre |
| Autores | Nicolas Garzon | Instructor | ADSO |  |
| Revisión |  |  |  | Mayo de 2017 |
| Aprobación |  |  |  | Mayo de 2017 |

---

## 15. Control de cambios

| Descripción del cambio | Razón del cambio | Fecha | Responsable / cargo |
|---|---|---|---|
| Actualización de la ficha con el estado actual funcional y técnico de Angelow. | Alinear la ficha del proyecto con la arquitectura de microservicios, frontend SPA, módulos implementados y documentación vigente del repositorio. | 29-06-2026 | Braian Andrés Oquendo Durango / Aprendiz ADSO |
