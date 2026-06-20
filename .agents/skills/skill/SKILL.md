---
name: skill
description: SKILL.md - Angelow Microservicios (Frontend + Backend)
---

## Objetivo
Esta skill define cómo trabajar la migración de Angelow legacy (PHP) a Angelow microservicios (SPA Vue + APIs Laravel), manteniendo diseño, lógica y datos alineados.

## Reglas obligatorias
1. El frontend de microservicios debe verse y comportarse igual que Angelow legacy en las pantallas migradas, incluyendo animaciones y transiciones.
2. Todo texto nuevo debe guardarse en UTF-8 real (sin mojibake) y con ortografía correcta en español, incluyendo tildes, `ñ`, signos y acentos en labels, placeholders, botones, breadcrumbs, modales, snackbars, tablas y exportables visibles.
2b. Toda documentación (`docs/**/*.md`, `README*.md` y archivos equivalentes) y todo comentario nuevo o intervenido en frontend/backend debe escribirse en español con UTF-8 real, usando la grafía correcta (`diseño`, `reseñas`, `configuración`, etc.).
2c. En nombres de archivo de documentación (`docs/**/*.md`, `**/docs/**/*.md`, `README*.md` y equivalentes) se deben usar nombres ASCII seguros sin tildes ni `ñ` (`diseno`, `resenas`, `configuracion`, etc.) para evitar problemas de GitHub; dentro del contenido y en comentarios sí se debe mantener UTF-8 real con ortografía española correcta.
2d. La documentación del repositorio debe vivir organizada por tipo y dominio. En `docs/`, ubicar cada archivo en la carpeta temática correcta (`arquitectura`, `datos`, `operaciones`, `referencias`, `investigacion`, `testing`, `patrones/<modulo>`, etc.) y, cuando un documento pertenezca a un microservicio específico, priorizar la carpeta `docs/` de ese servicio o módulo antes de dejarlo en la raíz compartida.
2e. Toda documentación o README creado/actualizado debe incluir o refrescar un índice navegable con enlaces ancla a sus secciones y, cuando aplique, enlaces a documentos relacionados para que la navegación interna no dependa de búsqueda manual.
2f. `README.md`, `docs/README.md` y `docs/operaciones/manual-tecnico.md` son puntos de entrada obligatorios: si una tarea cambia instalación, pruebas, operación, ubicación documental o flujos base, esos documentos deben revisarse y actualizarse en la misma intervención.
3. La arquitectura es de microservicios: cada dominio consulta su servicio y su tabla propia, con fallback legacy durante migración cuando aplique.
4. En UI, labels, subtítulos, placeholders, alertas, tooltips, textos auxiliares y copy operativo NO se debe exponer el contexto interno de implementación; evitar menciones explícitas a legacy, microservicios o Angelow salvo que la tarea lo pida de forma expresa.
5. En frontend se deben priorizar los estilos globales y componentes compartidos del dashboard para grids, tarjetas, formularios, filtros, tablas y modales; dejar estilos locales solo para variaciones realmente específicas de la vista.
6. En backend se debe usar ORM (Eloquent) como primera opción; usar Query Builder solo cuando sea necesario y documentado.
7. En frontend, navegación tipo SPA real: no recargar header ni aside al cambiar vistas de dashboard.
8. El aside de dashboard también sigue regla SPA (sin refresh completo, sin perder estado de sesión/perfil).
9. Antes de cerrar una tarea, revisar y corregir errores de codificación UTF-8 (incluyendo BOM) en frontend y backend, y hacer barrido explícito de textos visibles para detectar tildes faltantes, `ñ` omitida, copy incompleto o palabras mal escritas.
10. Si una vista del dashboard no muestra datos, validar primero API del microservicio dueño del dominio (direcciones/notificaciones/pedidos/favoritos) y luego frontend.
11. La UX de estados (éxito/error/info/warning) debe mantener paridad con legacy usando componentes reutilizables tipo snackbar/toast/alerta; no se permiten mensajes dispersos o implementaciones ad hoc por vista.
12. En vistas migradas de dashboard (ejemplo: direcciones), la lógica y el flujo funcional deben replicar legacy antes de introducir mejoras nuevas.
13. Todo cambio frontend/backend debe seguir código limpio: sin código espagueti, con separación de responsabilidades, funciones/métodos pequeños y nombres claros.
13b. Cada vez que se edite un archivo, si el archivo intervenido contiene comentarios o documentación interna en inglés, se deben traducir al español en esa misma intervención, manteniendo precisión técnica y contexto para facilitar mantenimiento del equipo.
13c. Cada vez que se cree o edite un archivo, comentar en español al menos cada función nueva o intervenida, cada bloque condicional y cada proceso no trivial para explicar qué hace y por qué existe.
13d. Cuando una función, helper o flujo reutilice lógica existente, el comentario debe indicar explícitamente qué se está reutilizando y desde qué archivo, vista o módulo proviene esa referencia funcional.
13e. Si en el archivo, componente o flujo intervenido se detecta sospecha razonable de código duplicado, innecesario o que aumente deuda técnica local, se debe corregir en la misma tarea cuando el ajuste sea acotado y seguro; si no es viable resolverlo sin ampliar alcance, se debe documentar explícitamente el hallazgo, el impacto y la siguiente acción recomendada.
13f. Si se detecta un archivo ubicado en una carpeta incorrecta para su responsabilidad, se debe mover a la ubicación correcta siguiendo la arquitectura del proyecto y actualizar imports, referencias, rutas o redirecciones necesarias para no dejar dependencias rotas ni trazabilidad inconsistente.
14. Los elementos repetibles (feedback visual, formularios, tarjetas, modales, tablas, estados vacíos, loaders) deben implementarse como componentes reutilizables, escalables y mantenibles.
14b. En ningún caso se deben reimplementar ad hoc en una vista componentes ya existentes en el sistema (`AdminStatsGrid`, `AdminCard`, `AdminPageHeader`, `AdminFilterCard`, `AdminResultsBar`, `AdminPagination`, `AdminModal`, `AdminEmptyState`, `AdminShimmer`, `AdminTableShimmer`, `AdminTableImage`). Toda vista nueva o mejorada debe consumir estos componentes directamente. Si un componente existente no cubre el caso de uso, se debe ampliar o parametrizar ese componente, nunca duplicarlo.
14c. Todo componente reutilizable del módulo admin debe ser responsivo por diseño: no se considera terminada la intervención sobre un componente si solo funciona en desktop. Cualquier componente compartido que no sea responsivo en móvil y tablet debe corregirse en la misma tarea donde se detecte el problema, incluso si no era el objetivo principal.
14d. Toda exportación administrativa a PDF o Excel debe salir de componentes/composables reutilizables compartidos. Si ya existen botones, contratos o helpers comunes de exportación, se deben reutilizar o ampliar; queda prohibido volver a crear helpers CSV/PDF aislados por vista para el mismo patrón funcional.
14e. Las exportaciones administrativas a PDF o Excel deben heredar siempre el logo actual del sitio y la configuración vigente compartida del storefront (`useAppShell` o la fuente común equivalente). Si la vista intervenida ya muestra imágenes operativas, el contrato compartido debe permitir incluirlas en PDF sin duplicar plantillas por pantalla.
15. Cada cambio del agente debe dejar documentación actualizada del patrón aplicado (o justificar por qué no aplica), tomando como referencia el catálogo de patrones de diseño: https://refactoring.guru/es/design-patterns/catalog.
15b. Siempre que una tarea agregue, amplíe o modifique una funcionalidad visible o un comportamiento funcional del sistema, en la misma intervención se debe actualizar `docs/referencias/matriz-requerimientos-funcionales-actualizada.md` dentro del módulo o dominio correspondiente.
15c. En `docs/referencias/matriz-requerimientos-funcionales-actualizada.md` las columnas `Nro.` de RF, RN y RI deben permanecer vacías para todas las filas nuevas o intervenidas; no se deben crear ni reutilizar numeraciones tipo `RF-###`, `RN-###` o `RI-###`.
16. Todo formulario (sin excepción) debe implementar validación en tiempo real por campo (on input/on change), mostrando errores claros y consistentes antes del submit.
17. Al documentar patrones de diseño, se debe indicar explícitamente en qué archivo(s) se aplicó cada patrón (ruta exacta en el proyecto), además del problema que resuelve.
18. Siempre que se agregue, actualice o use una librería (frontend/backend) o dependencia Composer para resolver una tarea, se debe registrar en un archivo .md dentro de docs indicando: nombre/version, motivo, comando usado y ruta exacta donde se aplica.
18b. Esta documentación de librerías/dependencias en docs es bloqueante: la tarea no puede marcarse como terminada si falta ese registro, aunque el código ya funcione.
18c. Cada vez que se toque infraestructura compartida de exportación PDF/Excel o sus librerías asociadas, en la misma intervención se debe actualizar la guía del frontend correspondiente, el registro de patrones y el registro de dependencias para evitar divergencia documental.
19. Después de cambios en endpoints/controladores, se deben ejecutar pruebas de verificación (endpoint o lógica equivalente) y eliminar al final cualquier archivo temporal de test/debug creado para esa validación.
20. Todo ajuste visual en frontend debe validarse y resolverse de forma responsiva en desktop, tablet y móvil; no se considera terminado si la UI se rompe o se solapa en alguno de esos tamaños.
21. Toda sección, vista, componente o bloque intervenido durante una tarea debe revisarse de forma explícita en sus breakpoints afectados y quedar responsive antes de cerrar el trabajo; no basta con que solo el layout general siga funcionando.
22. Nunca usar colores degradados en la UI de microservicios, salvo que una pantalla legacy específica ya los tenga y la tarea exija replicarlos exactamente.
23. Si las respuestas de las bases de datos (especialmente legacy) traen caracteres corruptos o errores UTF-8 (por ejemplo `Caf??` o `Mel??n`), se deben aplicar correcciones on-the-fly en las respuestas del backend o parseos del frontend para restaurar los formatos correctos (ej. `Café`, `Melón`) garantizando consistencia visual hasta que los esquemas DB de origen sean completamente corregidos en la migración.
24. En el dashboard admin, el lenguaje visual base debe usar colores sólidos pero suaves, superficies tipo glass sutil, bordes ligeros y radios consistentes; evitar botones planos, bloques desproporcionados y contrastes agresivos.
25. La regla visual del punto anterior aplica solo al módulo admin y debe implementarse desde estilos globales/componentes compartidos para mantener coherencia transversal entre todas las vistas administrativas.
26. Todo valor operativo proveniente de base de datos que llegue en inglés o formato técnico (`pending`, `paid`, `transfer`, `producto_defectuoso`, valores con `_`, slugs, códigos de estado, métodos o motivos internos) debe pasar por una capa de presentación antes de renderizarse en frontend. Nunca se deben mostrar crudos los valores de BD; la UI debe usar el término adecuado en español, con espacios, tildes y redacción natural según el contexto.
27. Las rutas, query params, slugs visibles y copy derivado de navegación no deben exponer términos internos de implementación como `legacy`, `microservice` o equivalentes; la URL pública debe mantenerse neutral para el usuario final.
28. Todo archivo servido desde `/uploads` debe resolverse con una URL pública válida y, si la BD apunta a un archivo inexistente, la IU debe mostrar un estado controlado de archivo no disponible en vez de una imagen rota o enlace inválido.
29. Antes de marcar un adjunto como no disponible, backend o frontend deben validar rutas candidatas razonables a partir del valor persistido (ruta completa, relativa, basename) para evitar falsos negativos por datos legacy o nombres parciales.
30. Cuando un adjunto no pueda mostrarse, la IU debe usar copy neutro orientado al usuario y nunca mencionar carpetas, rutas físicas, contenedores, mounts ni detalles internos de almacenamiento.
31. En cualquier flujo de imágenes del admin (settings, sliders, categorías, colecciones, productos, anuncios y similares) se debe usar lógica obligatoria de reemplazo: al subir una nueva imagen para el mismo campo/registro, eliminar de forma segura el archivo anterior y persistir solo la ruta vigente para evitar acumulación de basura en `/uploads`.
32. En flujos con orden visual (sliders, listados ordenables, banners, bloques destacados), toda operación de actualización debe preservar un orden consistente y persistido (sin duplicados de posición, sin huecos y con feedback inmediato en la UI).
33. Todo componente, modal, botón de tabla o acción que envíe cambios al servidor debe bloquear doble envío mientras la promesa está en curso, deshabilitar controles repetibles y mostrar estado visible de carga (spinner, texto "Guardando..." o equivalente) hasta recibir respuesta o error.

## Arquitectura funcional (resumen)
- `auth-service`: login, registro, perfil, recuperación de contraseña.
- `catalog-service`: productos, categorías, colecciones, favoritos.
- `order-service`: pedidos y estados.
- `shipping-service`: direcciones y lógica de envío.
- `notification-service`: notificaciones y preferencias.
- `frontend` (Vue): orquesta UX consumiendo APIs por dominio.

## Arquitectura frontend Vue obligatoria
- En Vue se permite usar Single File Components con `<template>`, `<script setup>` y `<style scoped>`, pero si un componente supera una complejidad razonable, debe separarse progresivamente.
- Una página dentro de `modules/**/pages` debe actuar principalmente como contenedor/orquestador, no como archivo gigante con toda la lógica, todos los estilos y todos los subcomponentes embebidos.
- Si una página o componente tiene mucha lógica reactiva, watchers, validaciones, carga de datos, procesamiento de imágenes, generación de payloads o reglas de negocio, esa lógica debe extraerse a composables dentro del módulo correspondiente, por ejemplo:
  - `frontend/src/modules/admin/composables/useAdminProductForm.js`
  - `frontend/src/modules/catalog/composables/useProductDetail.js`
  - `frontend/src/modules/account/composables/useAddresses.js`
- Si una lógica es reutilizable entre módulos, debe ir en `frontend/src/composables`, `frontend/src/utils` o `frontend/src/services`, según corresponda.
- Si una vista tiene más de un bloque visual importante, se debe dividir en componentes hijos dentro de `components` del mismo módulo.
- Los estilos extensos no deben permanecer dentro de `<style scoped>` cuando superen una complejidad razonable. Deben moverse a archivos `.css` externos ubicados en `frontend/src/modules/<modulo>/views`, `frontend/src/modules/<modulo>/styles` o `frontend/src/components/<dominio>`, según la responsabilidad.
- En los `.vue`, importar CSS externo con `import './NombreVista.css'` o usar la convención existente del módulo.
- No duplicar estilos globales ni estilos de componentes compartidos. Antes de crear CSS nuevo, revisar si ya existe una clase, componente o patrón en `frontend/src/modules/admin/styles/admin.css`, `frontend/src/styles/main.css`, `frontend/src/styles/variables.css` o componentes compartidos existentes.
- Los componentes reutilizables del admin deben seguir usando los componentes existentes como `AdminCard`, `AdminPageHeader`, `AdminModal`, `AdminShimmer`, `AdminStatsGrid`, `AdminFilterCard`, `AdminResultsBar`, `AdminPagination`, `AdminEmptyState`, `AdminTableImage`, `AdminTableShimmer`, `AdminToggleSwitch`, entre otros.
- No se permite crear variantes ad hoc de componentes que ya existen. Si falta una capacidad, ampliar el componente compartido de forma compatible.
- Toda separación debe preservar la paridad visual y funcional actual.
- No cambiar nombres de rutas públicas ni navegación SPA.
- No cambiar contratos de API ni payloads sin necesidad.
- No cambiar estructura de datos enviada o recibida si no es parte directa de la tarea.
- No hacer refactor masivo ciego. Se debe migrar por módulos o vistas, validando cada paso.
- Después de mover archivos, actualizar todos los imports, rutas relativas y referencias.
- Después de mover CSS, verificar que no se pierdan estilos por `scoped`, especificidad o cascada.
- Si un estilo dependía de `scoped`, adaptar selectores de forma segura para evitar fugas visuales.
- Cada refactor debe validar desktop, tablet y móvil.
- Cada refactor debe ejecutar `npm run build` o el comando equivalente disponible del frontend.
- Si hay ESLint o análisis configurado, ejecutarlo solo sobre archivos afectados cuando sea posible para evitar gasto innecesario.
- Documentar el patrón aplicado en `docs/patrones/<modulo>/...` indicando archivos exactos movidos o creados.
- Actualizar documentación solo donde aplique, sin crear documentación excesiva ni changelogs innecesarios.

## Datos y migración
- Durante la migración, el frontend debe enviar `user_id` y `user_email` cuando sea posible para resolver identidad legacy/distribuida.
- Si una entidad aún no está sincronizada en base distribuida, se permite fallback a legacy.
- Mantener consistencia visual y de conteos (pedidos, direcciones, favoritos, notificaciones) entre Angelow legacy y microservicios.
- Recordar que las tablas están distribuidas por servicio; no asumir que toda la información vive en una sola base.

## Imágenes y avatar
- Priorizar `/uploads`.
- Si no existe archivo, usar `assets/foundnotimages` (fallback por tipo).
- Para avatar legacy, soportar caso donde en BD solo existe nombre de archivo (ejemplo: `abc123.jpg`) y resolver como `uploads/users/abc123.jpg`.
- Para comprobantes, documentos y adjuntos del admin, resolver siempre la ruta pública real del archivo y validar que el nombre persistido corresponda al archivo físico almacenado.
- Si el valor persistido llega incompleto (por ejemplo solo basename), intentar resolverlo contra rutas candidatas antes de considerarlo faltante.
- Cuando se reemplace una imagen existente, borrar el archivo anterior si pertenece al dominio controlado de uploads y registrar únicamente la nueva ruta para mantener trazabilidad y limpieza.
- Si el usuario elimina una imagen desde el formulario, persistir explícitamente la eliminación y no conservar referencias huérfanas en BD.

## Flujo operativo obligatorio después de cambios
Siempre que haya cambios en frontend o servicios API:
1. Reconstruir/levantar contenedores necesarios.
2. Validar logs de servicios modificados.
3. Verificar en navegador que los cambios quedaron aplicados.
4. Dejar evidencia en la respuesta final: comando ejecutado, estado `docker compose ps` y resultado de logs.
5. Si el usuario reporta "no veo cambios", NO asumir que el build falló: repetir build de servicios tocados, invalidar caché de frontend y validar caché del navegador antes de continuar.
6. Cuando el frontend use Vite/HMR con volumen Docker, el protocolo obligatorio para descartar caché es: `docker compose up -d --build frontend` -> `docker compose exec frontend sh -c "rm -rf /app/node_modules/.vite"` -> `docker compose restart frontend` -> `docker compose logs --tail=120 frontend` -> hard refresh del navegador (`Ctrl+Shift+R`).
7. Si después de ese protocolo el cambio sigue sin verse, verificar dentro del contenedor que el archivo montado contiene el código actualizado antes de seguir depurando UI.
8. Ejecutar verificación de UTF-8 en archivos tocados y corregir de inmediato cualquier texto mojibake (`Ã`, `Â`) o BOM UTF-8.
9. Validar endpoints de datos críticos (direcciones, notificaciones, pedidos, favoritos) antes de cerrar.
10. Si se crean pruebas temporales, scripts de comprobación de base de datos (ej. check_tables.php) o endpoints de debug, DEBEN BORRARSE POR COMPLETO e inmediatamente finalizada la validación (tanto en local como dentro del contenedor web usando docker compose exec o comandos equivalentes).
11. Si hubo cambios en carga/edición de imágenes, validar también que la imagen previa se haya reemplazado/eliminado correctamente y que el orden asociado (si aplica) permanezca persistido tras recargar datos.

Comandos base:
- `docker compose up -d --build frontend`
- `docker compose exec frontend sh -c "rm -rf /app/node_modules/.vite"`
- `docker compose restart frontend`
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
5. Caché de frontend descartada cuando hubo dudas visuales o el usuario reportó que no veía cambios, incluyendo reinicio de Vite/HMR y hard refresh del navegador.
6. Verificación funcional en la ruta impactada (SPA + diseño/paridad Angelow), incluyendo revisión responsive de la sección intervenida en desktop, tablet y móvil.
7. Verificación de codificación UTF-8 en archivos modificados (sin BOM en PHP/JS/Vue/CSS y sin textos corruptos) y revisión visual/funcional de ortografía en todo texto visible tocado del admin.
8. Evidencia de pruebas de endpoints ejecutadas y limpieza de artefactos temporales de test/debug.
9. Revisión explícita de duplicación, código innecesario o deuda técnica local en los archivos tocados, con corrección inmediata o documentación del remanente antes de cerrar.
10. Verificación de ubicación correcta de archivos creados o movidos, con imports, referencias y enlaces actualizados después de la reorganización.

## Git y ramas (humanizado, git flow)
- Crear ramas de trabajo con intención funcional clara.
- Seguir lógica tipo git flow (feature/fix por módulo).
- Commits pequeños, descriptivos y naturales (no genéricos de IA).
- No mezclar en un commit cambios no relacionados.

## Criterio de calidad
- Código limpio, nombres claros, sin duplicación innecesaria.
- Comentarios cortos en español cuando una parte no sea obvia.
- Si en un archivo intervenido existen comentarios, notas técnicas o documentación local en inglés, traducirlos al español antes de cerrar la tarea.
- En documentación y comentarios, usar siempre UTF-8 real con ortografía española completa; no dejar transliteraciones ASCII cuando se trate de texto documental.
- Solo los nombres de archivo Markdown de documentación deben mantenerse en ASCII seguro; el contenido interno y los comentarios siguen siendo UTF-8 real en español.
- Preferencia operativa: al modificar archivos, agregar comentarios breves en español en lógica no trivial para facilitar mantenimiento y soporte.
- Al crear o editar archivos, no dejar funciones, condicionales ni procesos nuevos sin explicación; el comentario debe dejar clara la intención técnica y, si hay reutilización, mencionar de dónde viene.
- En cada intervención, revisar duplicación local, ramas muertas, estilos redundantes y acoplamientos innecesarios; resolverlos cuando la corrección sea segura y documentar cualquier deuda técnica remanente.
- Si un archivo o documento no está en la carpeta correcta según su responsabilidad, moverlo y dejar actualizadas todas las referencias necesarias en la misma intervención.
- En `docs/`, evitar archivos sueltos cuando ya exista una carpeta temática adecuada; clasificar por tipo, módulo, dominio o servicio antes de cerrar la tarea.
- Si se crea o actualiza documentación, refrescar el índice navegable del archivo y revisar si `README.md`, `docs/README.md` o `docs/operaciones/manual-tecnico.md` también requieren enlace o contexto adicional.
- Validaciones de formulario y mensajes consistentes con Angelow.
- En todo texto visible del admin, revisar ortografía española completa antes de cerrar: tildes, `ñ`, signos, nombres de estados, placeholders, títulos, breadcrumbs, botones, modales, tablas, empty states, snackbars y encabezados de exportación.
- En IU, cuando una vista tenga carga asíncrona o refresco perceptible, usar shimmer reutilizable y no saltos bruscos de contenido; preferir placeholders consistentes del sistema antes que vacíos repentinos.
- En IU, usar transiciones y animaciones suaves cuando aporten claridad de estado o jerarquía visual; deben ser fluidas, breves y sobrias, nunca decorativas ni invasivas.
- Validación en tiempo real obligatoria en todos los formularios con feedback inmediato por campo y sin esperar al submit.
- Mantener paridad visual con legacy antes de cerrar una tarea, incluyendo animaciones/microinteracciones cuando existan en la vista original.
- Todo diseño frontend entregado debe conservar legibilidad, jerarquía visual y layout correcto en desktop, tablet y móvil; evitar solapes, desbordes y controles inaccesibles.
- Si se modifica una sección existente, esa misma sección debe quedar validada en todos los tamaños que afecte el cambio, incluso cuando el ajuste parezca solo visual o puntual.
- Evitar degradados de color en la UI; preferir colores planos consistentes con Angelow legacy, excepto cuando la vista legacy ya use degradados de forma comprobable.
- En admin, preferir paletas sólidas suaves con acentos moderados, acabado glass sobrio y sombras cortas de soporte; no usar rellenos saturados ni geometrías rígidas que rompan la coherencia entre filtros, tablas, tarjetas y acciones.
- En admin, cualquier texto operativo derivado de estados, métodos o transiciones devuelto por la BD debe pasar por una capa de traducción/presentación para mostrarse en español y con terminología coherente del sistema.
- En admin, la navegación visible al usuario no debe revelar detalles de migración o arquitectura en la URL; si internamente se necesita distinguir fuentes de datos, esa adaptación debe hacerse detrás de una capa neutra de routing/presentación.
- En flujos con archivos adjuntos, guardar y reutilizar la ruta relativa real del archivo subido, no solo el nombre original del archivo, para evitar referencias rotas en `/uploads`.
- Si un adjunto no se puede renderizar, el mensaje debe ser neutro y útil para el usuario final, sin hablar de carpetas, mounts, filesystem o implementación interna.
- Prohibido mezclar UI, estado y acceso a datos en bloques monolíticos: aplicar estructura modular y reusable.
- Centralizar componentes de feedback (snackbar/toast/alertas) para asegurar consistencia visual y funcional en todo el frontend.
- Documentar en cada tarea qué patrón(es) de diseño se usaron, dónde se aplicaron y qué problema resolvieron, alineado con Refactoring Guru.
- Documentar en docs cada nueva librería/dependencia Composer utilizada, incluyendo archivo(s) y módulo(s) donde quedó aplicada.
