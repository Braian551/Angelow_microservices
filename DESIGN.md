# DESIGN.md — Arquitectura de Angelow

## 1. Objetivo del sistema

Migrar Angelow legacy en PHP hacia clientes Vue y Flutter respaldados por APIs Laravel por dominio, conservando la experiencia, la lógica funcional y la consistencia de datos durante la transición.

Este archivo contiene decisiones estables de arquitectura. Los procedimientos de implementación, validación y documentación viven en las skills de `.agents/skills/`.

## 2. Límites de dominio

| Componente | Responsabilidad principal | Propiedad de datos |
|---|---|---|
| `frontend` | Orquestar la experiencia SPA y consumir APIs por dominio | No es dueño de datos de negocio |
| `mobile/repartidor` | Experiencia móvil del repartidor, permisos de ubicación y navegación | No es dueño de datos de negocio; conserva solo la sesión segura |
| `auth-service` | Inicio de sesión, registro, perfil y recuperación de contraseña | Identidad, credenciales y rol global |
| `catalog-service` | Productos, categorías, colecciones y favoritos | Catálogo y favoritos |
| `order-service` | Pedidos y estados | Pedidos |
| `shipping-service` | Direcciones, postulaciones de repartidor y ciclo operativo de entregas | Direcciones, perfiles operativos, documentos, vehículos, asignaciones y ubicaciones de entrega |
| `notification-service` | Notificaciones y preferencias | Notificaciones |

Reglas arquitectónicas:

- Cada dominio consulta su propio servicio y sus tablas.
- No asumir una base de datos monolítica ni consultar tablas de otro servicio de forma directa.
- Durante la migración se permite fallback controlado a legacy cuando la entidad todavía no esté sincronizada.
- Preservar contratos públicos, rutas y payloads salvo que la tarea requiera explícitamente cambiarlos.
- Cuando sea necesario resolver identidad distribuida, enviar `user_id` y `user_email` mientras dure la coexistencia.
- `order-service` conserva la propiedad del pedido y su estado; al aprobarse el pago publica la elegibilidad de entrega a `shipping-service` mediante un contrato interno idempotente. El contrato conserva la instantánea legible del método y su tiempo estimado para poder publicar pedidos creados durante una indisponibilidad del catálogo distribuido, aun cuando no exista un identificador resoluble.
- La aceptación de una entrega se serializa en `shipping-service`: una asignación solo puede pertenecer a un repartidor y el cambio de pedido a enviado debe confirmarse o la asignación se revierte.
- La ubicación del repartidor solo se persiste y expone al cliente durante una ruta activa y con consentimiento explícito. El código de entrega se almacena cifrado y con hash, y se elimina al finalizar.
- `shipping-service` conserva la configuración de Mapbox y la entrega sin caché solo a repartidores autenticados, aprobados y activos. La aplicación móvil no incorpora el token en tiempo de compilación; lo mantiene únicamente en memoria durante la sesión para inicializar el SDK nativo.
- La aceptación solicita a `notification-service` tanto la notificación como el correo del código, reutilizando su plantilla global. La indisponibilidad del canal se registra, pero no libera una asignación que ya fue confirmada en pedidos.
- Los catálogos externos de vehículos y colores son ayudas de captura; la decisión operativa se persiste por nombre e identificador para no depender de su disponibilidad futura.
- La vinculación del repartidor persiste solo identidad operativa, vehículo, documentos aplicables y aceptación versionada de términos. La operación está limitada a Medellín, por lo que `courier_profiles` no duplica ciudad, contactos de emergencia ni afiliaciones laborales.

## 3. Arquitectura frontend

- Las páginas de `modules/**/pages` son contenedores y orquestadores.
- La lógica reactiva, validaciones, carga de datos, procesamiento de imágenes y payloads complejos se extraen a composables del módulo.
- La lógica compartida entre módulos se ubica en `frontend/src/composables`, `frontend/src/utils` o `frontend/src/services`, según su responsabilidad.
- Los bloques visuales relevantes se separan en componentes hijos.
- Los estilos globales y componentes compartidos tienen prioridad sobre CSS local.
- Los estilos extensos se mueven a archivos CSS del módulo; los selectores deben conservar el aislamiento que antes aportaba `scoped`.
- La navegación del dashboard, header y aside debe ser SPA real, sin recargas completas ni pérdida de estado.

## 4. Experiencia y presentación

- Las pantallas migradas mantienen paridad visual y funcional con legacy antes de introducir mejoras.
- La interfaz pública no revela términos internos como `legacy`, `microservice`, rutas físicas, mounts o detalles de almacenamiento.
- Estados, métodos, motivos, slugs y códigos técnicos pasan por una capa de presentación antes de mostrarse.
- El admin usa superficies suaves, bordes ligeros, radios consistentes y glass sutil; no usa degradados salvo paridad comprobada con legacy.
- Todo cambio visual debe funcionar en desktop, tablet y móvil.

## 5. Archivos y almacenamiento

- `/uploads` es la ruta pública prioritaria para archivos administrados.
- La base de datos debe persistir la ruta relativa real, no solo el nombre original.
- El reemplazo de imágenes elimina de forma segura el archivo anterior cuando pertenece al dominio controlado.
- Antes de declarar un archivo ausente se prueban rutas candidatas razonables: ruta persistida, ruta relativa y basename.
- La ausencia de un archivo se presenta con un estado neutral para el usuario.

## 6. Evolución de la arquitectura

Una tarea que cambie límites de dominio, propiedad de datos, contratos compartidos, estructura transversal o estrategia de migración debe:

1. actualizar este archivo;
2. actualizar la documentación funcional aplicable;
3. validar consumidores y productores afectados;
4. evitar duplicar la decisión en varias skills: la skill debe enlazar a `DESIGN.md`.
