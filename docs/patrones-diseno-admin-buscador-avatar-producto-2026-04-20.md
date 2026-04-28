# Patrones de diseño aplicados: buscador admin, avatar y detalle de producto (2026-04-20)

## Problema
Se reportaron cuatro fricciones de UX en vistas productivas:
1. El botón Volver del detalle de producto se veía desalineado.
2. El campo Último acceso de administradores no mostraba datos aunque el backend sí los exponía.
3. El control Cambiar foto en modal de administrador tenía una presentación visual deficiente.
4. El buscador global del header admin fallaba con términos como ord/ordenes/pedidos por sensibilidad a acentos y sinónimos.

## Patrón 1: Adapter (normalización de texto para búsqueda tolerante)
- Objetivo: adaptar entradas de usuario (con/sin tildes, singular/plural y sinónimos) a un formato común antes de filtrar módulos.
- Implementación:
  - frontend/src/modules/admin/components/AdminHeader.vue
- Qué resuelve:
  - Normaliza cadenas removiendo diacríticos con normalize('NFD').
  - Permite coincidencias reales para ord, ordenes, pedidos, facturas, clientes, etc.
  - Unifica click en icono y Enter para enviar búsqueda.

## Patrón 2: Null Object (fallback de fecha operativa)
- Objetivo: evitar celdas vacías o guiones ambiguos cuando no existe último acceso válido.
- Implementación:
  - frontend/src/modules/admin/pages/AdminAdministratorsPage.vue
- Qué resuelve:
  - Centraliza resolveLastAccess para leer last_access/last_login/last_access_at.
  - Retorna Sin registro cuando no hay fecha válida.
  - Mantiene consistencia visual en la tabla de administradores.

## Patrón 3: Decorator (mejora visual sin romper estructura existente)
- Objetivo: mejorar apariencia y legibilidad del botón Volver y del control de foto sin reestructurar componentes base.
- Implementación:
  - frontend/src/modules/catalog/views/ProductDetailView.css
  - frontend/src/modules/admin/pages/AdminAdministratorsPage.vue
- Qué resuelve:
  - Refina estilos del botón Volver con jerarquía visual clara y responsiva.
  - Reemplaza el trigger visual de foto por un control con iconografía clara y estados de carga.
  - Mantiene el flujo actual del modal y la misma API de carga de imagen.

## Resultado esperado
- Búsqueda global admin más tolerante y utilizable.
- Tabla de administradores mostrando correctamente Último acceso.
- Control de cambio de foto consistente con el lenguaje visual del dashboard.
- Botón Volver estable en desktop/móvil y alineado con la vista.