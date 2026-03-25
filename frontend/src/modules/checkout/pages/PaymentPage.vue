<template>
  <SiteHeader :settings="settings" :cart-count="cartCount" />

  <main class="section-container checkout-page">
    <h1 class="section-title">Pago</h1>
    <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>
    <p v-if="infoMessage" class="loading-box">{{ infoMessage }}</p>

    <div class="checkout-grid">
      <form class="checkout-form" @submit.prevent="confirmOrder">
        <label>
          Banco
          <select v-model="form.bank_code" required>
            <option v-for="bank in banks" :key="bank.id" :value="bank.bank_code">
              {{ bank.bank_name }}
            </option>
          </select>
        </label>
        <label>
          Referencia de pago
          <input v-model="form.reference_number" required />
        </label>
        <label>
          Codigo de descuento (opcional)
          <input v-model="form.discount_code" />
        </label>
        <button type="button" class="btn" @click="applyDiscount">Validar descuento</button>
        <button class="btn" type="submit">Confirmar pedido</button>
      </form>

      <aside class="checkout-summary">
        <h2>Resumen final</h2>
        <p>Subtotal: {{ formatPrice(summary.subtotal) }}</p>
        <p>Envio: {{ formatPrice(summary.shipping_cost) }}</p>
        <p>Descuento: -{{ formatPrice(summary.discount_amount) }}</p>
        <p>Total: {{ formatPrice(summary.total) }}</p>
      </aside>
    </div>
  </main>

  <SiteFooter :settings="settings" />
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import SiteHeader from '../../../components/layout/SiteHeader.vue'
import SiteFooter from '../../../components/layout/SiteFooter.vue'
import { getHomeData } from '../../../services/catalogApi'
import { getCart } from '../../../services/cartApi'
import { validateDiscountCode } from '../../../services/discountApi'
import { createNotification } from '../../../services/notificationApi'
import { createOrder } from '../../../services/orderApi'
import { getBanks, createPayment } from '../../../services/paymentApi'
import { useSession } from '../../../composables/useSession'

const router = useRouter()
const { sessionId, user } = useSession()

const settings = ref({})
const banks = ref([])
const cartCount = ref(0)
const errorMessage = ref('')
const infoMessage = ref('')

const shippingData = ref(null)
const summary = ref({
  subtotal: 0,
  shipping_cost: 0,
  discount_amount: 0,
  total: 0,
})

const form = ref({
  bank_code: '',
  reference_number: '',
  discount_code: '',
})

function createOrderNumber() {
  const now = new Date()
  const datePart = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}`
  const randomPart = Math.random().toString(16).slice(2, 8).toUpperCase()
  return `ORD${datePart}${randomPart}`
}

async function loadInitialData() {
  const rawShipping = localStorage.getItem('angelow_checkout_shipping')
  if (!rawShipping) {
    errorMessage.value = 'Debes completar los datos de envio primero.'
    return
  }

  shippingData.value = JSON.parse(rawShipping)
  summary.value.subtotal = Number(shippingData.value.subtotal || 0)
  summary.value.shipping_cost = Number(shippingData.value.shipping_cost || 0)
  summary.value.discount_amount = 0
  summary.value.total = Number(shippingData.value.total || 0)

  try {
    const [homeRes, banksRes, cartRes] = await Promise.all([
      getHomeData(),
      getBanks(),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
    ])
    settings.value = homeRes?.data?.settings || {}
    banks.value = banksRes?.data || []
    form.value.bank_code = banks.value[0]?.bank_code || ''
    cartCount.value = Number(cartRes?.data?.item_count || 0)
  } catch {
    errorMessage.value = 'No se pudo cargar la informacion de pago.'
  }
}

async function applyDiscount() {
  if (!form.value.discount_code) return
  errorMessage.value = ''
  infoMessage.value = ''

  try {
    const result = await validateDiscountCode({
      code: form.value.discount_code,
      user_id: user.value?.id || null,
      order_total: summary.value.subtotal,
    })
    if (!result?.valid || !result?.discount) {
      errorMessage.value = 'Codigo no valido.'
      return
    }

    const discountValue = Number(result.discount.discount_value || 0)
    const amount = Math.round((summary.value.subtotal * discountValue) / 100)
    summary.value.discount_amount = amount
    summary.value.total = Math.max(0, summary.value.subtotal + summary.value.shipping_cost - amount)
    infoMessage.value = `Descuento aplicado: ${discountValue}%`
  } catch {
    errorMessage.value = 'No fue posible validar el codigo.'
  }
}

async function confirmOrder() {
  errorMessage.value = ''
  infoMessage.value = ''

  if (!shippingData.value) {
    errorMessage.value = 'Faltan datos de envio.'
    return
  }

  try {
    const orderPayload = {
      order_number: createOrderNumber(),
      user_id: user.value?.id || null,
      subtotal: summary.value.subtotal,
      total: summary.value.total,
      status: 'pending',
      shipping_address: shippingData.value.address,
      shipping_city: shippingData.value.city,
      payment_method: 'transfer',
    }

    const orderRes = await createOrder(orderPayload)
    const orderId = Number(orderRes?.id || 0)
    if (!orderId) {
      throw new Error('No se pudo crear la orden')
    }

    const paymentRes = await createPayment({
      order_id: orderId,
      user_id: user.value?.id || null,
      amount: summary.value.total,
      reference_number: form.value.reference_number,
      payment_proof: null,
    })

    if (user.value?.id) {
      await createNotification({
        user_id: user.value.id,
        type_id: 1,
        title: `Pedido creado ${orderPayload.order_number}`,
        message: 'Tu pedido fue registrado y esta pendiente de verificacion de pago.',
        related_entity_type: 'order',
        related_entity_id: orderId,
      })
    }

    localStorage.setItem('angelow_checkout_result', JSON.stringify({
      order_id: orderId,
      order_number: orderPayload.order_number,
      payment_id: paymentRes?.id || null,
      total: summary.value.total,
      reference_number: form.value.reference_number,
    }))

    router.push({ name: 'confirmation' })
  } catch {
    errorMessage.value = 'No se pudo confirmar el pedido.'
  }
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
