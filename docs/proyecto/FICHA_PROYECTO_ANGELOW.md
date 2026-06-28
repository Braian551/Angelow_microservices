# Ficha del proyecto Angelow

<!-- indice:auto:start -->
## Índice rápido

- [Información general](#información-general)
- [Equipo académico y técnico](#equipo-académico-y-técnico)
- [Descripción actual del proyecto](#descripción-actual-del-proyecto)
- [APIs y servicios utilizados](#apis-y-servicios-utilizados)
- [Objetivos del proyecto](#objetivos-del-proyecto)
- [Arquitectura actual](#arquitectura-actual)
- [Módulos del software](#módulos-del-software)
- [Alcance](#alcance)
- [Estado actual del proyecto](#estado-actual-del-proyecto)
- [Indicadores de éxito](#indicadores-de-éxito)
- [Documentos relacionados](#documentos-relacionados)
- [Conclusiones](#conclusiones)
- [Contacto](#contacto)
<!-- indice:auto:end -->

## Información general

**Título del proyecto:** Desarrollo de un sistema de gestión de ventas para ropa infantil Angelow.

**Sector productivo objetivo:** tienda virtual de ropa infantil con venta al detal y mayorista.

**Entidad asociada:** Centro Textil y de Gestión Industrial, SENA.

**Programa de formación:** Análisis y Desarrollo de Software (ADSO).

**Nro. ficha:** 3147208.

**Instructor titular:** Edilfredo Pineda.

**Versión del documento:** 4.0.

**Fecha de actualización:** 22 de junio de 2026.

## Equipo académico y técnico

### Instructores participantes

| Nombres y apellidos | Competencia que imparte | Observaciones | Fecha |
|---|---|---|---|
| Edilfredo Pineda | Diseñar la solución de software de acuerdo con procedimientos y requisitos técnicos. | Instructor titular. | Segundo trimestre |
| Héctor Maya | Evaluar requisitos de la solución de software de acuerdo con metodologías de análisis y estándares. | Acompañamiento técnico. | Segundo trimestre |
| Juan David Carvajal | Evaluar requisitos de la solución de software de acuerdo con metodologías de análisis y estándares. | Acompañamiento técnico. | Segundo trimestre |

### Aprendices

| Documento de identidad | Nombres y apellidos | E-mail | Teléfono |
|---|---|---|---|
| 1023526011 | Braian Andrés Oquendo Durango | braianoquen@gmail.com | 302 2613326 |

## Descripción actual del proyecto

Angelow es una plataforma de comercio electrónico especializada en ropa infantil. El sistema combina storefront, cuenta de cliente, carrito, pagos por transferencia, administración de productos, control de inventario, órdenes, envíos, descuentos, notificaciones, auditoría y reportes.

El proyecto evolucionó desde una base PHP monolítica hacia una arquitectura de microservicios con frontend SPA en Vue y APIs Laravel por dominio. Esta transición permite mantener compatibilidad con datos existentes durante la migración, separar responsabilidades por servicio y escalar módulos críticos sin acoplar toda la operación a una sola aplicación.

La experiencia de compra guía al cliente por catálogo, detalle de producto, carrito, dirección de envío y comprobante de pago. El panel administrativo centraliza la operación diaria: productos, variantes, pedidos, pagos, reembolsos, facturas, envíos, clientes, reseñas, descuentos, anuncios, configuración, notificaciones e inventario.

El flujo de autenticación nativa usa correo o teléfono y contraseña. El ingreso con Google se mantiene separado mediante Firebase, usado únicamente para validar el botón social. Los formularios nativos sensibles se protegen con Cloudflare Turnstile desde backend, sin exponer la clave secreta en el frontend ni en archivos versionados.

## APIs y servicios utilizados

| Área | Servicio o API | Uso actual |
|---|---|---|
| Autenticación nativa | `auth-service` Laravel + Sanctum | Registro, inicio de sesión, perfil y recuperación de contraseña. |
| Autenticación social | Firebase | Validación del botón de ingreso con Google. |
| Seguridad de formularios | Cloudflare Turnstile | Verificación en registro, recuperación de contraseña y login condicionado por intentos fallidos. |
| Geolocalización | OpenStreetMap | Apoyo visual y operativo para direcciones de envío. |
| Catálogo | `catalog-service` | Productos, categorías, colecciones, favoritos e inventario de catálogo. |
| Carrito | `cart-service` | Carritos persistentes y preparación del proceso de compra. |
| Órdenes | `order-service` | Creación, consulta y evolución de pedidos. |
| Pagos | `payment-service` | Transferencias bancarias, comprobantes, estados de pago y facturas. |
| Envíos | `shipping-service` | Direcciones, métodos de envío y reglas logísticas. |
| Notificaciones | `notification-service` + Redis | Notificaciones, preferencias, dismissals y eventos operativos. |
| Auditoría | `audit-service` | Trazabilidad de acciones relevantes. |
| Tiempo real | `realtime-gateway` | Actualización de eventos operativos hacia la SPA. |

## Objetivos del proyecto

### Objetivo general

Desarrollar una plataforma de comercio electrónico integral para la comercialización mayorista y minorista de ropa infantil, con una experiencia de compra clara, operación administrativa centralizada, control de inventario, pagos verificables, notificaciones oportunas y una arquitectura preparada para mantenimiento y crecimiento.

### Objetivos específicos

1. Identificar y organizar los requerimientos funcionales, no funcionales y de información necesarios para la operación de Angelow.
2. Diseñar una arquitectura modular basada en frontend SPA y microservicios Laravel con base de datos por dominio.
3. Implementar una experiencia de compra usable para clientes, incluyendo catálogo, carrito, dirección, comprobante y seguimiento.
4. Construir un panel administrativo que permita gestionar productos, órdenes, pagos, envíos, clientes, descuentos, reseñas, anuncios, inventario y reportes.
5. Proteger los flujos sensibles de autenticación con validaciones de backend, control de intentos fallidos y verificación de seguridad.
6. Validar el sistema mediante builds, pruebas de endpoints, revisión de logs, pruebas manuales y documentación actualizada.

## Arquitectura actual

La arquitectura actual separa la plataforma en los siguientes componentes:

| Componente | Responsabilidad |
|---|---|
| `frontend` | SPA Vue que orquesta la experiencia pública, cuenta de cliente, checkout y administración. |
| `auth-service` | Usuarios, roles, tokens, Google, recuperación de contraseña y protección de formularios nativos. |
| `catalog-service` | Productos, categorías, colecciones, favoritos e inventario asociado al catálogo. |
| `cart-service` | Carritos, líneas de compra y persistencia previa al checkout. |
| `order-service` | Órdenes, estados y coordinación con pagos, envíos, descuentos y notificaciones. |
| `payment-service` | Comprobantes, validación administrativa, facturas y estados financieros. |
| `discount-service` | Códigos, campañas, reglas y validación de descuentos. |
| `shipping-service` | Direcciones, métodos, costos, reglas y trazabilidad logística. |
| `notification-service` | Notificaciones de usuario y panel administrativo. |
| `audit-service` | Registro de acciones y soporte de trazabilidad. |
| `realtime-gateway` | Canal de eventos en tiempo real para la interfaz. |

Cada microservicio usa PostgreSQL propio. Redis se usa para caché, colas o eventos donde aplica. El frontend mantiene navegación SPA real para no recargar cabecera, aside ni estado de sesión entre vistas.

## Módulos del software

### Módulo de gestión de usuarios

- Registro e inicio de sesión nativo con correo, teléfono y contraseña.
- Inicio de sesión con Google mediante Firebase.
- Perfil de cliente y avatar con resolución de archivos desde `/uploads`.
- Recuperación de contraseña con código seguro.
- Control de intentos fallidos y verificación adicional en login nativo.

### Módulo de seguridad de autenticación

- Registro y recuperación de contraseña protegidos siempre con Cloudflare Turnstile.
- Login nativo sin verificación inicial y con verificación obligatoria después de intentos fallidos.
- Bloqueo temporal controlado después de demasiados fallos reales de credenciales.
- Clave pública en frontend mediante `VITE_TURNSTILE_SITE_KEY`.
- Clave secreta solo en entorno de backend mediante `TURNSTILE_SECRET_KEY`.

### Módulo de productos e inventario

- Administración de productos, variantes, tallas, colores, imágenes y estados.
- Control de stock por variante.
- Alertas visuales y administrativas por inventario bajo o agotado.
- Resolución controlada de imágenes y fallback cuando un archivo no existe.

### Módulo de carrito y checkout

- Carrito persistente.
- Flujo de compra con resumen, dirección y comprobante.
- Validación de datos antes de enviar cambios al servidor.
- Estados claros de carga, error y éxito.

### Módulo de pagos

- Transferencia bancaria como método principal.
- Carga y validación administrativa de comprobantes.
- Estados de pago presentados en español y sin valores técnicos crudos.
- Generación de facturas y exportaciones administrativas.

### Módulo de envíos

- Direcciones de usuario.
- Métodos y reglas de envío.
- Seguimiento administrativo del despacho.
- Notificaciones al cliente cuando el pedido cambia de estado logístico.

### Módulo de administración

- Dashboard con métricas operativas.
- Gestión de productos, órdenes, pagos, reembolsos, facturas, envíos, clientes, reseñas, descuentos, anuncios y configuración.
- Componentes reutilizables para tarjetas, filtros, tablas, modales, paginación, estados vacíos, loaders y exportaciones.
- Notificaciones administrativas con polling y eventos en tiempo real.

### Módulo de reportes y análisis

- Exportaciones administrativas en PDF o Excel.
- Reutilización de componentes y composables compartidos.
- Uso del logo y configuración vigente del storefront en salidas documentales.

### Módulo de wishlist y alertas

- Favoritos de cliente.
- Notificaciones o recordatorios asociados a productos relevantes.
- Integración con catálogo y cuenta de usuario.

## Alcance

El alcance actual cubre la operación completa de una tienda virtual de ropa infantil con compra asistida, administración de catálogo, control de inventario, pagos por transferencia, seguimiento de pedidos, notificaciones y reportes.

La migración mantiene compatibilidad con datos existentes cuando aplica, pero cada dominio nuevo consulta su microservicio dueño y su tabla propia. Los textos visibles se mantienen en español, con tildes y copy orientado al usuario final.

No se contempla en esta fase una pasarela de pago automática con tarjeta. La validación financiera se realiza por comprobante y revisión administrativa.

## Estado actual del proyecto

### Avance general: 90% completado

#### Implementado

- Frontend SPA Vue con rutas públicas, cuenta de cliente, checkout y panel administrativo.
- Microservicios Laravel por dominio con PostgreSQL.
- Autenticación nativa, Google, perfil y recuperación de contraseña.
- Cloudflare Turnstile integrado en formularios nativos sensibles.
- Control de intentos fallidos y bloqueo temporal en login nativo.
- Catálogo, carrito, órdenes, pagos, envíos, descuentos, notificaciones, auditoría y reportes.
- Componentes administrativos reutilizables para vistas de operación.
- Documentación de patrones, manual técnico, matriz de requerimientos y guías de operación.

#### En proceso

- Pruebas manuales completas por navegador en desktop, tablet y móvil.
- Afinamiento de rendimiento y reducción progresiva del tamaño de bundles.
- Revisión continua de textos visibles, codificación UTF-8 y consistencia de estados.
- Consolidación de documentación de usuario final.

#### Pendiente

- Despliegue formal en producción.
- Capacitación final de usuarios operativos.
- Plan de mantenimiento posterior al despliegue.
- Validación final de seguridad con variables reales del entorno productivo.

## Indicadores de éxito

### Métricas técnicas

- **Cobertura funcional:** módulos principales implementados y documentados.
- **Disponibilidad objetivo:** 99.9% en entorno productivo.
- **Usuarios simultáneos objetivo:** soporte inicial para 100+ usuarios.
- **Seguridad:** formularios nativos protegidos desde backend y secretos fuera del frontend.
- **Trazabilidad:** operaciones críticas registradas o documentadas por servicio.

### Métricas de negocio

- **Tasa de conversión objetivo:** mayor al 3%.
- **Satisfacción de usuarios:** mayor al 90%.
- **Tiempo promedio de compra:** menor a 5 minutos.
- **Abandono de carrito objetivo:** menor al 30%.

### Métricas de calidad

- **Cumplimiento documental:** README, manual técnico, matriz y patrones actualizados según cambios funcionales.
- **Errores críticos en producción:** 0 como objetivo.
- **Cobertura de pruebas objetivo:** mayor al 80% en flujos críticos.
- **Responsive:** vistas intervenidas verificadas en desktop, tablet y móvil.

## Documentos relacionados

- [README del repositorio](../../README.md)
- [Índice general de documentación](../README.md)
- [Manual técnico](../operaciones/manual-tecnico.md)
- [Matriz de requerimientos funcionales](../referencias/matriz-requerimientos-funcionales-actualizada.md)
- [Patrones de autenticación con verificación de seguridad](../patrones/auth/patrones-diseno-auth-turnstile-2026-06-21.md)
- [Índice de microservicios](../microservicios/README.md)

## Conclusiones

Angelow representa una solución integral de comercio electrónico para ropa infantil con operación administrativa completa y arquitectura moderna. La separación por microservicios mejora la mantenibilidad, permite escalar dominios específicos y facilita la evolución gradual desde la plataforma anterior.

El sistema conserva una experiencia de compra clara para clientes y una operación centralizada para administradores. La protección de formularios nativos con Cloudflare Turnstile, el control de intentos fallidos y el uso de variables de entorno para secretos refuerzan la seguridad sin afectar el flujo de Google.

El proyecto se encuentra en fase de pruebas finales, estabilización operativa y preparación para despliegue, con la documentación base actualizada para sostener mantenimiento y capacitación.

## Contacto

**Proyecto Angelow - SENA**  
Centro Textil y de Gestión Industrial  
Ficha: 3147208  
Programa: Análisis y Desarrollo de Software (ADSO)

**Instructor titular:** Edilfredo Pineda

**Equipo de desarrollo:**

- Braian Andrés Oquendo Durango, líder técnico.
- Sara Hernández Higuita.
- Julian David Cardenas Obando.
- Sofía Guisao Álvarez.

---

Este documento refleja el estado actual del proyecto Angelow, sus alcances, limitaciones y resultados obtenidos hasta la fecha. Se actualiza conforme avanza el desarrollo y debe mantenerse alineado con la matriz de requerimientos, el manual técnico y la documentación de patrones.
