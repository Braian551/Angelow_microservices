---
name: skill
description: SKILL.md - Angelow Microservicios (Frontend + Backend)
---

## Objetivo
Esta skill define cómo trabajar la migración de Angelow legacy (PHP) a Angelow microservicios (SPA Vue + APIs Laravel), manteniendo diseño, lógica y datos alineados.

## Reglas obligatorias
1. El frontend de microservicios debe verse y comportarse igual que Angelow legacy en las pantallas migradas, incluyendo animaciones y transiciones.
2. Todo texto nuevo debe guardarse en UTF-8 real (sin mojibake).
2b. Toda documentación (`docs/**/*.md`, `README*.md` y archivos equivalentes) y todo comentario nuevo o intervenido en frontend/backend debe escribirse en español con UTF-8 real, usando la grafía correcta (`diseño`, `reseñas`, `configuración`, etc.).
2c. En nombres de archivo de documentación (`docs/**/*.md`, `**/docs/**/*.md`, `README*.md` y equivalentes) se deben usar nombres ASCII seguros sin tildes ni `ñ` (`diseno`, `resenas`, `configuracion`, etc.) para evitar problemas de GitHub; dentro del contenido y en comentarios sí se debe mantener UTF-8 real con ortografía española correcta.
2d. La documentación del repositorio debe vivir organizada por tipo y dominio. En `docs/`, ubicar cada archivo en la carpeta temática correcta (`arquitectura`, `datos`, `operaciones`, `referencias`, `investigacion`, `testing`, `patrones/<modulo>`, etc.) y, cuando un documento pertenezca a un microservicio específico, priorizar la carpeta `docs/` de ese servicio o módulo antes de dejarlo en la raíz compartida.
2e. Toda documentación o README creado/actualizado debe incluir o refrescar un índice navegable con enlaces ancla a sus secciones y, cuando aplique, enlaces a documentos relacionados para que la navegación interna no dependa de búsqueda manual.
2f. `README.md`, `docs/README.md` y `docs/operaciones/manual-tecnico.md` son puntos de entrada obligatorios: si una tarea cambia instalación, pruebas, operación, ubicación documental o flujos base, esos documentos deben revisarse y actualizarse en la misma intervención.
3. La arquitectura es de microservicios: cada dominio consulta su servicio y su tabla propia, con fallback legacy durante migración cuando aplique.
4. En backend se debe usar ORM (Eloquent) como primera opción; usar Query Builder solo cuando sea necesario y documentado.
5. En frontend, navegación tipo SPA real: no recargar header ni aside al cambiar vistas de dashboard.
6. El aside de dashboard también sigue regla SPA (sin refresh completo, sin perder estado de sesión/perfil).
7. Antes de cerrar una tarea, revisar y corregir errores de codificación UTF-8 (incluyendo BOM) en frontend y backend.
8. Si una vista del dashboard no muestra datos, validar primero API del microservicio dueño del dominio (direcciones/notificaciones/pedidos/favoritos) y luego frontend.
9. La UX de estados (éxito/error/info/warning) debe mantener paridad con legacy usando componentes reutilizables tipo snackbar/toast/alerta; no se permiten mensajes dispersos o implementaciones ad hoc por vista.
10. En vistas migradas de dashboard (ejemplo: direcciones), la lógica y el flujo funcional deben replicar legacy antes de introducir mejoras nuevas.
11. Todo cambio frontend/backend debe seguir código limpio: sin código espagueti, con separación de responsabilidades, funciones/métodos pequeños y nombres claros.
11b. Cada vez que se cree o edite un archivo, comentar en español al menos cada función nueva o intervenida, cada bloque condicional y cada proceso no trivial para explicar qué hace y por qué existe.
11c. Cuando una función, helper o flujo reutilice lógica existente, el comentario debe indicar explícitamente qué se está reutilizando y desde qué archivo, vista o módulo proviene esa referencia funcional.
11d. Si en el archivo, componente o flujo intervenido se detecta sospecha razonable de código duplicado, innecesario o que aumente deuda técnica local, se debe corregir en la misma tarea cuando el ajuste sea acotado y seguro; si no es viable resolverlo sin ampliar alcance, se debe documentar explícitamente el hallazgo, el impacto y la siguiente acción recomendada.
11e. Si se detecta un archivo ubicado en una carpeta incorrecta para su responsabilidad, se debe mover a la ubicación correcta siguiendo la arquitectura del proyecto y actualizar imports, referencias, rutas o redirecciones necesarias para no dejar dependencias rotas ni trazabilidad inconsistente.
12. Los elementos repetibles (feedback visual, formularios, tarjetas, modales, tablas, estados vacíos, loaders) deben implementarse como componentes reutilizables, escalables y mantenibles.
12b. Toda exportación administrativa a PDF o Excel debe implementarse sobre componentes/composables reutilizables compartidos; si ya existe un botón, helper o contrato común de exportación, se debe reutilizar o ampliar y no crear otra implementación local por vista.
12c. Las exportaciones administrativas a PDF o Excel deben usar el logo actual del sitio y la configuración vigente compartida del storefront (`useAppShell` o fuente equivalente). Si la vista ya muestra imágenes operativas, el PDF compartido debe soportarlas sin duplicar plantillas por pantalla.
13. Cada cambio del agente debe dejar documentación actualizada del patrón aplicado (o justificar por qué no aplica), tomando como referencia el catálogo de patrones de diseño: https://refactoring.guru/es/design-patterns/catalog.
14. Todo formulario (sin excepción) debe implementar validación en tiempo real por campo (on input/on change), mostrando errores claros y consistentes antes del submit.
15. Al documentar patrones de diseño, se debe indicar explícitamente en qué archivo(s) se aplicó cada patrón (ruta exacta en el proyecto), además del problema que resuelve.
16. Siempre que se agregue, actualice o use una librería (frontend/backend) o dependencia Composer para resolver una tarea, se debe registrar en un archivo .md dentro de docs indicando: nombre/version, motivo, comando usado y ruta exacta donde se aplica.
16b. Esta documentación en docs es requisito obligatorio de cierre: no se puede dar por terminada la tarea si no quedó registrada la librería/dependencia usada.
16c. Cada vez que se toque la infraestructura compartida de exportación PDF/Excel o sus librerías asociadas, en la misma intervención se debe actualizar la guía del frontend correspondiente, el registro de patrones y el registro de dependencias para evitar divergencia documental.
17. Después de cambios en endpoints/controladores, se deben ejecutar pruebas de verificación (endpoint o lógica equivalente) y eliminar al final cualquier archivo temporal de test/debug creado para esa validación.

## Arquitectura funcional (resumen)
- `auth-service`: login, registro, perfil, recuperación de contraseña.
- `catalog-service`: productos, categorías, colecciones, favoritos.
- `order-service`: pedidos y estados.
- `shipping-service`: direcciones y lógica de envío.
- `notification-service`: notificaciones y preferencias.
- `frontend` (Vue): orquesta UX consumiendo APIs por dominio.

## Datos y migración
- Durante la migración, el frontend debe enviar `user_id` y `user_email` cuando sea posible para resolver identidad legacy/distribuida.
- Si una entidad aún no está sincronizada en base distribuida, se permite fallback a legacy.
- Mantener consistencia visual y de conteos (pedidos, direcciones, favoritos, notificaciones) entre Angelow legacy y microservicios.
- Recordar que las tablas están distribuidas por servicio; no asumir que toda la información vive en una sola base.

## Imágenes y avatar
- Priorizar `/uploads`.
- Si no existe archivo, usar `assets/foundnotimages` (fallback por tipo).
- Para avatar legacy, soportar caso donde en BD solo existe nombre de archivo (ejemplo: `abc123.jpg`) y resolver como `uploads/users/abc123.jpg`.

## Flujo operativo obligatorio después de cambios
Siempre que haya cambios en frontend o servicios API:
1. Reconstruir/levantar contenedores necesarios.
2. Validar logs de servicios modificados.
3. Verificar en navegador que los cambios quedaron aplicados.
4. Dejar evidencia en la respuesta final: comando ejecutado, estado `docker compose ps` y resultado de logs.
5. Si el usuario reporta "no veo cambios", repetir build de servicios tocados y validar caché de frontend antes de continuar.
6. Ejecutar verificación de UTF-8 en archivos tocados y corregir de inmediato cualquier texto mojibake (`Ã`, `Â`) o BOM UTF-8.
7. Validar endpoints de datos críticos (direcciones, notificaciones, pedidos, favoritos) antes de cerrar.
8. Si se crean pruebas temporales para validar endpoints, deben borrarse antes de finalizar la tarea.

Comandos base:
- `docker compose up -d --build frontend`
- `docker compose up -d --build auth-service shipping-service catalog-service notification-service`
- `docker compose ps`
- `docker compose logs --tail=120 frontend`
- `docker compose logs --tail=120 auth-service shipping-service catalog-service notification-service`

## Definición de terminado (DoD) de cada tarea
Una tarea NO se considera terminada si falta alguno de estos pasos:
1. Cambios de código aplicados.
2. Docker actualizado con `up -d --build` en servicios afectados.
3. Contenedores en estado `Up` en `docker compose ps`.
4. Logs sin error fatal de arranque.
5. Verificación funcional en la ruta impactada (SPA + diseño/paridad Angelow).
6. Verificación de codificación UTF-8 en archivos modificados (sin BOM en PHP/JS/Vue/CSS y sin textos corruptos).
7. Evidencia de pruebas de endpoints ejecutadas y limpieza de artefactos temporales de test/debug.
9. Verificación de ubicación correcta de archivos creados o movidos, con imports, referencias y enlaces actualizados después de la reorganización.
8. Revisión explícita de duplicación, código innecesario o deuda técnica local en los archivos tocados, con corrección inmediata o documentación del remanente antes de cerrar.

## Git y ramas (humanizado, git flow)
- Crear ramas de trabajo con intención funcional clara.
- Seguir lógica tipo git flow (feature/fix por módulo).
- Commits pequeños, descriptivos y naturales (no genéricos de IA).
- No mezclar en un commit cambios no relacionados.

## Criterio de calidad
- Código limpio, nombres claros, sin duplicación innecesaria.
- Comentarios cortos en español cuando una parte no sea obvia.
- En documentación y comentarios, usar siempre UTF-8 real con ortografía española completa; no dejar transliteraciones ASCII cuando se trate de texto documental.
- Solo los nombres de archivo Markdown de documentación deben mantenerse en ASCII seguro; el contenido interno y los comentarios siguen siendo UTF-8 real en español.
- Preferencia operativa: al modificar archivos, agregar comentarios breves en español en lógica no trivial para facilitar mantenimiento y soporte.
- Al crear o editar archivos, no dejar funciones, condicionales ni procesos nuevos sin explicación; el comentario debe dejar clara la intención técnica y, si hay reutilización, mencionar de dónde viene.
- En cada intervención, revisar duplicación local, ramas muertas, estilos redundantes y acoplamientos innecesarios; resolverlos cuando la corrección sea segura y documentar cualquier deuda técnica remanente.
- Si un archivo o documento no está en la carpeta correcta según su responsabilidad, moverlo y dejar actualizadas todas las referencias necesarias en la misma intervención.
- En `docs/`, evitar archivos sueltos cuando ya exista una carpeta temática adecuada; clasificar por tipo, módulo, dominio o servicio antes de cerrar la tarea.
- Si se crea o actualiza documentación, refrescar el índice navegable del archivo y revisar si `README.md`, `docs/README.md` o `docs/operaciones/manual-tecnico.md` también requieren enlace o contexto adicional.
- Validaciones de formulario y mensajes consistentes con Angelow.
- Validación en tiempo real obligatoria en todos los formularios con feedback inmediato por campo y sin esperar al submit.
- Mantener paridad visual con legacy antes de cerrar una tarea, incluyendo animaciones/microinteracciones cuando existan en la vista original.
- Prohibido mezclar UI, estado y acceso a datos en bloques monolíticos: aplicar estructura modular y reusable.
- Centralizar componentes de feedback (snackbar/toast/alertas) para asegurar consistencia visual y funcional en todo el frontend.
- Documentar en cada tarea qué patrón(es) de diseño se usaron, dónde se aplicaron y qué problema resolvieron, alineado con Refactoring Guru.
- Documentar en docs cada nueva librería/dependencia Composer utilizada, incluyendo archivo(s) y módulo(s) donde quedó aplicada.