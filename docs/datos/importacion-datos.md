# Importación de Datos por Microservicio

<!-- indice:auto:start -->
## Índice rápido

- [Fuente](#fuente)
- [Qué hace el script](#qué-hace-el-script)
- [Codificación UTF-8](#codificación-utf-8)
- [Dedupe aplicado](#dedupe-aplicado)
- [Ejecución](#ejecución)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Fuente

- Archivo: `basededatos.sql`
- Script: `scripts/importar-datos-microservicios.ps1`

## Qué hace el script

1. Lee los bloques `COPY public.tabla (...) FROM stdin;` del dump.
2. Distribuye cada tabla al microservicio correcto.
3. Ejecuta `TRUNCATE ... RESTART IDENTITY CASCADE` por tabla.
4. Inserta datos y aplica `setval` de secuencias.
5. Falla de forma explícita si detecta errores de importación.

## Codificación UTF-8

El script lee el dump como bytes para evitar que Windows PowerShell convierta caracteres UTF-8 a ANSI. La detección prioriza UTF-8 estricto, acepta BOM si existe y usa Windows-1252 como fallback para dumps exportados desde herramientas antiguas.

Los SQL temporales enviados a `psql` se escriben como UTF-8 sin BOM y declaran `SET client_encoding = 'UTF8';`, conservando valores visibles como `Bogotá`, `Itaú`, `Bancamía`, `niñas`, `configuración` y otros textos con tildes o `ñ`.

## Dedupe aplicado

Para evitar conflictos por claves únicas del esquema migrado:

- `popular_searches`: dedupe por `search_term`.
- `google_auth`: dedupe por `google_id`.

## Ejecución

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\importar-datos-microservicios.ps1
```

## Documentos relacionados

- [Manual técnico](../operaciones/manual-tecnico.md)
- [Migración de tablas](migracion-tablas.md)
