# Importacion de Datos por Microservicio

<!-- indice:auto:start -->
## Índice rápido

- [Fuente](#fuente)
- [Que hace el script](#que-hace-el-script)
- [Dedupe aplicado](#dedupe-aplicado)
- [Ejecucion](#ejecucion)
<!-- indice:auto:end -->

## Fuente

- Archivo: `basededatos.sql`
- Script: `scripts/importar-datos-microservicios.ps1`

## Que hace el script

1. Lee los bloques `COPY public.tabla (...) FROM stdin;` del dump.
2. Distribuye cada tabla al microservicio correcto.
3. Ejecuta `TRUNCATE ... RESTART IDENTITY CASCADE` por tabla.
4. Inserta datos y aplica `setval` de secuencias.
5. Falla de forma explicita si detecta errores de importacion.

## Dedupe aplicado

Para evitar conflictos por claves unicas del esquema migrado:

- `popular_searches`: dedupe por `search_term`.
- `google_auth`: dedupe por `google_id`.

## Ejecucion

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\importar-datos-microservicios.ps1
```
