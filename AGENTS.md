# AGENTS.md — Angelow

## Propósito

Este archivo es el enrutador obligatorio del repositorio. Mantenerlo corto: decide qué contexto cargar, pero no duplica reglas detalladas de las skills ni decisiones de arquitectura de `DESIGN.md`.

## Protocolo inicial obligatorio

1. Leer la solicitud y revisar únicamente los archivos, módulos y servicios relacionados.
2. Invocar siempre `$angelow-core` para cualquier tarea de Angelow.
3. Aplicar la tabla de enrutamiento y cargar solo las skills necesarias; no precargar todas.
4. Leer `DESIGN.md` únicamente cuando la tarea cambie arquitectura, límites de servicio, propiedad de datos, contratos entre módulos o decisiones transversales.
5. Antes de crear código, componentes, helpers, documentos o skills, buscar equivalentes existentes y actualizar o ampliar la fuente canónica.
6. Mantener el cambio acotado. No realizar refactors masivos, migraciones colaterales ni copias de respaldo dentro del repositorio salvo solicitud expresa.
7. Antes de cerrar cualquier cambio de código o configuración ejecutable, invocar `$angelow-delivery`.

## Enrutamiento de skills

| Alcance detectado | Skill obligatoria | Momento |
|---|---|---|
| Cualquier tarea de Angelow | `$angelow-core` | Al iniciar |
| Vue, JavaScript, CSS, SPA, UI, formularios, responsive, admin | `$angelow-frontend` | Antes de editar frontend |
| Laravel, API, controladores, servicios, base de datos, migración de datos | `$angelow-backend` | Antes de editar backend |
| Campos, columnas, payloads o documentos persistidos aparentemente sin uso | `$angelow-data-pruning` | Antes de retirar datos o crear migraciones destructivas |
| Imágenes, avatar, adjuntos, `/uploads`, PDF, Excel, orden visual | `$angelow-files-exports` | Antes de tocar esos flujos |
| Markdown, README, requisitos, patrones, dependencias o índices | `$angelow-documentation` | Antes de editar documentación |
| Docker, build, pruebas, logs, endpoints, caché, DoD o entrega | `$angelow-delivery` | Durante la validación y al cierre |
| `AGENTS.md`, `DESIGN.md`, `.agents/skills` o creación/edición de skills | `$agent-skill-maintainer` | Antes de modificar instrucciones de agentes |

Una tarea multidominio debe cargar la unión mínima de skills aplicables. Ejemplo: un formulario Vue que sube imágenes requiere `$angelow-core`, `$angelow-frontend`, `$angelow-files-exports` y, al cerrar, `$angelow-delivery`.

## Precedencia y fuentes de verdad

1. Instrucciones del sistema y solicitud explícita del usuario.
2. `AGENTS.override.md` o `AGENTS.md` más cercano al directorio de trabajo.
3. Este `AGENTS.md` raíz.
4. Skills activadas para la tarea.
5. `DESIGN.md` y referencias específicas.
6. Código, configuración y documentación vigente del módulo, que deben inspeccionarse antes de asumir rutas o comandos.

Cuando dos reglas del mismo nivel entren en conflicto, aplicar la más específica al archivo o dominio tocado y documentar la decisión en la respuesta final.

## Contrato de cierre

La respuesta final debe indicar, de forma breve:

- qué se cambió;
- qué validaciones se ejecutaron y su resultado;
- qué no pudo verificarse y por qué;
- cualquier deuda técnica real que permanezca, sin crear archivos de respaldo o documentos innecesarios.
