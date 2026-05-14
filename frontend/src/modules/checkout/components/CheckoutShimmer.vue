<template>
  <!-- Esqueleto shimmer para los pasos del flujo de checkout -->
  <div class="checkout-shimmer" aria-busy="true" aria-label="Cargando...">
    <!-- Barra superior divisora -->
    <div class="checkout-shimmer__divider" />

    <div class="checkout-shimmer__layout">
      <!-- Columna principal -->
      <div class="checkout-shimmer__main">
        <!-- Tarjeta 1 -->
        <div class="checkout-shimmer__card">
          <div class="checkout-shimmer__card-header">
            <div class="checkout-shimmer__line checkout-shimmer__line--title" />
            <div class="checkout-shimmer__line checkout-shimmer__line--subtitle" />
          </div>
          <div class="checkout-shimmer__card-body">
            <div class="checkout-shimmer__block checkout-shimmer__block--tall" />
            <div class="checkout-shimmer__block checkout-shimmer__block--tall" />
          </div>
        </div>

        <!-- Tarjeta 2 -->
        <div class="checkout-shimmer__card">
          <div class="checkout-shimmer__card-header">
            <div class="checkout-shimmer__line checkout-shimmer__line--title" />
            <div class="checkout-shimmer__line checkout-shimmer__line--subtitle" />
          </div>
          <div class="checkout-shimmer__card-body">
            <div class="checkout-shimmer__form-row">
              <div class="checkout-shimmer__field" />
              <div class="checkout-shimmer__field" />
            </div>
            <div class="checkout-shimmer__block checkout-shimmer__block--medium" />
          </div>
        </div>
      </div>

      <!-- Columna resumen (sidebar) -->
      <aside class="checkout-shimmer__sidebar">
        <div class="checkout-shimmer__sidebar-card">
          <div class="checkout-shimmer__line checkout-shimmer__line--title" />
          <div class="checkout-shimmer__sidebar-row" />
          <div class="checkout-shimmer__sidebar-row checkout-shimmer__sidebar-row--short" />
          <div class="checkout-shimmer__sidebar-row" />
          <div class="checkout-shimmer__sidebar-row checkout-shimmer__sidebar-row--short" />
          <div class="checkout-shimmer__sidebar-total" />
          <div class="checkout-shimmer__sidebar-btn" />
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
// Componente de carga shimmer reutilizable para todas las vistas del flujo de checkout.
// Representa el esqueleto visual de la página mientras se cargan los datos de la API.
</script>

<style scoped>
/* Animación base shimmer */
@keyframes checkoutShimmerPulse {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.checkout-shimmer {
  width: 100%;
  animation: checkoutShimmerFadeIn 0.3s ease;
}

@keyframes checkoutShimmerFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Mixin de patrón shimmer (aplicado vía clase de utilidad) */
.checkout-shimmer__divider,
.checkout-shimmer__line,
.checkout-shimmer__block,
.checkout-shimmer__field,
.checkout-shimmer__sidebar-row,
.checkout-shimmer__sidebar-total,
.checkout-shimmer__sidebar-btn {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e8e8e8 37%,
    #f0f0f0 63%
  );
  background-size: 400% 100%;
  animation: checkoutShimmerPulse 1.5s ease infinite;
  border-radius: 0.8rem;
}

/* Divisor superior */
.checkout-shimmer__divider {
  height: 1px;
  background: #e0e0e0;
  margin-bottom: 2rem;
  animation: none;
  border-radius: 0;
}

/* Layout dos columnas */
.checkout-shimmer__layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 36rem;
  gap: 3rem;
  align-items: start;
}

/* Columna principal */
.checkout-shimmer__main {
  display: grid;
  gap: 2.5rem;
}

/* Tarjetas */
.checkout-shimmer__card {
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  padding: 2.4rem;
}

.checkout-shimmer__card-header {
  padding-bottom: 1.6rem;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.checkout-shimmer__card-body {
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

/* Líneas de texto */
.checkout-shimmer__line {
  height: 1.6rem;
  border-radius: 0.6rem;
}

.checkout-shimmer__line--title {
  width: 42%;
  height: 2rem;
}

.checkout-shimmer__line--subtitle {
  width: 70%;
  height: 1.4rem;
}

/* Bloques de contenido */
.checkout-shimmer__block {
  border-radius: 1.8rem;
  width: 100%;
}

.checkout-shimmer__block--tall {
  height: 9rem;
}

.checkout-shimmer__block--medium {
  height: 14rem;
}

/* Fila de campos de formulario */
.checkout-shimmer__form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.4rem;
}

.checkout-shimmer__field {
  height: 5.6rem;
  border-radius: 1.4rem;
}

/* Sidebar resumen */
.checkout-shimmer__sidebar {
  position: sticky;
  top: 12rem;
}

.checkout-shimmer__sidebar-card {
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  padding: 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

.checkout-shimmer__sidebar-row {
  height: 1.6rem;
  width: 100%;
  border-radius: 0.6rem;
}

.checkout-shimmer__sidebar-row--short {
  width: 65%;
}

.checkout-shimmer__sidebar-total {
  height: 2.2rem;
  width: 80%;
  margin-top: 0.4rem;
  border-radius: 0.6rem;
}

.checkout-shimmer__sidebar-btn {
  height: 5.6rem;
  border-radius: 999px;
  margin-top: 0.8rem;
}

/* Responsivo */
@media (max-width: 1080px) {
  .checkout-shimmer__layout {
    grid-template-columns: 1fr;
  }

  .checkout-shimmer__sidebar {
    position: static;
    top: auto;
  }
}

@media (max-width: 768px) {
  .checkout-shimmer__card {
    padding: 1.8rem 1.4rem;
  }

  .checkout-shimmer__form-row {
    grid-template-columns: 1fr;
  }
}
</style>
