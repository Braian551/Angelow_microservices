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
- [Pruebas y validación técnica](#pruebas-y-validación-técnica)
- [Mantenimiento de documentación](#mantenimiento-de-documentación)
- [Mapa documental recomendado](#mapa-documental-recomendado)
- [Incidencias frecuentes](#incidencias-frecuentes)
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

Documentos relacionados:

- `docs/datos/importacion-datos.md`
- `docs/datos/migracion-tablas.md`

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
- `docs/microservicios/README.md`: acceso a documentación por servicio.
- `docs/testing/README.md`: ubicación de guías y evidencias de validación transversal.
- `frontend/README.md`: guía local del frontend.
- `frontend/docs/README.md`: flujo y patrones específicos del frontend.

## Incidencias frecuentes

- Si un cambio visual no aparece, repetir el protocolo de caché del frontend antes de seguir depurando.
- Si una vista del dashboard llega sin datos, validar primero la API del dominio dueño y luego la capa Vue.
- Si se crea documentación nueva, ubicarla en la carpeta temática correcta y enlazarla desde el índice correspondiente.