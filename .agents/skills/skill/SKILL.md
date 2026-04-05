---
name: skill
description: SKILL.md - Angelow Microservicios (Frontend + Backend)
---

## Objetivo
Esta skill define cómo trabajar la migración de Angelow legacy (PHP) a Angelow microservicios (SPA Vue + APIs Laravel), manteniendo diseño, lógica y datos alineados.

## Reglas obligatorias
1. El frontend de microservicios debe verse y comportarse igual que Angelow legacy en las pantallas migradas, incluyendo animaciones y transiciones.
2. Todo texto nuevo debe guardarse en UTF-8 real (sin mojibake).
3. La arquitectura es de microservicios: cada dominio consulta su servicio y su tabla propia, con fallback legacy durante migración cuando aplique.
4. En backend se debe usar ORM (Eloquent) como primera opción; usar Query Builder solo cuando sea necesario y documentado.
5. En frontend, navegación tipo SPA real: no recargar header ni aside al cambiar vistas de dashboard.
6. El aside de dashboard también sigue regla SPA (sin refresh completo, sin perder estado de sesión/perfil).
7. Antes de cerrar una tarea, revisar y corregir errores de codificación UTF-8 (incluyendo BOM) en frontend y backend.
8. Si una vista del dashboard no muestra datos, validar primero API del microservicio dueño del dominio (direcciones/notificaciones/pedidos/favoritos) y luego frontend.
9. La UX de estados (éxito/error/info/warning) debe mantener paridad con legacy usando componentes reutilizables tipo snackbar/toast/alerta; no se permiten mensajes dispersos o implementaciones ad hoc por vista.
10. En vistas migradas de dashboard (ejemplo: direcciones), la lógica y el flujo funcional deben replicar legacy antes de introducir mejoras nuevas.
11. Todo cambio frontend/backend debe seguir código limpio: sin código espagueti, con separación de responsabilidades, funciones/métodos pequeños y nombres claros.
12. Los elementos repetibles (feedback visual, formularios, tarjetas, modales, tablas, estados vacíos, loaders) deben implementarse como componentes reutilizables, escalables y mantenibles.
13. Cada cambio del agente debe dejar documentación actualizada del patrón aplicado (o justificar por qué no aplica), tomando como referencia el catálogo de patrones de diseño: https://refactoring.guru/es/design-patterns/catalog.
14. Todo formulario (sin excepción) debe implementar validación en tiempo real por campo (on input/on change), mostrando errores claros y consistentes antes del submit.
15. Al documentar patrones de diseño, se debe indicar explícitamente en qué archivo(s) se aplicó cada patrón (ruta exacta en el proyecto), además del problema que resuelve.
16. Siempre que se agregue, actualice o use una librería (frontend/backend) o dependencia Composer para resolver una tarea, se debe registrar en un archivo .md dentro de docs indicando: nombre/version, motivo, comando usado y ruta exacta donde se aplica.
17. Después de cambios en endpoints/controladores, se deben ejecutar pruebas de verificación (endpoint o lógica equivalente) y eliminar al final cualquier archivo temporal de test/debug creado para esa validación.
18. Todo ajuste visual en frontend debe validarse y resolverse de forma responsiva en desktop, tablet y móvil; no se considera terminado si la UI se rompe o se solapa en alguno de esos tamaños.
19. Nunca usar colores degradados en la UI de microservicios, salvo que una pantalla legacy específica ya los tenga y la tarea exija replicarlos exactamente.
20. Si las respuestas de las bases de datos (especialmente legacy) traen caracteres corruptos o errores UTF-8 (por ejemplo `Caf??` o `Mel??n`), se deben aplicar correcciones on-the-fly en las respuestas del backend o parseos del frontend para restaurar los formatos correctos (ej. `Café`, `Melón`) garantizando consistencia visual hasta que los esquemas DB de origen sean completamente corregidos en la migración.

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
8. Si se crean pruebas temporales, scripts de comprobación de base de datos (ej. check_tables.php) o endpoints de debug, DEBEN BORRARSE POR COMPLETO e inmediatamente finalizada la validación (tanto en local como dentro del contenedor web usando docker compose exec o comandos equivalentes).

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

## Git y ramas (humanizado, git flow)
- Crear ramas de trabajo con intención funcional clara.
- Seguir lógica tipo git flow (feature/fix por módulo).
- Commits pequeños, descriptivos y naturales (no genéricos de IA).
- No mezclar en un commit cambios no relacionados.

## Criterio de calidad
- Código limpio, nombres claros, sin duplicación innecesaria.
- Comentarios cortos en español cuando una parte no sea obvia.
- Preferencia operativa: al modificar archivos, agregar comentarios breves en español en lógica no trivial para facilitar mantenimiento y soporte.
- Validaciones de formulario y mensajes consistentes con Angelow.
- Validación en tiempo real obligatoria en todos los formularios con feedback inmediato por campo y sin esperar al submit.
- Mantener paridad visual con legacy antes de cerrar una tarea, incluyendo animaciones/microinteracciones cuando existan en la vista original.
- Todo diseño frontend entregado debe conservar legibilidad, jerarquía visual y layout correcto en desktop, tablet y móvil; evitar solapes, desbordes y controles inaccesibles.
- Evitar degradados de color en la UI; preferir colores planos consistentes con Angelow legacy, excepto cuando la vista legacy ya use degradados de forma comprobable.
- Prohibido mezclar UI, estado y acceso a datos en bloques monolíticos: aplicar estructura modular y reusable.
- Centralizar componentes de feedback (snackbar/toast/alertas) para asegurar consistencia visual y funcional en todo el frontend.
- Documentar en cada tarea qué patrón(es) de diseño se usaron, dónde se aplicaron y qué problema resolvieron, alineado con Refactoring Guru.
- Documentar en docs cada nueva librería/dependencia Composer utilizada, incluyendo archivo(s) y módulo(s) donde quedó aplicada.