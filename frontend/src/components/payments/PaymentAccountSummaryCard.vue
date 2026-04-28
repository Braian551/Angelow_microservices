<template>
  <div class="payment-account-card">
    <div v-if="account" class="payment-account-card__surface">
      <div class="payment-account-card__headline">
        <div class="payment-account-card__icon">
          <i class="fas fa-university"></i>
        </div>

        <div class="payment-account-card__copy">
          <h3>{{ account.bank_name || 'Cuenta bancaria activa' }}</h3>
          <p>{{ description }}</p>
        </div>
      </div>

      <div class="payment-account-card__grid">
        <div class="payment-account-card__item">
          <span>Tipo de cuenta</span>
          <strong>{{ accountTypeLabel }}</strong>
        </div>

        <div class="payment-account-card__item">
          <span>Número de cuenta</span>
          <strong>{{ account.account_number || 'Sin definir' }}</strong>
        </div>

        <div class="payment-account-card__item">
          <span>Titular</span>
          <strong>{{ account.account_holder || 'Sin definir' }}</strong>
        </div>

        <div class="payment-account-card__item">
          <span>Documento</span>
          <strong>{{ documentLabel }}</strong>
        </div>

        <div v-if="account.email" class="payment-account-card__item">
          <span>Email de contacto</span>
          <strong>{{ account.email }}</strong>
        </div>

        <div v-if="account.phone" class="payment-account-card__item">
          <span>Teléfono</span>
          <strong>{{ account.phone }}</strong>
        </div>

        <template v-if="showTransferMeta">
          <div class="payment-account-card__item">
            <span>Monto a registrar</span>
            <strong>{{ amountLabel }}</strong>
          </div>

          <div class="payment-account-card__item">
            <span>Referencia requerida</span>
            <strong>{{ reference || 'Pendiente' }}</strong>
          </div>

          <div class="payment-account-card__item">
            <span>Estado esperado</span>
            <strong>{{ expectedStatus }}</strong>
          </div>
        </template>
      </div>

      <slot />
    </div>

    <div v-else class="payment-account-card__empty">
      <i class="fas fa-credit-card"></i>
      <strong>Sin cuenta activa configurada</strong>
      <p>No hay una cuenta habilitada para mostrar al cliente en este momento.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  account: {
    type: Object,
    default: null,
  },
  description: {
    type: String,
    default: 'Cuenta activa para recibir transferencias.',
  },
  amount: {
    type: Number,
    default: null,
  },
  reference: {
    type: String,
    default: '',
  },
  expectedStatus: {
    type: String,
    default: 'Pendiente de verificación',
  },
  showTransferMeta: {
    type: Boolean,
    default: false,
  },
})

const accountTypeLabel = computed(() => {
  const rawType = String(props.account?.account_type_label || props.account?.account_type || '').toLowerCase().trim()

  if (rawType === 'corriente' || rawType === 'cuenta corriente') return 'Cuenta corriente'
  if (rawType === 'ahorros' || rawType === 'cuenta de ahorros') return 'Cuenta de ahorros'
  return 'Cuenta bancaria'
})

const documentLabel = computed(() => {
  const documentType = String(props.account?.identification_type_label || props.account?.identification_type || '').toLowerCase().trim()
  const typeLabel = documentType === 'cc' || documentType === 'cédula'
    ? 'Cédula'
    : (documentType === 'ce' || documentType === 'cédula de extranjería'
        ? 'Cédula de extranjería'
        : (documentType === 'nit' ? 'NIT' : 'Documento'))

  const documentNumber = String(props.account?.identification_number || '').trim()
  return documentNumber ? `${typeLabel} ${documentNumber}` : typeLabel
})

const amountLabel = computed(() => {
  const numericAmount = Number(props.amount)
  if (!Number.isFinite(numericAmount)) return 'Por definir'

  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(numericAmount)
})
</script>

<style scoped>
.payment-account-card {
  width: 100%;
}

.payment-account-card__surface,
.payment-account-card__empty {
  border: 1px solid rgba(207, 224, 236, 0.92);
  border-radius: 1.8rem;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.06);
}

.payment-account-card__surface {
  display: grid;
  gap: 1.4rem;
  padding: 1.6rem;
}

.payment-account-card__headline {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.payment-account-card__icon {
  width: 4.4rem;
  height: 4.4rem;
  border-radius: 1.3rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.1);
  color: #0f7abf;
  font-size: 1.7rem;
  flex-shrink: 0;
}

.payment-account-card__copy {
  display: grid;
  gap: 0.3rem;
}

.payment-account-card__copy h3 {
  margin: 0;
  font-size: 1.7rem;
  color: #12263a;
}

.payment-account-card__copy p {
  margin: 0;
  color: #526277;
  font-size: 1rem;
  line-height: 1.5;
}

.payment-account-card__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.95rem;
}

.payment-account-card__item {
  display: grid;
  gap: 0.32rem;
  padding: 0.95rem 1rem;
  border-radius: 1.2rem;
  border: 1px solid rgba(207, 224, 236, 0.86);
  background: #f8fbfe;
}

.payment-account-card__item span {
  font-size: 0.92rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: #6b7a90;
}

.payment-account-card__item strong {
  font-size: 1.08rem;
  line-height: 1.45;
  color: #12263a;
  word-break: break-word;
}

.payment-account-card__empty {
  display: grid;
  justify-items: center;
  gap: 0.7rem;
  padding: 2.1rem 1.4rem;
  text-align: center;
  color: #526277;
}

.payment-account-card__empty i {
  font-size: 2rem;
  color: #0f7abf;
}

.payment-account-card__empty strong {
  font-size: 1.15rem;
  color: #12263a;
}

.payment-account-card__empty p {
  margin: 0;
  max-width: 44rem;
  line-height: 1.55;
}

@media (max-width: 768px) {
  .payment-account-card__surface {
    padding: 1.25rem;
  }

  .payment-account-card__grid {
    grid-template-columns: 1fr;
  }
}
</style>