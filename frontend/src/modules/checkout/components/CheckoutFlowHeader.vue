<template>
  <header class="checkout-flow-header">
    <!-- Título de la pantalla actual del checkout con ícono contextual. -->
    <div class="checkout-flow-heading">
      <div class="checkout-flow-icon">
        <i :class="iconClass" />
      </div>
      <h1 class="checkout-flow-title">{{ title }}</h1>
    </div>

    <!-- Indicador fijo de las cuatro etapas del checkout. -->
    <div class="checkout-flow-steps" aria-label="Pasos del checkout">
      <div
        v-for="step in steps"
        :key="step.id"
        class="checkout-flow-step"
        :class="{ 'checkout-flow-step--active': step.id === activeStep }"
      >
        <span>{{ step.id }}</span>
        <p>{{ step.label }}</p>
      </div>
    </div>
  </header>
</template>

<script setup>
// Props que permiten reutilizar el encabezado en envío, pago y confirmación.
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  iconClass: {
    type: String,
    required: true,
  },
  activeStep: {
    type: Number,
    required: true,
  },
})

// Secuencia visual compartida del checkout; el paso activo se resalta desde props.
const steps = [
  { id: 1, label: 'Carrito' },
  { id: 2, label: 'Envío' },
  { id: 3, label: 'Pago' },
  { id: 4, label: 'Confirmación' },
]
</script>

<style scoped>
/* Contenedor del encabezado y del indicador de pasos. */
.checkout-flow-header {
  display: grid;
  gap: 2rem;
  margin-bottom: 1rem;
}

/* Agrupa ícono y título de la pantalla. */
.checkout-flow-heading {
  display: flex;
  align-items: center;
  gap: 1.4rem;
}

/* Ícono circular que identifica la etapa actual. */
.checkout-flow-icon {
  width: 7.8rem;
  height: 7.8rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.12);
  color: #0077b6;
  font-size: 2.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Título principal del paso de checkout. */
.checkout-flow-title {
  margin: 0;
  color: #333333;
  font-size: 3.2rem;
  font-weight: 700;
  line-height: 1.1;
}

/* Carril horizontal de pasos del checkout. */
.checkout-flow-steps {
  position: relative;
  display: flex;
  justify-content: space-between;
  width: min(100%, 760px);
  margin: 0 auto;
}

/* Línea de fondo que conecta los pasos. */
.checkout-flow-steps::before {
  content: '';
  position: absolute;
  top: 1.6rem;
  left: 0;
  right: 0;
  height: 2px;
  background: #e0e0e0;
  z-index: 0;
}

/* Paso individual con número y etiqueta. */
.checkout-flow-step {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.8rem;
}

/* Círculo numerado del paso. */
.checkout-flow-step span {
  width: 3.2rem;
  height: 3.2rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #e0e0e0;
  color: #666666;
  font-size: 1.7rem;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

/* Etiqueta textual debajo del círculo. */
.checkout-flow-step p {
  margin: 0;
  color: #666666;
  font-size: 1.4rem;
  font-weight: 500;
}

/* Estado activo con color de marca y pulso suave. */
.checkout-flow-step--active span {
  background: #0077b6;
  color: #ffffff;
  transform: scale(1.08);
  box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.12);
  animation: checkoutFlowPulse 2s ease-in-out infinite;
}

/* Etiqueta destacada para el paso activo. */
.checkout-flow-step--active p {
  color: #333333;
  font-weight: 600;
}

/* Pulso visual para reforzar el paso actual sin bloquear lectura. */
@keyframes checkoutFlowPulse {
  0%,
  100% {
    box-shadow: 0 0 0 0 rgba(0, 119, 182, 0.4);
  }

  50% {
    box-shadow: 0 0 0 10px rgba(0, 119, 182, 0);
  }
}

/* Ajustes móviles para reducir tamaño del encabezado y pasos. */
@media (max-width: 640px) {
  .checkout-flow-heading {
    gap: 1rem;
  }

  .checkout-flow-icon {
    width: 6.2rem;
    height: 6.2rem;
    font-size: 2rem;
  }

  .checkout-flow-title {
    font-size: 2.3rem;
  }

  .checkout-flow-steps {
    gap: 0.8rem;
  }

  .checkout-flow-step p {
    font-size: 1.1rem;
  }
}
</style>
