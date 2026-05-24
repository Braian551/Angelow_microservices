# Patrones de diseño — Tienda: Filtros, Preguntas y Reseñas (2026-05-10)

## Contexto
Rediseño visual de la barra lateral de filtros de la tienda y de los componentes de preguntas/respuestas y reseñas del detalle de producto, para mejorar jerarquía visual, claridad y presentación.

---

## 1) Decorator (Decorador)

**Archivo:** `frontend/src/modules/catalog/pages/StorePage.vue`

**Problema resuelto:** La barra de filtros carecía de jerarquía visual: los títulos eran texto plano sin íconos ni indicadores de estado activo, lo que hacía la interfaz confusa y plana.

**Solución:** Se decoraron los botones de título de cada sección de filtro con:
- Un ícono semántico por tipo de filtro (etiquetas, silueta infantil, dólar, precio, capas).
- Un punto azul (`.filter-active-dot`) visible únicamente cuando el filtro tiene valor seleccionado.
- Clase CSS dinámica `has-filter` en el wrapper del grupo para cambiar el color del ícono y del texto del título.

```html
<span class="filter-title-left">
  <span class="filter-icon-wrap"><i class="fas fa-tags"></i></span>
  <span>Categorías</span>
  <span v-if="filters.category !== ''" class="filter-active-dot"></span>
</span>
```

---

## 2) State (Estado) — visual

**Archivo:** `frontend/src/modules/catalog/pages/StorePage.css`

**Problema resuelto:** No existía retroalimentación visual sobre cuáles filtros tenían valores activos.

**Solución:** Se usan las clases CSS `has-filter` (a nivel del grupo) y `is-open` (en el botón de título) para cambiar visualmente el ícono, el color del texto y el ícono de flecha del acordeón.

```css
/* Activo cuando hay valor en el filtro */
.has-filter .filter-icon-wrap { background: #e6f1fb; color: #0b78b4; }
.has-filter .filter-title { color: #0b78b4; }

/* Opción seleccionada con :has() */
.filter-option:has(input:checked) {
  background: rgba(11, 120, 180, 0.09);
  border-radius: 8px;
  color: #0b78b4;
  font-weight: 600;
}
```

---

## 3) Composite (Compuesto) — tarjetas de reseñas

**Archivo:** `frontend/src/modules/catalog/pages/ProductDetailPage.vue`  
**Archivo CSS:** `frontend/src/modules/catalog/views/ProductDetailView.css`

**Problema resuelto:** La tarjeta de reseña tenía el avatar desconectado visualmente del nombre del usuario, y las estrellas estaban en la sección del cuerpo (`.review-head`) lejos del encabezado. El layout era confuso.

**Solución:** Se reestructuró el componente `.review-card` para que `.review-meta` contenga:
- Avatar (izquierda, flex: 0 0 52px)
- `.user-info` (flex: 1) con `.user-info-top` (nombre + badge) y la fecha abajo
- Estrellas (`.review-stars`) alineadas a la derecha como tercer elemento del flex

```html
<div class="review-meta">
  <div class="user-avatar">...</div>
  <div class="user-info">
    <div class="user-info-top">
      <strong>Nombre</strong>
      <span class="badge verified">Compra verificada</span>
    </div>
    <span class="time">fecha</span>
  </div>
  <div class="user-rating review-stars">★★★★★</div>
</div>
```

CSS clave:
```css
.product-detail-page .review-meta {
  display: flex; align-items: flex-start; gap: 1.2rem;
  padding-bottom: 1.4rem;
  border-bottom: 1px solid rgba(12, 52, 84, 0.07);
}
.product-detail-page .user-info { flex: 1; min-width: 0; }
```

---

## 4) Composite — tarjetas de preguntas y respuestas

**Archivo:** `frontend/src/modules/catalog/pages/ProductDetailPage.vue`  
**Archivo CSS:** `frontend/src/modules/catalog/views/ProductDetailView.css`

**Problema resuelto:** Las preguntas estaban en un contenedor blanco unificado sin separación visual por pregunta. Las respuestas no tenían identidad visual propia.

**Solución:**
- `.questions-list` cambió de un contenedor blanco con borde único a un flex-column con `gap: 1.2rem` y fondo transparente.
- Cada `.question-item` es ahora una tarjeta independiente con `border-radius: 16px`.
- Cada `.answer-item` tiene fondo `rgba(11, 120, 180, 0.05)` y borde azul tenue para identificarse como respuesta del vendedor.
- El meta de la respuesta usa `.answer-user-info` (flex: 1) con `.user-info-top` que agrupa nombre y badge.

```css
.product-detail-page .questions-list { background: transparent; display: flex; flex-direction: column; gap: 1.2rem; }
.product-detail-page .question-item { background: #fff; border-radius: 16px; padding: 2rem 2.4rem; }
.product-detail-page .answer-item { background: rgba(11, 120, 180, 0.05); border-radius: 12px; }
.product-detail-page .answer-user-info { flex: 1; min-width: 0; }
```

---

## Archivos modificados

| Archivo | Tipo de cambio |
|---|---|
| `frontend/src/modules/catalog/pages/StorePage.vue` | Template: íconos, has-filter, filter-active-dot en cada grupo de filtro |
| `frontend/src/modules/catalog/pages/StorePage.css` | CSS: estilos filter-icon-wrap, filter-active-dot, :has(input:checked), hover |
| `frontend/src/modules/catalog/pages/ProductDetailPage.vue` | Template: review-meta reestructurado, question-meta con user-info-top, answer-meta con answer-user-info |
| `frontend/src/modules/catalog/views/ProductDetailView.css` | CSS: review-meta flex-start, user-info flex:1, user-info-top, questions-list transparente, answer-item con acento azul |
