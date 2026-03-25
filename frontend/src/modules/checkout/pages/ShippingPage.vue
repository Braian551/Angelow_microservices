<template>
  <SiteHeader :settings="settings" :cart-count="cartCount" />

  <main class="section-container checkout-page">
    <h1 class="section-title">Datos de envio</h1>
    <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <div class="checkout-grid">
      <form class="checkout-form" @submit.prevent="continueToPayment">
        <label>
          Nombre completo
          <input v-model="form.full_name" required />
        </label>
        <label>
          Telefono
          <input v-model="form.phone" required />
        </label>
        <label>
          Ciudad
          <input v-model="form.city" required />
        </label>
        <label>
          Direccion
          <textarea v-model="form.address" required />
        </label>
        <label>
          Metodo de envio
          <select v-model.number="form.shipping_method_id" required>
            <option v-for="method in methods" :key="method.id" :value="method.id">
              {{ method.name }} - {{ formatPrice(method.base_cost) }}
            </option>
          </select>
        </label>
        <button class="btn" type="submit">Continuar a pago</button>
      </form>

      <aside class="checkout-summary">
        <h2>Resumen</h2>
        <p>Subtotal: {{ formatPrice(subtotal) }}</p>
        <p>Envio estimado: {{ formatPrice(shippingCost) }}</p>
        <p>Total estimado: {{ formatPrice(subtotal + shippingCost) }}</p>
      </aside>
    </div>
  </main>

  <SiteFooter :settings="settings" />
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import SiteHeader from '../../../components/layout/SiteHeader.vue'
import SiteFooter from '../../../components/layout/SiteFooter.vue'
import { getHomeData } from '../../../services/catalogApi'
import { getCart } from '../../../services/cartApi'
import { estimateShipping, getShippingMethods } from '../../../services/shippingApi'
import { useSession } from '../../../composables/useSession'

const router = useRouter()
const { sessionId, user } = useSession()

const settings = ref({})
const methods = ref([])
const cartCount = ref(0)
const subtotal = ref(0)
const shippingCost = ref(0)
const errorMessage = ref('')

const form = ref({
  full_name: '',
  phone: '',
  city: 'Medellin',
  address: '',
  shipping_method_id: null,
})

watch(
  () => form.value.city,
  async () => {
    await calculateShipping()
  },
)

async function loadInitialData() {
  try {
    const [homeRes, methodsRes, cartRes] = await Promise.all([
      getHomeData(),
      getShippingMethods(),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
    ])

    settings.value = homeRes?.data?.settings || {}
    methods.value = methodsRes?.data || []
    form.value.shipping_method_id = methods.value[0]?.id || null
    cartCount.value = Number(cartRes?.data?.item_count || 0)
    subtotal.value = Number(cartRes?.data?.subtotal || 0)

    await calculateShipping()
  } catch {
    errorMessage.value = 'No se pudo cargar el checkout.'
  }
}

async function calculateShipping() {
  try {
    const response = await estimateShipping({
      subtotal: subtotal.value,
      city: form.value.city,
    })
    shippingCost.value = Number(response?.shipping_cost || 0)
  } catch {
    shippingCost.value = 0
  }
}

function continueToPayment() {
  const payload = {
    ...form.value,
    subtotal: subtotal.value,
    shipping_cost: shippingCost.value,
    total: subtotal.value + shippingCost.value,
    session_id: sessionId.value,
    user_id: user.value?.id || null,
  }

  localStorage.setItem('angelow_checkout_shipping', JSON.stringify(payload))
  router.push({ name: 'payment' })
}

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

onMounted(loadInitialData)
</script>

<style scoped>
.checkout-grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
  gap: 1.5rem;
}

.checkout-form {
  display: grid;
  gap: 0.9rem;
}

.checkout-form label {
  display: grid;
  gap: 0.45rem;
}

.checkout-form input,
.checkout-form textarea,
.checkout-form select {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0.7rem;
}

.checkout-summary {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  height: fit-content;
}

@media (max-width: 900px) {
  .checkout-grid {
    grid-template-columns: 1fr;
  }
}
</style>
