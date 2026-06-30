<template>
  <!--
    Componente tarjeta resumen de cuenta bancaria.
    Muestra los datos de la cuenta de pago activa al cliente.
    Si no hay cuenta configurada, muestra un estado vacío informativo.
  -->
  <div class="payment-account-card">
    <!-- Sección principal: se renderiza solo si existe el objeto account -->
    <div v-if="account" class="payment-account-card__surface">
      <!-- Encabezado con ícono del banco y nombre de la cuenta -->
      <div class="payment-account-card__headline">
        <!-- Ícono decorativo que representa una entidad bancaria -->
        <div class="payment-account-card__icon">
          <i class="fas fa-university"></i>
        </div>

        <!-- Nombre del banco (con fallback si no está definido) y descripción de la cuenta -->
        <div class="payment-account-card__copy">
          <h3>{{ account.bank_name || 'Cuenta bancaria activa' }}</h3>
          <p>{{ description }}</p>
        </div>
      </div>

      <!--
        Grid de datos de la cuenta: muestra información clave en pares etiqueta-valor.
        Se organiza en 2 columnas en escritorio y 1 columna en móvil.
      -->
      <div class="payment-account-card__grid">
        <!-- Tipo de cuenta bancaria (corriente, ahorros, etc.) -->
        <div class="payment-account-card__item">
          <span>Tipo de cuenta</span>
          <strong>{{ accountTypeLabel }}</strong>
        </div>

        <!-- Número de cuenta bancaria, con fallback si no está registrado -->
        <div class="payment-account-card__item">
          <span>Número de cuenta</span>
          <strong>{{ account.account_number || 'Sin definir' }}</strong>
        </div>

        <!-- Nombre del titular de la cuenta, con fallback si no está registrado -->
        <div class="payment-account-card__item">
          <span>Titular</span>
          <strong>{{ account.account_holder || 'Sin definir' }}</strong>
        </div>

        <!-- Documento de identificación del titular (cédula, NIT, etc.) -->
        <div class="payment-account-card__item">
          <span>Documento</span>
          <strong>{{ documentLabel }}</strong>
        </div>

        <!-- Email de contacto: se muestra solo si el campo existe en la cuenta -->
        <div v-if="account.email" class="payment-account-card__item">
          <span>Email de contacto</span>
          <strong>{{ account.email }}</strong>
        </div>

        <!-- Teléfono de contacto: se muestra solo si el campo existe en la cuenta -->
        <div v-if="account.phone" class="payment-account-card__item">
          <span>Teléfono</span>
          <strong>{{ account.phone }}</strong>
        </div>

        <!--
          Sección de metadatos de transferencia: se muestra solo cuando
          showTransferMeta es true. Incluye monto, referencia y estado esperado.
          Útil para guiar al cliente durante el proceso de pago.
        -->
        <template v-if="showTransferMeta">
          <!-- Monto que el cliente debe transferir, formateado en pesos colombianos -->
          <div class="payment-account-card__item">
            <span>Monto a registrar</span>
            <strong>{{ amountLabel }}</strong>
          </div>

          <!-- Referencia o código que el cliente debe incluir en la transferencia -->
          <div class="payment-account-card__item">
            <span>Referencia requerida</span>
            <strong>{{ reference || 'Pendiente' }}</strong>
          </div>

          <!-- Estado que se espera alcanzar tras confirmar la transferencia -->
          <div class="payment-account-card__item">
            <span>Estado esperado</span>
            <strong>{{ expectedStatus }}</strong>
          </div>
        </template>
      </div>

      <!-- Slot para contenido adicional que el componente padre pueda inyectar (botones, acciones, etc.) -->
      <slot />
    </div>

    <!--
      Estado vacío: se muestra cuando la prop account es null o no existe.
      Indica al cliente que no hay una cuenta bancaria configurada para recibir pagos.
    -->
    <div v-else class="payment-account-card__empty">
      <i class="fas fa-credit-card"></i>
      <strong>Sin cuenta activa configurada</strong>
      <p>No hay una cuenta habilitada para mostrar al cliente en este momento.</p>
    </div>
  </div>
</template>

<!-- Script del componente usando Composition API con <script setup> -->
<script setup>
// Importación de computed para crear propiedades reactivas derivadas
import { computed } from 'vue'

// Definición de las props del componente con valores por defecto
// account: objeto con los datos de la cuenta bancaria (puede ser null)
const props = defineProps({
  account: {
    type: Object,
    default: null,
  },
  // description: texto descriptivo que acompaña el nombre del banco
  description: {
    type: String,
    default: 'Cuenta activa para recibir transferencias.',
  },
  // amount: monto numérico que el cliente debe transferir (opcional)
  amount: {
    type: Number,
    default: null,
  },
  // reference: código o referencia que el cliente debe incluir en la transferencia
  reference: {
    type: String,
    default: '',
  },
  // expectedStatus: estado que se espera tras la transferencia (para guiar al cliente)
  expectedStatus: {
    type: String,
    default: 'Pendiente de verificación',
  },
  // showTransferMeta: controla si se muestran los metadatos de transferencia
  // (monto, referencia, estado) en la tarjeta
  showTransferMeta: {
    type: Boolean,
    default: false,
  },
})

// Computed que normaliza y formatea el tipo de cuenta bancaria.
// Convierte el valor raw a minúsculas y retorna una etiqueta legible.
// Si el tipo no se reconoce, retorna 'Cuenta bancaria' como valor genérico.
const accountTypeLabel = computed(() => {
  const rawType = String(props.account?.account_type_label || props.account?.account_type || '').toLowerCase().trim()

  if (rawType === 'corriente' || rawType === 'cuenta corriente') return 'Cuenta corriente'
  if (rawType === 'ahorros' || rawType === 'cuenta de ahorros') return 'Cuenta de ahorros'
  return 'Cuenta bancaria'
})

// Computed que construye la etiqueta del documento de identificación.
// Mapea códigos como 'cc', 'ce', 'nit' a sus nombres completos en español.
// Concatena el tipo de documento con el número si este existe.
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

// Computed que formatea el monto de la transferencia en pesos colombianos (COP).
// Utiliza Intl.NumberFormat para obtener el formato monetario local.
// Si el monto no es un número válido, retorna 'Por definir'.
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

<!-- Estilos scoped: solo aplican a este componente -->
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