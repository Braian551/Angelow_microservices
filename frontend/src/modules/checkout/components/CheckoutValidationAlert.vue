<template>
  <transition name="checkout-alert-slide">
    <div
      v-if="visible && errors.length > 0"
      class="checkout-validation-alert"
      role="alert"
      aria-live="polite"
    >
      <div class="checkout-validation-alert__icon">
        <i class="fas fa-exclamation-circle" />
      </div>
      <div class="checkout-validation-alert__body">
        <strong>{{ title }}</strong>
        <ul class="checkout-validation-alert__list">
          <li v-for="(error, index) in errors" :key="index">
            <i :class="error.icon || 'fas fa-circle-dot'" />
            {{ error.text }}
          </li>
        </ul>
      </div>
    </div>
  </transition>
</template>

<script setup>
// Componente reutilizable de alerta de validación de formulario para el flujo de checkout.
// Replica el estilo del banner de error de ShippingPage para consistencia visual entre pasos.
defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Antes de continuar, completa lo siguiente:',
  },
  errors: {
    type: Array,
    default: () => [],
    // Cada elemento: { icon: 'fas fa-...', text: 'Mensaje de error' }
  },
})
</script>

<style scoped>
/* Banner de validación global del formulario de checkout */
.checkout-validation-alert {
  display: flex;
  align-items: flex-start;
  gap: 1.2rem;
  margin: 0 0 2rem;
  padding: 1.6rem 2rem;
  border-radius: 1.4rem;
  background: #fff5f5;
  border: 1.5px solid rgba(198, 40, 40, 0.25);
  box-shadow: 0 2px 8px rgba(198, 40, 40, 0.08);
}

.checkout-validation-alert__icon {
  flex-shrink: 0;
  width: 3.4rem;
  height: 3.4rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(198, 40, 40, 0.1);
  color: #c62828;
  font-size: 1.5rem;
}

.checkout-validation-alert__body {
  flex: 1;
  min-width: 0;
}

.checkout-validation-alert__body strong {
  display: block;
  margin-bottom: 0.8rem;
  color: #8b1a1a;
  font-size: 1.35rem;
  font-weight: 700;
}

.checkout-validation-alert__list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.checkout-validation-alert__list li {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  color: #c62828;
  font-size: 1.28rem;
  font-weight: 600;
}

.checkout-validation-alert__list li i {
  font-size: 1.1rem;
  flex-shrink: 0;
  color: #c62828;
  opacity: 0.75;
}

/* Transición de entrada/salida del banner */
.checkout-alert-slide-enter-active,
.checkout-alert-slide-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.checkout-alert-slide-enter-from,
.checkout-alert-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
