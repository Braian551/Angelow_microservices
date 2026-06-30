# Manual técnico de Angelow Microservices

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Alcance](#alcance)
- [Requisitos previos](#requisitos-previos)
- [Instalación inicial](#instalación-inicial)
- [Servicios y puertos operativos](#servicios-y-puertos-operativos)
- [Migraciones e importación de datos](#migraciones-e-importación-de-datos)
- [Flujo técnico de frontend](#flujo-técnico-de-frontend)
- [Verificación de seguridad en autenticación](#verificación-de-seguridad-en-autenticación)
- [Verificación de correo en registro](#verificación-de-correo-en-registro)
- [Validaciones numéricas de formularios](#validaciones-numéricas-de-formularios)
- [Pruebas y validación técnica](#pruebas-y-validación-técnica)
- [Mantenimiento de documentación](#mantenimiento-de-documentación)
- [Mapa documental recomendado](#mapa-documental-recomendado)
- [Incidencias frecuentes](#incidencias-frecuentes)
- [Operación de reservas de stock](#operación-de-reservas-de-stock)
<!-- indice:auto:end -->

Guía operativa central para instalar, levantar, validar y mantener el repositorio sin perder trazabilidad entre frontend, microservicios y documentación compartida.

## Objetivo

Concentrar en un solo documento los pasos técnicos base de instalación, arranque, pruebas, validación y consulta documental del proyecto.

## Alcance

- Preparación del entorno local.
- Arranque de contenedores y servicios principales.
- Migraciones e importación de datos.
- Validación funcional, pruebas y revisión de logs.
- Navegación hacia la documentación específica por dominio.

## Requisitos previos

- Docker Desktop o Docker Engine con `docker compose` disponible.
- PowerShell para ejecutar scripts `.ps1` del repositorio.
- Puertos locales libres para frontend, APIs y PostgreSQL publicados en `docker-compose.yml`.
- Archivos `.env` requeridos por cada servicio configurados a partir de sus ejemplos cuando aplique.

## Instalación inicial

1. Instalar Docker y verificar que `docker compose version` responda sin error.
2. Revisar el índice raíz del repositorio en `README.md` y la documentación compartida en `docs/README.md`.
3. Configurar variables de entorno necesarias usando los archivos de ejemplo de cada servicio y del frontend.
4. Levantar la plataforma base:

```bash
docker compose up -d --build
docker compose ps
```

## Servicios y puertos operativos

La tabla principal de puertos se mantiene en `README.md`. Si necesitas detalle por dominio, consulta además `docs/microservicios/README.md` y el `README.md` de cada servicio en `services/*/`.

## Migraciones e importación de datos

Para ejecutar migraciones por servicio:

```bash
docker compose exec -T auth-service php artisan migrate --force
docker compose exec -T catalog-service php artisan migrate --force
docker compose exec -T cart-service php artisan migrate --force
docker compose exec -T order-service php artisan migrate --force
docker compose exec -T payment-service php artisan migrate --force
docker compose exec -T discount-service php artisan migrate --force
docker compose exec -T shipping-service php artisan migrate --force
docker compose exec -T notification-service php artisan migrate --force
docker compose exec -T audit-service php artisan migrate --force
```

Para importar datos legacy distribuidos:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\importar-datos-microservicios.ps1
```

El importador lee `basededatos.sql` con detección estricta de codificación para preservar UTF-8 real. Si el dump viene de una herramienta antigua, usa fallback Windows-1252 y vuelve a enviar SQL temporal en UTF-8 sin BOM antes de ejecutar `psql`.

Documentos relacionados:

- `docs/datos/importacion-datos.md`
- `docs/datos/migracion-tablas.md`
- `docs/datos/estructura-unificada-microservicios.sql`
- `docs/datos/rendimiento-bd-microservicios.md`
- `docs/patrones/datos/patrones-diseno-rendimiento-bd-microservicios-2026-06-29.md`

## Flujo técnico de frontend

Cuando haya cambios visuales o dudas de caché en el frontend, usar este protocolo:

```bash
docker compose up -d --build frontend
docker compose exec frontend sh -c "rm -rf /app/node_modules/.vite"
docker compose restart frontend
docker compose logs --tail=120 frontend
docker compose ps
docker compose exec frontend sh -c "npm run build"
```

Después del reinicio, validar la ruta impactada en navegador y forzar recarga dura si el cambio no aparece de inmediato.
Si la tarea toca exportaciones administrativas PDF o Excel, validar además una descarga PDF y una descarga Excel sobre alguna vista intervenida y revisar la guía `frontend/docs/exportaciones-admin-reutilizables.md`.

## Verificación de seguridad en autenticación

Los flujos nativos de registro, inicio de sesión condicionado y recuperación de contraseña usan Cloudflare Turnstile. El frontend consume `VITE_TURNSTILE_SITE_KEY` y `auth-service` consume `TURNSTILE_SECRET_KEY` desde el entorno del contenedor o del despliegue.

Variables operativas:

```bash
TURNSTILE_SECRET_KEY=
TURNSTILE_VERIFY_URL=https://challenges.cloudflare.com/turnstile/v0/siteverify
AUTH_LOGIN_CAPTCHA_AFTER_ATTEMPTS=3
AUTH_LOGIN_TEMP_BLOCK_AFTER_ATTEMPTS=8
AUTH_LOGIN_TEMP_BLOCK_MINUTES=15
VITE_TURNSTILE_SITE_KEY=
```

La llave secreta no debe escribirse en Vue, HTML público, documentación ni archivos versionados. Para Docker local, exporta `TURNSTILE_SECRET_KEY` antes de reconstruir `auth-service` o configúrala en el `.env` raíz ignorado por Git. Si todos los formularios con verificación responden `503`, primero valida que `auth-service` tenga la variable cargada antes de revisar el widget del frontend.

Documento relacionado: `docs/patrones/auth/patrones-diseno-auth-turnstile-2026-06-21.md`.

## Verificación de correo en registro

El registro nativo confirma el correo antes del paso de teléfono. `auth-service` expone `/api/auth/registration-verification/request-code`, `/resend-code` y `/verify-code`; la creación de cuenta consume `registration_token` junto con el formulario final.

Variables operativas:

```bash
REGISTRATION_VERIFICATION_CODE_TTL=900
REGISTRATION_VERIFICATION_RESEND_COOLDOWN=60
PHPMAILER_HOST=smtp.gmail.com
PHPMAILER_PORT=587
PHPMAILER_USERNAME=
PHPMAILER_PASSWORD=
PHPMAILER_ENCRYPTION=tls
```

Documento relacionado: `docs/patrones/auth/patrones-diseno-auth-codigo-registro-recuperacion-2026-06-22.md`.

## Validaciones numéricas de formularios

Las cantidades físicas, stock, inventario y unidades de carrito deben validarse como enteros positivos mayores o iguales a `1` tanto en frontend como en backend. Los precios COP deben persistirse como enteros sin centavos; el punto se acepta únicamente como separador de miles visual, por ejemplo `$68.799`, y se normaliza a `68799` antes de guardar.

El frontend centraliza estas reglas en `frontend/src/utils/numericValidation.js`. Las APIs de `catalog-service` y `cart-service` deben repetir la validación para rechazar decimales, ceros, negativos, letras y símbolos inválidos aunque la solicitud no venga desde la SPA.

Documento relacionado: `docs/patrones/admin/patrones-diseno-validaciones-numericas-2026-06-10.md`.

## Pruebas y validación técnica

Pruebas backend por servicio:

```bash
docker compose exec -T auth-service php artisan test
docker compose exec -T catalog-service php artisan test
docker compose exec -T cart-service php artisan test
docker compose exec -T order-service php artisan test
docker compose exec -T payment-service php artisan test
docker compose exec -T discount-service php artisan test
docker compose exec -T shipping-service php artisan test
docker compose exec -T notification-service php artisan test
docker compose exec -T audit-service php artisan test
```

Validaciones mínimas de cierre:

- `docker compose ps` con contenedores relevantes en estado `Up`.
- `docker compose logs --tail=120 <servicio>` sin error fatal de arranque.
- `docker compose exec frontend sh -c "npm run build"` cuando hubo cambios en frontend.
- Revisión funcional de la ruta impactada en desktop, tablet y móvil cuando hubo cambios de UI.

## Mantenimiento de documentación

- `README.md` y `docs/README.md` son puntos de entrada obligatorios del repositorio.
- Este manual debe actualizarse cuando cambien instalación, arranque, pruebas, flujos de validación o rutas documentales clave.
- Los nombres de archivo Markdown de documentación deben mantenerse en ASCII seguro sin tildes ni `ñ`.
- El contenido interno de la documentación y los comentarios del código sí debe mantenerse en español con UTF-8 real.

## Mapa documental recomendado

- `README.md`: visión general del repositorio, puertos y comandos base.
- `docs/README.md`: índice compartido por categorías.
- `docs/arquitectura/diagramas-clases-microservicios-plantuml.md`: índice de diagramas de clases separados por microservicio, sin capas frontend mezcladas.
- `docs/arquitectura/modelos-relacionales-bases-datos-plantuml.md`: índice de modelos relacionales separados por base de datos y microservicio.
- `docs/arquitectura/modelo-relacional-completo-plantuml.md`: mapa maestro con todas las tablas de negocio y relaciones internas o lógicas.
- `docs/datos/estructura-unificada-microservicios.sql`: estructura SQL de referencia para visualizar cómo quedaría una única base de datos con tablas y relaciones de todos los microservicios.
- `docs/datos/rendimiento-bd-microservicios.md`: detalle operativo de vistas, funciones e índices agregados para mejorar tiempos de lectura.
- `docs/patrones/datos/patrones-diseno-rendimiento-bd-microservicios-2026-06-29.md`: patrones usados para activar optimizaciones con respaldo seguro.
- `docs/microservicios/README.md`: acceso a documentación por servicio.
- `docs/referencias/historias-usuario-angelow.md`: historias de usuario completas por épica, actor y criterios en lenguaje funcional.
- `docs/referencias/casos-uso-angelow.md`: casos de uso completos del sistema en lenguaje entendible para cliente.
- `docs/referencias/matriz-requerimientos-funcionales-actualizada.md`: matriz funcional actualizada por módulo, subproceso, regla del negocio y requisito de información.
- `docs/testing/README.md`: ubicación de guías y evidencias de validación transversal.
- `frontend/README.md`: guía local del frontend.
- `frontend/docs/README.md`: flujo y patrones específicos del frontend.
- `frontend/docs/exportaciones-admin-reutilizables.md`: contrato técnico compartido para exportaciones admin.

## Incidencias frecuentes

- Si un cambio visual no aparece, repetir el protocolo de caché del frontend antes de seguir depurando.
- Si una vista del dashboard llega sin datos, validar primero la API del dominio dueño y luego la capa Vue.
- Si se crea documentación nueva, ubicarla en la carpeta temática correcta y enlazarla desde el índice correspondiente.

## Operación de reservas de stock

Las reservas temporales de inventario de `order-service` usan `ORDER_STOCK_RESERVATION_TTL` como ventana máxima para completar pago y sostener unidades. Cuando el TTL vence, el proceso operativo debe liberar la reserva y cancelar la orden con motivo `reservation_ttl_expired`; no debe quedar un estado final `expired` o `vencido` en órdenes.

Comandos de verificación operativa:

```bash
docker compose exec -T order-service php artisan reservations:reconcile --batch=200
docker compose logs --tail=120 order-worker
docker compose logs --tail=120 order-scheduler
```

El cierre automático publica websocket en el canal configurado por `ORDER_STOCK_WS_CHANNEL`, registra historial y crea notificación para el cliente indicando que puede crear un nuevo pedido si los productos siguen disponibles.
