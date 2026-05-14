# Patrones de diseño aplicados en la vista previa de anuncios y sliders del admin

Fecha: 2026-05-10

## Contexto del problema
- La vista previa del anuncio en el modal del admin ya reutilizaba el componente visual de home, pero fallaba al subir una imagen nueva porque la resolución de medios trataba URLs locales `blob:` y `data:` como rutas estáticas.
- La vista previa del slider en el admin renderizaba una maqueta propia, distinta al hero real de inicio, lo que generaba diferencias visuales frente al carrusel publicado.

## Patrón 1: Adapter
- Referencia: https://refactoring.guru/es/design-patterns/adapter
- Problema que resuelve: adaptar en un mismo contrato de medios rutas de `uploads`, URLs absolutas y previews temporales del navegador (`blob:` y `data:`) para que banners y sliders no rompan la vista previa durante la edición.
- Archivos aplicados:
  - frontend/src/utils/media.js
  - frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue
  - frontend/src/modules/home/components/PromoBanner.vue
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - frontend/src/modules/home/components/HomeHeroSlider.vue
- Implementación clave:
  - `media.js` detecta previews locales del navegador y los devuelve como candidatos válidos antes de aplicar fallbacks de `uploads`.
  - El admin puede usar el mismo flujo de render que home sin ramificaciones ad hoc para imágenes recién seleccionadas.

## Patrón 2: Flyweight
- Referencia: https://refactoring.guru/es/design-patterns/flyweight
- Problema que resuelve: evitar duplicar la lógica visual del carrusel entre home y admin, manteniendo una única representación del hero para publicación y vista previa.
- Archivos aplicados:
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - frontend/src/modules/home/components/HomeHeroSlider.vue
- Implementación clave:
  - El modal de sliders deja de construir una tarjeta independiente y reutiliza `HomeHeroSlider` con un contenedor compacto dentro del admin.
  - La vista previa se renderiza debajo del formulario y ocupa todo el ancho disponible del contenedor del modal para aproximarse mejor a la proporción horizontal del home.
  - Los textos, la imagen, el CTA y la composición general quedan gobernados por el mismo componente que usa la página de inicio.

## Patrón 3: Template Method
- Referencia: https://refactoring.guru/es/design-patterns/template-method
- Problema que resuelve: mantener en sliders el mismo flujo operativo de selección de destinos que ya existe en anuncios, sin obligar al usuario a escribir rutas manuales en casos comunes.
- Archivos aplicados:
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
  - frontend/src/modules/admin/pages/AdminAnnouncementsPage.vue
  - frontend/src/modules/admin/utils/storeLinkOptions.js
- Implementación clave:
  - Sliders y anuncios consumen una misma utilitaria de destinos de tienda con rutas frecuentes, filtros públicos reales, categorías y colecciones dinámicas.
  - La detección de enlaces guardados decide si el formulario debe mostrar una opción del catálogo o abrir el campo manual, siguiendo el mismo patrón operativo en ambos módulos.
  - El campo manual permanece disponible para campañas específicas como productos por slug, colecciones puntuales o URLs externas.

## Patrón 4: Strategy
- Referencia: https://refactoring.guru/es/design-patterns/strategy
- Problema que resuelve: la vista previa del slider en admin necesita un comportamiento visual distinto al home para evitar shimmer persistente en un contexto de edición, sin duplicar el componente completo.
- Archivos aplicados:
  - frontend/src/modules/home/components/HomeHeroSlider.vue
  - frontend/src/modules/admin/pages/AdminSlidersPage.vue
- Implementación clave:
  - `HomeHeroSlider` admite modo de vista previa para admin, manteniendo la misma estructura visual del hero pero sin depender del estado de carga animado del home.
  - El admin reutiliza el componente real del home y solo cambia la estrategia de presentación de imagen mientras el usuario edita.

## Resultado esperado
- Al seleccionar una imagen nueva en anuncios, la vista previa del banner muestra el archivo local inmediatamente y con el mismo render de home.
- Al editar o crear sliders, la vista previa del modal replica el hero real de inicio en lugar de una aproximación visual distinta y se muestra con un ancho más horizontal dentro del modal.
- El usuario de admin sliders y anuncios puede elegir rutas frecuentes, categorías activas y colecciones activas desde un selector común, y solo escribir una URL cuando realmente necesita un destino personalizado.
- La consistencia entre admin y tienda mejora sin duplicar lógica de presentación.