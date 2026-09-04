# SERVICIO NACIONAL DE APRENDIZAJE

## SENA

## MANUAL DE INSTALACIÓN

**Estructura base para instalar, configurar y ejecutar el software**

# Angelow Microservices

**Versión:** 0.0

**Aprendiz:** Braian Andrés Oquendo Durango — documento 1023526011

**Instructor titular:** Edilfredo Pineda

**Ficha:** 3147208

**Medellín, agosto de 2026**

> La ficha del proyecto identifica el Centro Textil y de Gestión Industrial. La mención institucional de Regional Antioquia y Medellín de esta portada debe validarse con el instructor antes de aprobar la entrega.

---

# Portadilla

## MANUAL DE INSTALACIÓN

### Angelow Microservices

**Versión 0.0**

**Programa de formación:** Tecnólogo en Análisis y Desarrollo de Software (ADSO)

**Autor:** Braian Andrés Oquendo Durango

**SENA — Regional Antioquia — Medellín**

**Fecha:** 11 de agosto de 2026

---

# Lista de colaboradores

| Participante o entidad | Rol y datos disponibles |
|---|---|
| Braian Andrés Oquendo Durango | Aprendiz; análisis, desarrollo y documentación. Documento 1023526011. |
| Edilfredo Pineda | Instructor titular. |
| Héctor Maya | Instructor relacionado en la ficha del proyecto. |
| Juan David Carvajal | Instructor relacionado en la ficha del proyecto. |
| Pendiente de asignar | Responsable de instalación e infraestructura: nombre, cargo y canal de contacto. |
| Pendiente de asignar | Soporte de primer nivel: nombre, horario y canal de atención. |
| Pendiente de asignar | Cliente o entidad asociada: nombre, responsable y datos de contacto. |

---

# Tabla de contenido

1. [Portada](#servicio-nacional-de-aprendizaje)
2. [Portadilla](#portadilla)
3. [Lista de colaboradores](#lista-de-colaboradores)
4. [Tabla de contenido](#tabla-de-contenido)
5. [Tabla de imágenes](#tabla-de-imágenes)
6. [Introducción](#introducción)
7. [Requerimientos](#requerimientos)
8. [Guía de instalación, desinstalación, publicación o despliegue, configuración y ejecución](#guía-de-instalación-desinstalación-publicación-o-despliegue-configuración-y-ejecución)
9. [Licenciamiento y garantías](#licenciamiento-y-garantías)
10. [Asistencia técnica](#asistencia-técnica)
11. [Apéndices](#apéndices)
12. [Glosario de términos](#glosario-de-términos)
13. [Bibliografía](#bibliografía)
14. [Índice analítico](#índice-analítico)

> Actualizar esta tabla al convertir el documento a un formato paginado, pues Markdown no asigna páginas fijas.

# Tabla de imágenes

No se incluyen capturas ni diagramas numerados en este manual. Los diagramas técnicos están disponibles en [la documentación de arquitectura](../arquitectura/arquitectura-web-plantuml.md), incluido el [diagrama simple de despliegue e instalación](../arquitectura/diagrama-despliegue-instalacion.md). Si se agregan evidencias de instalación, deben numerarse y ocultar datos personales, tokens, contraseñas y direcciones internas.

# Introducción

Este manual permite instalar, configurar, ejecutar y verificar **Angelow Microservices**, plataforma de comercio electrónico para ropa infantil. Cubre el entorno local con Docker Compose, la preparación para pruebas o producción y la aplicación Flutter de repartidores cuando se requiera.

La versión a instalar es **0.0**, identificada como versión de proyecto en desarrollo; debe sustituirse por una versión etiquetada antes de una liberación productiva. Está dirigido a desarrollo, infraestructura, soporte, QA e instructores que requieran reproducir el sistema de forma verificable.

Angelow incluye una SPA Vue 3, nueve APIs Laravel (`auth`, `catalog`, `cart`, `order`, `payment`, `discount`, `shipping`, `notification` y `audit`), PostgreSQL independiente por dominio, Redis, workers, scheduler, gateway WebSocket Node.js y una aplicación Flutter para repartidores. La guía de arquitectura y operación detallada se conserva en [manual técnico](manual-tecnico.md).

# Requerimientos

## Requerimientos de hardware

| Recurso | Mínimo para desarrollo | Recomendado |
|---|---:|---:|
| Procesador | 4 núcleos de 64 bits | 6 núcleos o más |
| Memoria RAM | 8 GB | 16 GB |
| Almacenamiento libre | 20 GB SSD | 40 GB SSD |
| Resolución | 1366 × 768 | 1920 × 1080 |
| Periféricos | Teclado, mouse y acceso a red | Dispositivo Android o emulador para la app de repartidores |

Docker debe disponer de memoria suficiente para nueve PostgreSQL, Redis, APIs, workers, gateway y frontend. En equipos con 8 GB se recomienda no ejecutar el emulador Android simultáneamente o levantar solo los servicios necesarios.

## Requerimientos de software

| Componente | Versión o requisito |
|---|---|
| Sistema operativo | Windows 10/11 de 64 bits, Linux o macOS con Docker compatible |
| Control de código | Git |
| Contenedores | Docker Engine o Docker Desktop con `docker compose` |
| Base de datos | PostgreSQL 17, suministrado por los contenedores |
| Caché y colas | Redis 7, suministrado por el contenedor |
| Frontend | Vue 3 y Vite; Node.js se suministra en el contenedor para la ejecución Docker |
| Backend | Laravel y PHP, suministrados por los contenedores de cada API |
| Tiempo real | Node.js para `realtime-gateway`, suministrado por contenedor |
| Móvil, opcional | Flutter estable con Dart SDK 3.11.5 o compatible, Android SDK API 23+ o Xcode para iOS |

Para desarrollo fuera de Docker, use los archivos de bloqueo existentes (`composer.lock`, `package-lock.json` y `pubspec.lock`) y no sustituya versiones sin prueba previa.

## Requerimientos de red y permisos

| Recurso | Uso local |
|---|---|
| `5173` | SPA Vue |
| `8001` a `8009` | APIs de autenticación, catálogo, carrito, pedidos, pagos, descuentos, envíos, notificaciones y auditoría |
| `8090` | Gateway WebSocket |
| `5433` a `5441` | PostgreSQL por dominio; no se publican en producción |
| `6379` | Redis; no se publica en producción |
| `80` y `443` | Frontend y HTTPS en producción |

Se requiere conectividad a los registros de imágenes Docker y, si se habilitan las integraciones, a Firebase Authentication, Cloudflare Turnstile, SMTP, Mapbox y servicios de mapas. El responsable de infraestructura necesita permiso para ejecutar Docker, acceder al repositorio, administrar DNS/firewall y gestionar secretos. En producción, permita públicamente solo `80` y `443`; mantenga bases de datos, Redis y APIs en la red interna.

# Guía de instalación, desinstalación, publicación o despliegue, configuración y ejecución

## Preparación del ambiente

1. Obtenga una copia identificada del repositorio y confirme la rama o etiqueta que se instalará:

   ```powershell
   git clone <URL_DEL_REPOSITORIO> Angelow_microservices
   Set-Location Angelow_microservices
   git status --short
   docker compose version
   ```

2. Revise que los puertos de la tabla anterior estén libres y que Docker tenga el almacenamiento y la memoria requeridos.
3. Para pruebas o producción, respalde las nueve bases de datos y el directorio `uploads/` antes de actualizar. Conserve el respaldo fuera del host que se va a intervenir.
4. Defina los responsables técnicos y cree secretos nuevos para el ambiente. No copie secretos, archivos `.env` ni datos de usuarios desde desarrollo.
5. Valide la sintaxis de la orquestación antes de iniciar:

   ```powershell
   docker compose config --quiet
   ```

Resultado esperado: el comando termina sin errores y no hay puertos en conflicto.

## Instalación

### Plataforma web y APIs

1. Desde la raíz del proyecto, construya e inicie los contenedores:

   ```powershell
   docker compose up -d --build
   docker compose ps
   ```

2. Aplique las migraciones de cada servicio Laravel. Ejecute cada comando y corrija cualquier error antes de continuar:

   ```powershell
   docker compose exec auth-service php artisan migrate --force
   docker compose exec catalog-service php artisan migrate --force
   docker compose exec cart-service php artisan migrate --force
   docker compose exec order-service php artisan migrate --force
   docker compose exec payment-service php artisan migrate --force
   docker compose exec discount-service php artisan migrate --force
   docker compose exec shipping-service php artisan migrate --force
   docker compose exec notification-service php artisan migrate --force
   docker compose exec audit-service php artisan migrate --force
   ```

3. Cargue datos semilla o importaciones únicamente si el ambiente lo requiere. Use los procedimientos de [importación de datos](../datos/importacion-datos.md); no importe datos de producción en desarrollo.
4. Compruebe que frontend, APIs, workers, schedulers, Redis y gateway estén en estado `Up`:

   ```powershell
   docker compose ps
   docker compose logs --tail=100 frontend auth-service order-service realtime-gateway
   ```

Resultado esperado: la SPA responde en `http://localhost:5173`, las APIs responden en sus puertos y los registros no contienen errores fatales de inicio.

### Aplicación Flutter para repartidores (opcional)

1. Instale Flutter y el SDK Android; conecte un dispositivo autorizado o inicie un emulador.
2. Desde `mobile/repartidor`, instale dependencias y ejecute con URLs alcanzables para el dispositivo:

   ```powershell
   flutter pub get
   flutter run --dart-define=AUTH_API_URL=http://10.0.2.2:8001/api `
     --dart-define=SHIPPING_API_URL=http://10.0.2.2:8007/api `
     --dart-define=MAPBOX_ACCESS_TOKEN=TU_TOKEN_PUBLICO
   ```

`10.0.2.2` funciona solo en el emulador Android; para dispositivo físico use una IP de la red local o un dominio HTTPS. No incorpore tokens privados al repositorio.

## Desinstalación

Detenga el sistema sin borrar datos:

```powershell
docker compose stop
```

Para retirar contenedores y red, conservando volúmenes:

```powershell
docker compose down
```

`docker compose down -v` elimina los volúmenes de PostgreSQL y puede destruir los datos del ambiente. Solo puede ejecutarlo el responsable de infraestructura después de verificar el respaldo y recibir autorización explícita. No elimine `uploads/`, respaldos, certificados ni archivos de configuración de producción como parte de una desinstalación normal.

## Publicación o despliegue

1. Cree o actualice una rama o etiqueta de liberación y registre versión, responsable, fecha y cambios.
2. En el servidor, haga respaldo de bases y `uploads/`; verifique DNS, firewall, Docker, Compose, Nginx y Certbot.
3. Copie el código de la versión aprobada y cree las variables de producción fuera del repositorio.
4. Construya con el archivo de producción:

   ```bash
   docker compose -f docker-compose.yml -f docker-compose.production.yml up -d --build
   docker compose -f docker-compose.yml -f docker-compose.production.yml ps
   ```

5. Ejecute las migraciones con copia de seguridad validada y compruebe el flujo de inicio de sesión, catálogo, carrito, pedido, carga de comprobante, notificaciones y archivos por HTTPS.
6. Asegure que solo se expongan `80` y `443`; el archivo de producción elimina los puertos directos de APIs, PostgreSQL y Redis.

### Reversión

Si falla una validación crítica, detenga la nueva versión, restituya la etiqueta o imagen anterior, restaure la configuración y, si corresponde, restaure las bases y `uploads/` desde el respaldo validado. No ejecute una reversión de datos sin autorización del responsable de datos. Documente el incidente, versión, hora y resultado.

Para el detalle del proxy, certificados y rutas públicas, consulte [despliegue con Nginx](DESPLIEGUE_SERVIDOR_NGINX.md).

## Configuración

La plantilla global [`.env.example`](../../.env.example) contiene las variables interpoladas por Docker Compose. Los ejemplos específicos están en `frontend/.env.example` y `services/*/.env.example`; para un entorno fuera de los valores provistos por Docker, copie cada ejemplo a `.env` en el mismo componente y configure solo secretos del ambiente:

| Área | Variables principales |
|---|---|
| Frontend | `VITE_*_API_URL`, `VITE_UPLOADS_BASE_URL`, Firebase y Turnstile públicos |
| APIs Laravel | `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_*`, `REDIS_*`, `MAIL_*` |
| Comunicación interna | `FRONTEND_URL`, `INTERNAL_API_TOKEN`, URL de servicios entre dominios |
| Inventario y tiempo real | Parámetros `ORDER_STOCK_*`, canal WebSocket y URL del gateway |
| Móvil | `AUTH_API_URL`, `SHIPPING_API_URL` y token público de Mapbox mediante `--dart-define` |

Use secretos únicos, `APP_DEBUG=false` en producción y URLs HTTPS públicas en todas las variables `VITE_*`. El archivo `docker-compose.production.yml` admite las variables de compilación del frontend y aplica el dominio público. Nunca publique `.env`, claves privadas, contraseñas SMTP, tokens internos ni respaldos.

## Ejecución y verificación

Inicie el ambiente cuando esté detenido:

```powershell
docker compose up -d
docker compose ps
```

Compruebe:

1. La SPA en `http://localhost:5173` y la carga de archivos de prueba sin datos reales.
2. Salud y registros de las APIs; use `docker compose logs --tail=100 <servicio>` ante un error.
3. Una migración aplicada en cada base y procesos `order-worker`, `order-scheduler`, `notification-worker` y `catalog-scheduler` activos cuando el flujo los exige.
4. Inicio de sesión, catálogo, carrito, creación controlada de pedido y actualización de inventario con cuentas sintéticas.
5. En producción: HTTPS, ausencia de llamadas a `localhost`, acceso no público a Redis/bases/APIs y renovación de certificados.

El resultado esperado es un ambiente funcional sin errores fatales, con la SPA y los servicios requeridos accesibles exclusivamente por sus rutas autorizadas.

# Licenciamiento y garantías

No se identificó un archivo de licencia de la aplicación en el repositorio. La entidad propietaria debe definir por escrito los derechos de uso, redistribución, garantía, vigencia y responsabilidades antes de una entrega externa. Mientras no exista ese documento, Angelow debe tratarse como proyecto formativo y no como software con garantía comercial o acuerdo de niveles de servicio.

Las dependencias de Laravel, Vue, Vite, PostgreSQL, Redis, Flutter y demás paquetes conservan sus propias licencias. Antes de distribuir se deben revisar los archivos de bloqueo y licencias de cada dependencia. La garantía de infraestructura depende de los acuerdos del cliente, el proveedor de alojamiento y el responsable de instalación; no se infiere de este manual.

# Asistencia técnica

| Nivel | Responsable | Canal, horario y tiempo de respuesta |
|---|---|---|
| Nivel 1 | Pendiente de asignar | Recepción de incidencias, evidencia y validaciones básicas. Definir correo/teléfono, horario y tiempo de respuesta. |
| Nivel 2 | Responsable de infraestructura y desarrollo | Diagnóstico de contenedores, configuración, bases de datos e integraciones. Datos pendientes de asignar. |
| Nivel 3 | Líder técnico o proveedor externo | Seguridad, pérdida de datos, pagos, indisponibilidad general o servicios de terceros. Datos pendientes de asignar. |

Registre cada caso con fecha, versión, ambiente, usuario anonimizado, pasos para reproducir y registros pertinentes sin secretos. Escale inmediatamente los incidentes de seguridad, pagos, pérdida de datos o caída general. La vigencia del soporte queda pendiente de acuerdo con el cliente o entidad asociada.

# Apéndices

## Apéndice A. Inventario de componentes

| Componente | Puerto local | Función |
|---|---:|---|
| frontend | 5173 | SPA pública, cuenta y administración |
| auth-service a audit-service | 8001 a 8009 | APIs por dominio |
| realtime-gateway | 8090 | Eventos WebSocket de inventario |
| auth-db a audit-db | 5433 a 5441 | PostgreSQL por dominio |
| redis | 6379 | Caché, colas, bloqueos y Pub/Sub |

## Apéndice B. Comprobaciones rápidas

```powershell
docker compose config --quiet
docker compose ps
docker compose logs --tail=100 order-service
docker compose exec order-service php artisan migrate:status
```

## Apéndice C. Recuperación

Conserve un respaldo verificable de cada base y de `uploads/` antes de una actualización. Para recuperar, restaure primero la versión de aplicación compatible y luego datos solo bajo autorización; valide las migraciones, permisos, archivos, workers y flujos críticos antes de abrir el servicio a usuarios.

# Glosario de términos

| Término | Definición |
|---|---|
| API | Interfaz HTTP que permite comunicación entre aplicaciones. |
| Compose | Herramienta y archivo para ejecutar varios contenedores Docker. |
| Contenedor | Unidad aislada que ejecuta una aplicación con sus dependencias. |
| Despliegue | Publicación de una versión en pruebas o producción. |
| Migración | Cambio versionado de la estructura de una base de datos. |
| Microservicio | Aplicación autónoma propietaria de un dominio funcional y sus datos. |
| Proxy inverso | Servicio que recibe tráfico público y lo dirige a componentes internos. |
| Redis | Almacén en memoria usado para caché, colas, bloqueos y eventos. |
| Rollback | Reversión controlada a una versión o estado anterior. |
| SPA | Aplicación web que navega dinámicamente sin recargar la página completa. |
| Worker | Proceso que ejecuta trabajos en segundo plano. |

# Bibliografía

- Angelow. [README principal](../../README.md).
- Angelow. [Manual técnico](manual-tecnico.md).
- Angelow. [Guía de despliegue en servidor con Nginx](DESPLIEGUE_SERVIDOR_NGINX.md).
- Angelow. [Ficha del proyecto](../proyecto/FICHA_PROYECTO_ANGELOW.md).
- Angelow. [Importación de datos](../datos/importacion-datos.md).
- Angelow. [Documentación de arquitectura](../arquitectura/arquitectura-web-plantuml.md).
- Angelow. [Diagrama simple de despliegue e instalación](../arquitectura/diagrama-despliegue-instalacion.md).
- Angelow. [Plantilla global de entorno](../../.env.example).
- Servicio Nacional de Aprendizaje — SENA. [Laboratorio de software](https://repositorio.sena.edu.co/handle/11404/7846), 2022. Referencia institucional consultada el 11 de agosto de 2026.
- Docker. [Documentación de Docker Compose](https://docs.docker.com/compose/).
- Laravel. [Documentación oficial](https://laravel.com/docs/).
- Vue.js. [Documentación oficial](https://vuejs.org/guide/introduction.html).
- Flutter. [Documentación oficial](https://docs.flutter.dev/).

# Índice analítico

| Término o comando | Ubicación |
|---|---|
| `.env.example` | Configuración |
| `docker compose config --quiet` | Preparación del ambiente; Apéndice B |
| `docker compose down` | Desinstalación |
| `docker compose down -v` | Desinstalación (destructivo) |
| `docker compose logs` | Instalación; Ejecución y verificación; Apéndice B |
| `docker compose up -d --build` | Instalación; Publicación o despliegue |
| Flutter | Requerimientos de software; Instalación |
| Migraciones | Instalación; Recuperación |
| PostgreSQL | Requerimientos; Apéndice A |
| Puertos | Requerimientos de red y permisos; Apéndice A |
| Redis | Requerimientos; Apéndice A |
| Reversión | Publicación o despliegue |
| `uploads/` | Preparación, Desinstalación y Recuperación |
