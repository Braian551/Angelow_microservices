<template>
  <main class="payment-page-root">
    <section class="payment-page-shell">
      <CheckoutFlowHeader
        title="Confirmar y Pagar"
        icon-class="fas fa-credit-card"
        :active-step="3"
      />

      <div class="payment-page-divider" />

      <p v-if="loading" class="loading-box payment-page-status">Preparando tu resumen de pago...</p>
      <p v-else-if="errorMessage && !shippingData" class="error-box payment-page-status">{{ errorMessage }}</p>

      <section v-else-if="!shippingData" class="payment-empty-state">
        <div class="payment-empty-icon">
          <i class="fas fa-file-invoice" />
        </div>
        <h2>No encontramos tu paso de envío</h2>
        <p>Regresa al checkout y selecciona una dirección antes de confirmar el pago.</p>
        <RouterLink :to="{ name: 'shipping' }" class="payment-filled-action">
          <i class="fas fa-arrow-left" />
          <span>Volver al envío</span>
        </RouterLink>
      </section>

      <form v-else class="payment-page-form" @submit.prevent="confirmOrder">
        <p v-if="errorMessage" class="error-box payment-page-status">{{ errorMessage }}</p>
        <p v-if="infoMessage" class="loading-box payment-page-status">{{ infoMessage }}</p>

        <div class="payment-page-layout">
          <div class="payment-page-sections">
            <section class="payment-section-card">
              <header class="payment-section-head">
                <div>
                  <h2>
                    <i class="fas fa-building-columns" />
                    Información para Transferencia
                  </h2>
                  <p>Usa estos datos como guía para registrar tu transferencia y asociarla con tu pedido.</p>
                </div>
              </header>

              <div class="payment-bank-card">
                <PaymentAccountSummaryCard
                  :account="paymentAccount"
                  description="Realiza la transferencia a esta cuenta y luego registra tu banco de origen, la referencia y el comprobante."
                  :amount="summary.total"
                  :reference="form.reference_number"
                  expected-status="Pendiente de verificación"
                  :show-transfer-meta="true"
                />

                <div class="payment-instructions-box">
                  <h4>Cómo completar este paso</h4>
                  <ol>
                    <li>Selecciona el banco desde el cual realizaste la transferencia.</li>
                    <li>Ingresa la referencia exacta del comprobante.</li>
                    <li>Adjunta una imagen o PDF para facilitar la verificación manual.</li>
                    <li>Confirma el pedido y te notificaremos cuando el pago sea validado.</li>
                  </ol>
                </div>
              </div>
            </section>

            <section class="payment-section-card">
              <header class="payment-section-head">
                <div>
                  <h2>
                    <i class="fas fa-file-arrow-up" />
                    Comprobante de Pago
                  </h2>
                  <p>Los campos se validan en tiempo real para que el pedido salga completo como en Angelow.</p>
                </div>
              </header>

              <div class="payment-form-grid">
                <label class="payment-field" for="payment-bank">
                  <span>Banco desde el que transferiste *</span>
                  <select
                    id="payment-bank"
                    v-model="form.bank_code"
                    :class="{ 'payment-field-control--error': fieldErrors.bank_code }"
                    @change="validateField('bank_code')"
                  >
                    <option value="">Selecciona un banco</option>
                    <option v-for="bank in banks" :key="bank.id || bank.bank_code" :value="bank.bank_code">
                      {{ bank.bank_name }}
                    </option>
                  </select>
                  <small class="payment-field-error payment-field-error--fixed">{{ fieldErrors.bank_code }}</small>
                </label>

                <label class="payment-field" for="payment-reference">
                  <span>Número de referencia *</span>
                  <input
                    id="payment-reference"
                    v-model.trim="form.reference_number"
                    type="text"
                    placeholder="Ingresa la referencia exacta"
                    :class="{ 'payment-field-control--error': fieldErrors.reference_number }"
                    @input="validateField('reference_number')"
                  />
                  <small class="payment-field-error payment-field-error--fixed">{{ fieldErrors.reference_number }}</small>
                </label>
              </div>

              <div class="payment-upload-block">
                <label class="payment-upload-label">Comprobante (JPG, PNG o PDF) *</label>

                <button
                  type="button"
                  class="payment-upload-dropzone"
                  :class="{ 'payment-upload-dropzone--error': fieldErrors.payment_proof }"
                  @click="openProofPicker"
                >
                  <input
                    ref="proofInput"
                    type="file"
                    class="payment-upload-native"
                    accept=".jpg,.jpeg,.png,.pdf"
                    @change="onProofSelected"
                  />

                  <template v-if="proofPreview">
                    <img
                      v-if="proofIsImage"
                      :src="proofPreview"
                      alt="Vista previa del comprobante"
                      class="payment-proof-preview"
                    />
                    <div v-else class="payment-proof-placeholder">
                      <i class="fas fa-file-pdf" />
                    </div>

                    <div class="payment-proof-meta">
                      <strong>{{ paymentProofFile?.name }}</strong>
                      <span>{{ paymentProofFileLabel }}</span>
                    </div>
                  </template>

                  <template v-else>
                    <div class="payment-proof-placeholder">
                      <i class="fas fa-cloud-arrow-up" />
                    </div>
                    <div class="payment-proof-meta">
                      <strong>Arrastra o selecciona tu comprobante</strong>
                      <span>Formatos permitidos: JPG, PNG o PDF. Máximo 5MB.</span>
                    </div>
                  </template>
                </button>

                <div class="payment-upload-actions">
                  <button type="button" class="payment-outline-action" @click="openProofPicker">
                    <i class="fas fa-folder-open" />
                    <span>{{ paymentProofFile ? 'Cambiar archivo' : 'Seleccionar archivo' }}</span>
                  </button>

                  <button
                    v-if="paymentProofFile"
                    type="button"
                    class="payment-remove-action"
                    @click="clearProofSelection"
                  >
                    <i class="fas fa-times" />
                    <span>Quitar</span>
                  </button>
                </div>

                <small v-if="fieldErrors.payment_proof" class="payment-field-error">{{ fieldErrors.payment_proof }}</small>
              </div>

              <label class="payment-terms-box">
                <input v-model="form.accept_terms" type="checkbox" @change="validateField('accept_terms')" />
                <span>Acepto los términos y condiciones y autorizo la validación manual de este comprobante.</span>
              </label>
              <small v-if="fieldErrors.accept_terms" class="payment-field-error">{{ fieldErrors.accept_terms }}</small>
            </section>

            <section class="payment-section-card">
              <header class="payment-section-head">
                <div>
                  <h2>
                    <i class="fas fa-bag-shopping" />
                    Resumen de tu Pedido
                  </h2>
                  <p>Revisa dirección, método de envío y productos antes de confirmar.</p>
                </div>
              </header>

              <div class="payment-summary-grid">
                <article class="payment-summary-block">
                  <h3>
                    <i class="fas fa-map-marker-alt" />
                    Dirección de Envío
                  </h3>
                  <p><strong>{{ selectedAddress.recipient_name }}</strong> ({{ selectedAddress.recipient_phone }})</p>
                  <p>{{ buildCheckoutAddressLine(selectedAddress) }}</p>
                  <p v-if="buildCheckoutZoneLine(selectedAddress)">{{ buildCheckoutZoneLine(selectedAddress) }}</p>
                  <p v-if="selectedAddress.delivery_instructions" class="payment-summary-note">
                    {{ selectedAddress.delivery_instructions }}
                  </p>
                </article>

                <article class="payment-summary-block">
                  <h3>
                    <i class="fas fa-truck" />
                    Método de Envío
                  </h3>
                  <p><strong>{{ selectedShippingMethod.name }}</strong></p>
                  <p>{{ selectedShippingMethod.description || 'Entrega segura con seguimiento manual.' }}</p>
                  <p v-if="selectedShippingMethod.delivery_time" class="payment-summary-note">
                    {{ selectedShippingMethod.delivery_time }}
                  </p>

                  <div class="payment-shipping-breakdown">
                    <p>
                      <span>Costo base:</span>
                      <strong>{{ shippingBreakdown.method_cost > 0 ? formatCheckoutPrice(shippingBreakdown.method_cost) : 'Gratis' }}</strong>
                    </p>
                    <p v-if="shippingBreakdown.range_rule_additional_cost > 0" class="payment-shipping-breakdown__extra">
                      <span>{{ shippingRangeSummaryLabel }}</span>
                      <strong>+{{ formatCheckoutPrice(shippingBreakdown.range_rule_additional_cost) }}</strong>
                    </p>
                    <p>
                      <span>Envío total:</span>
                      <strong>{{ summary.shipping_cost > 0 ? formatCheckoutPrice(summary.shipping_cost) : 'Gratis' }}</strong>
                    </p>
                  </div>
                </article>

                <article class="payment-summary-block payment-summary-block--products">
                  <h3>
                    <i class="fas fa-box-open" />
                    Productos ({{ itemCount }})
                  </h3>

                  <div class="payment-products-list">
                    <article
                      v-for="item in orderItems"
                      :key="item.item_id || `${item.product_id}-${item.product_name}`"
                      class="payment-product-row"
                    >
                      <div class="payment-product-media">
                        <img
                          :src="resolveMediaUrl(item.product_image, 'product')"
                          :alt="item.product_name"
                          @error="onItemImageError($event, item.product_image)"
                        />
                      </div>

                      <div class="payment-product-copy">
                        <h4>{{ item.product_name }}</h4>
                        <p v-if="buildCheckoutVariantName(item)">{{ buildCheckoutVariantName(item) }}</p>
                        <div class="payment-product-meta">
                          <span>{{ item.quantity }} x {{ formatCheckoutPrice(item.price) }}</span>
                          <strong>{{ formatCheckoutPrice(item.total || item.price * item.quantity) }}</strong>
                        </div>
                      </div>
                    </article>
                  </div>
                </article>
              </div>
            </section>
          </div>

          <aside class="payment-sidebar-column">
            <div class="payment-sidebar-box">
              <h2>Resumen del Pago</h2>

              <div class="payment-sidebar-rows">
                <div class="payment-sidebar-row">
                  <span>Subtotal</span>
                  <strong>{{ formatCheckoutPrice(summary.subtotal) }}</strong>
                </div>

                <div class="payment-sidebar-row">
                  <span>Envío base (método)</span>
                  <strong>{{ shippingBreakdown.method_cost > 0 ? formatCheckoutPrice(shippingBreakdown.method_cost) : 'Gratis' }}</strong>
                </div>

                <div v-if="shippingBreakdown.range_rule_additional_cost > 0" class="payment-sidebar-row payment-sidebar-row--highlight">
                  <span>{{ shippingRangeSummaryLabel }}</span>
                  <strong>+{{ formatCheckoutPrice(shippingBreakdown.range_rule_additional_cost) }}</strong>
                </div>

                <div class="payment-sidebar-row">
                  <span>Envío total</span>
                  <strong>{{ summary.shipping_cost > 0 ? formatCheckoutPrice(summary.shipping_cost) : 'Gratis' }}</strong>
                </div>

                <div v-if="summary.discount_amount > 0" class="payment-sidebar-row payment-sidebar-row--discount">
                  <span>{{ discountSummaryLabel }}</span>
                  <strong>-{{ formatCheckoutPrice(summary.discount_amount) }}</strong>
                </div>

                <div class="payment-sidebar-row payment-sidebar-row--total">
                  <span>Total a Pagar</span>
                  <strong>{{ formatCheckoutPrice(summary.total) }}</strong>
                </div>
              </div>

              <div class="payment-sidebar-actions">
                <RouterLink :to="{ name: 'shipping' }" class="payment-outline-pill">
                  <i class="fas fa-arrow-left" />
                  <span>Volver al envío</span>
                </RouterLink>

                <button type="submit" class="payment-primary-pill" :disabled="submitting">
                  <span>{{ submitting ? 'Procesando...' : 'Confirmar Pedido' }}</span>
                  <i class="fas fa-check-circle" />
                </button>
              </div>

              <div class="payment-security-note">
                <i class="fas fa-shield-alt" />
                <span>Tu pedido quedará registrado y el comprobante se validará manualmente antes de despacharlo.</span>
              </div>
            </div>
          </aside>
        </div>
      </form>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import CheckoutFlowHeader from '../components/CheckoutFlowHeader.vue'
import PaymentAccountSummaryCard from '../../../components/payments/PaymentAccountSummaryCard.vue'
import { useAppShell } from '../../../composables/useAppShell'
import { useSession } from '../../../composables/useSession'
import { orderHttp } from '../../../services/http'
import { getCart, removeCartItem } from '../../../services/cartApi'
import { createOrder } from '../../../services/orderApi'
import { createPayment, getBanks, getPaymentAccount } from '../../../services/paymentApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import {
  buildCheckoutAddressLine,
  buildCheckoutVariantName,
  buildCheckoutZoneLine,
  formatCheckoutPrice,
  normalizeCheckoutAddress,
  normalizeCheckoutCartItem,
  normalizeCheckoutMethod,
} from '../utils/checkoutHelpers'

const router = useRouter()
const { sessionId, user } = useSession()
const { setCartCount } = useAppShell()

const loading = ref(true)
const submitting = ref(false)
const errorMessage = ref('')
const infoMessage = ref('')
const banks = ref([])
const cart = ref({ items: [], item_count: 0 })
const shippingData = ref(null)
const paymentAccount = ref(null)
const paymentProofFile = ref(null)
const proofPreview = ref('')
const proofInput = ref(null)

const form = ref({
  bank_code: '',
  reference_number: '',
  accept_terms: false,
})

const fieldErrors = ref({
  bank_code: '',
  reference_number: '',
  payment_proof: '',
  accept_terms: '',
})

const selectedAddress = computed(() => normalizeCheckoutAddress(shippingData.value?.selected_address || {}))
const selectedShippingMethod = computed(() => normalizeCheckoutMethod(shippingData.value?.selected_shipping_method || {}))

const orderItems = computed(() => {
  const cartItems = Array.isArray(cart.value?.items) ? cart.value.items : []
  if (cartItems.length > 0) {
    return cartItems.map(normalizeCheckoutCartItem)
  }

  const snapshotItems = Array.isArray(shippingData.value?.items_snapshot) ? shippingData.value.items_snapshot : []
  return snapshotItems.map(normalizeCheckoutCartItem)
})

const itemCount = computed(() => orderItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0))

const storedSummary = computed(() => ({
  subtotal: Number(shippingData.value?.subtotal || 0),
  shipping_cost: Number(shippingData.value?.shipping_cost || 0),
  discount_amount: Number(shippingData.value?.discount_amount || 0),
  total: Number(shippingData.value?.total || 0),
}))

const shippingBreakdown = computed(() => {
  const breakdown = shippingData.value?.shipping_breakdown && typeof shippingData.value.shipping_breakdown === 'object'
    ? shippingData.value.shipping_breakdown
    : {}

  const methodCost = Number(
    breakdown?.method_cost
    ?? selectedShippingMethod.value?.method_cost
    ?? selectedShippingMethod.value?.base_cost
    ?? 0,
  )

  const rangeRuleAdditionalCost = Number(
    breakdown?.range_rule_additional_cost
    ?? selectedShippingMethod.value?.range_rule_additional_cost
    ?? selectedShippingMethod.value?.rule_shipping_cost
    ?? 0,
  )

  const rangeRuleLabel = String(
    breakdown?.range_rule_label
    ?? selectedShippingMethod.value?.range_rule_label
    ?? '',
  ).trim()

  return {
    method_cost: Math.max(0, methodCost),
    range_rule_additional_cost: Math.max(0, rangeRuleAdditionalCost),
    range_rule_label: rangeRuleLabel,
  }
})

const resolvedShippingCost = computed(() => {
  return Math.max(0, shippingBreakdown.value.method_cost + shippingBreakdown.value.range_rule_additional_cost)
})

const summary = computed(() => {
  const subtotalAmount = Math.max(0, Number(storedSummary.value.subtotal || 0))
  const discountAmount = Math.max(0, Number(storedSummary.value.discount_amount || 0))

  return {
    subtotal: subtotalAmount,
    shipping_cost: resolvedShippingCost.value,
    discount_amount: discountAmount,
    total: Math.max(0, subtotalAmount + resolvedShippingCost.value - discountAmount),
  }
})

const shippingRangeSummaryLabel = computed(() => {
  const label = String(shippingBreakdown.value.range_rule_label || '').trim()
  return label ? `Ajuste por rango (${label})` : 'Ajuste por rango de compra'
})

const discountSummaryLabel = computed(() => {
  const discountSource = String(shippingData.value?.discount_source || '').trim()
  const discountCode = String(shippingData.value?.discount_code || '').trim()
  const bulkLabel = String(shippingData.value?.bulk_discount?.label || '').trim()

  if (discountSource === 'code' && discountCode) {
    return `Descuento (${discountCode})`
  }

  if (discountSource === 'bulk') {
    return bulkLabel ? `Descuento por cantidad (${bulkLabel})` : 'Descuento por cantidad'
  }

  return discountCode ? `Descuento (${discountCode})` : 'Descuento'
})

const selectedBank = computed(() => {
  return banks.value.find((bank) => bank.bank_code === form.value.bank_code) || null
})

const proofIsImage = computed(() => Boolean(paymentProofFile.value?.type?.startsWith('image/')))
const paymentProofFileLabel = computed(() => {
  if (!paymentProofFile.value) {
    return ''
  }

  const sizeInMb = Number(paymentProofFile.value.size || 0) / (1024 * 1024)
  return `${sizeInMb.toFixed(2)} MB`
})

onMounted(loadInitialData)
onUnmounted(revokeProofPreview)

async function loadInitialData() {
  loading.value = true
  errorMessage.value = ''

  const rawShipping = localStorage.getItem('angelow_checkout_shipping')
  if (!rawShipping) {
    loading.value = false
    errorMessage.value = 'Debes completar el paso de envío antes de confirmar el pago.'
    return
  }

  shippingData.value = parseStoredJson(rawShipping)

  try {
    const [paymentAccountRes, banksRes, cartRes] = await Promise.all([
      getPaymentAccount().catch(() => null),
      getBanks(),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
    ])

    paymentAccount.value = paymentAccountRes?.data || null

    const apiBanks = Array.isArray(banksRes?.data) ? banksRes.data : []
    banks.value = apiBanks.length > 0
      ? apiBanks
      : [{
        id: 'manual-transfer',
        bank_code: 'manual-transfer',
        bank_name: 'Transferencia manual',
      }]
    cart.value = cartRes?.data && typeof cartRes.data === 'object'
      ? cartRes.data
      : { items: [], item_count: 0 }

    form.value.bank_code = shippingData.value?.bank_code || banks.value[0]?.bank_code || ''
    setCartCount(Number(cart.value?.item_count || itemCount.value))
  } catch {
    banks.value = []
    paymentAccount.value = null
    cart.value = { items: [], item_count: 0 }
    setCartCount(itemCount.value)
    errorMessage.value = 'No pudimos cargar todos los datos del pago, pero aún puedes revisar tu pedido.'
  } finally {
    loading.value = false
  }
}

function validateField(fieldName) {
  if (fieldName === 'bank_code') {
    fieldErrors.value.bank_code = form.value.bank_code
      ? ''
      : 'Selecciona el banco desde el que hiciste la transferencia.'
  }

  if (fieldName === 'reference_number') {
    fieldErrors.value.reference_number = form.value.reference_number.trim().length >= 4
      ? ''
      : 'Ingresa una referencia válida de al menos 4 caracteres.'
  }

  if (fieldName === 'payment_proof') {
    fieldErrors.value.payment_proof = paymentProofFile.value
      ? ''
      : 'Debes adjuntar el comprobante de pago.'
  }

  if (fieldName === 'accept_terms') {
    fieldErrors.value.accept_terms = form.value.accept_terms
      ? ''
      : 'Debes aceptar los términos para continuar.'
  }

  return !fieldErrors.value[fieldName]
}

function validateAllFields() {
  const checks = [
    validateField('bank_code'),
    validateField('reference_number'),
    validateField('payment_proof'),
    validateField('accept_terms'),
  ]

  return checks.every(Boolean)
}

function openProofPicker() {
  proofInput.value?.click()
}

function onProofSelected(event) {
  const file = event?.target?.files?.[0]

  if (!file) {
    clearProofSelection()
    return
  }

  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf']
  if (!allowedTypes.includes(file.type)) {
    paymentProofFile.value = null
    revokeProofPreview()
    fieldErrors.value.payment_proof = 'Solo se aceptan archivos JPG, PNG o PDF.'
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    paymentProofFile.value = null
    revokeProofPreview()
    fieldErrors.value.payment_proof = 'El archivo no puede superar los 5MB.'
    return
  }

  paymentProofFile.value = file
  fieldErrors.value.payment_proof = ''

  revokeProofPreview()
  if (file.type.startsWith('image/')) {
    proofPreview.value = URL.createObjectURL(file)
  } else {
    proofPreview.value = 'pdf'
  }
}

function clearProofSelection() {
  paymentProofFile.value = null
  revokeProofPreview()
  fieldErrors.value.payment_proof = 'Debes adjuntar el comprobante de pago.'
  if (proofInput.value) {
    proofInput.value.value = ''
  }
}

function revokeProofPreview() {
  if (proofPreview.value && proofPreview.value !== 'pdf') {
    URL.revokeObjectURL(proofPreview.value)
  }

  proofPreview.value = ''
}

function createOrderNumber() {
  const now = new Date()
  const datePart = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}`
  const randomPart = Math.random().toString(16).slice(2, 8).toUpperCase()
  return `ORD${datePart}${randomPart}`
}

function normalizeCheckoutUserId() {
  const candidate = user.value?.id ?? shippingData.value?.user_id ?? null
  const normalized = String(candidate ?? '').trim()
  return normalized ? normalized : null
}

function resolveCheckoutVariantId(item = {}) {
  const candidates = [
    item?.size_variant_id,
    item?.sizeVariantId,
    item?.variant_id,
    item?.variantId,
    item?.size_variant?.id,
    item?.variant?.id,
  ]

  for (const candidate of candidates) {
    const parsed = Number(candidate)
    if (Number.isFinite(parsed) && parsed > 0) {
      return parsed
    }
  }

  return null
}

function resolveCheckoutColorVariantId(item = {}) {
  const candidates = [
    item?.color_variant_id,
    item?.colorVariantId,
    item?.color_variant?.id,
    item?.color?.id,
  ]

  for (const candidate of candidates) {
    const parsed = Number(candidate)
    if (Number.isFinite(parsed) && parsed > 0) {
      return parsed
    }
  }

  return null
}

function resolveCheckoutApiErrorMessage(error) {
  const apiData = error?.response?.data
  const apiMessage = String(apiData?.message || '').trim()
  if (apiMessage) {
    return apiMessage
  }

  if (apiData?.errors && typeof apiData.errors === 'object') {
    for (const messages of Object.values(apiData.errors)) {
      if (!Array.isArray(messages) || messages.length === 0) {
        continue
      }

      const firstMessage = String(messages[0] || '').trim()
      if (firstMessage) {
        return firstMessage
      }
    }
  }

  const genericMessage = String(error?.message || '').trim()
  return genericMessage || ''
}

async function confirmOrder() {
  errorMessage.value = ''
  infoMessage.value = ''

  if (!shippingData.value) {
    errorMessage.value = 'Faltan los datos del paso de envío.'
    return
  }

  if (!validateAllFields()) {
    errorMessage.value = 'Corrige los campos marcados antes de confirmar el pedido.'
    return
  }

  if (orderItems.value.length === 0) {
    errorMessage.value = 'No encontramos productos en tu pedido. Regresa al carrito para continuar.'
    return
  }

  submitting.value = true
  infoMessage.value = 'Registrando tu pedido...'

  const normalizedUserId = normalizeCheckoutUserId()
  let createdOrderId = null

  try {
    const orderNumber = createOrderNumber()
    const orderItemsPayload = orderItems.value.map((item) => ({
      product_id: Number(item.product_id || 0),
      color_variant_id: resolveCheckoutColorVariantId(item),
      size_variant_id: resolveCheckoutVariantId(item),
      product_name: item.product_name,
      variant_name: buildCheckoutVariantName(item),
      price: Number(item.price || 0),
      quantity: Number(item.quantity || 1),
      total: Number(item.total || Number(item.price || 0) * Number(item.quantity || 1)),
    }))

    // Evita enviar la orden cuando un ítem no trae variante válida y da feedback claro al usuario.
    if (orderItemsPayload.some((item) => !item.size_variant_id)) {
      errorMessage.value = 'Uno de los productos de tu carrito no tiene una talla válida. Actualiza el carrito y vuelve a intentarlo.'
      return
    }

    const orderPayload = {
      order_number: orderNumber,
      user_id: normalizedUserId,
      subtotal: summary.value.subtotal,
      shipping_cost: summary.value.shipping_cost,
      discount_amount: summary.value.discount_amount,
      discount_code: shippingData.value?.discount_code || null,
      discount_source: shippingData.value?.discount_source || null,
      total: summary.value.total,
      status: 'pending',
      payment_method: 'transfer',
      payment_status: 'pending',
      shipping_address: buildCheckoutAddressLine(selectedAddress.value),
      shipping_city: selectedAddress.value.city || selectedAddress.value.neighborhood || 'Medellín',
      shipping_method_id: selectedShippingMethod.value.id || null,
      billing_name: selectedAddress.value.recipient_name || user.value?.name || '',
      billing_document: '',
      billing_email: user.value?.email || shippingData.value?.user_email || '',
      billing_phone: selectedAddress.value.recipient_phone || '',
      billing_address: buildCheckoutAddressLine(selectedAddress.value),
      billing_city: selectedAddress.value.city || selectedAddress.value.neighborhood || 'Medellín',
      notes: shippingData.value?.notes || null,
      items: orderItemsPayload,
    }

    const orderRes = await createOrder(orderPayload)
    const orderId = Number(orderRes?.id || 0)
    if (!orderId) {
      throw new Error('No se pudo crear la orden')
    }
    createdOrderId = orderId

    const paymentPayload = new FormData()
    paymentPayload.append('order_id', String(orderId))

    if (normalizedUserId) {
      paymentPayload.append('user_id', normalizedUserId)
    }

    paymentPayload.append('amount', String(summary.value.total))
    paymentPayload.append('reference_number', form.value.reference_number.trim())
    paymentPayload.append('bank_code', form.value.bank_code)
    paymentPayload.append('payment_method', 'transfer')

    if (paymentProofFile.value) {
      paymentPayload.append('payment_proof', paymentProofFile.value)
      paymentPayload.append('payment_proof_name', paymentProofFile.value.name)
    }

    const paymentRes = await createPayment(paymentPayload)

    let confirmationEmailWarning = ''
    try {
      await orderHttp.post(`/orders/${orderId}/send-confirmation`, {
        source: 'microservice',
        payment_reference: form.value.reference_number.trim(),
        bank_name: selectedBank.value?.bank_name || 'Transferencia manual',
        payment_proof_name: paymentProofFile.value?.name || null,
      })
    } catch {
      confirmationEmailWarning = 'El pedido quedó registrado, pero no pudimos enviar el correo de confirmación en este momento.'
    }

    await Promise.allSettled(
      orderItems.value
        .filter((item) => Number(item.item_id || 0) > 0)
        .map((item) => removeCartItem(item.item_id)),
    )

    setCartCount(0)

    localStorage.setItem('angelow_checkout_result', JSON.stringify({
      order_id: orderId,
      order_number: orderNumber,
      payment_id: paymentRes?.id || null,
      reference_number: form.value.reference_number.trim(),
      created_at: new Date().toISOString(),
      subtotal: summary.value.subtotal,
      shipping_cost: summary.value.shipping_cost,
      discount_amount: summary.value.discount_amount,
      discount_source: shippingData.value?.discount_source || null,
      discount_code: shippingData.value?.discount_code || null,
      bulk_discount: shippingData.value?.bulk_discount || null,
      total: summary.value.total,
      payment_bank_name: selectedBank.value?.bank_name || '',
      payment_proof_name: paymentProofFile.value?.name || '',
      shipping: {
        recipient_name: selectedAddress.value.recipient_name,
        recipient_phone: selectedAddress.value.recipient_phone,
        address: buildCheckoutAddressLine(selectedAddress.value),
        zone: buildCheckoutZoneLine(selectedAddress.value),
        instructions: selectedAddress.value.delivery_instructions,
        method_name: selectedShippingMethod.value.name,
        method_description: selectedShippingMethod.value.description,
        method_eta: selectedShippingMethod.value.delivery_time,
        method_cost: shippingBreakdown.value.method_cost,
        range_rule_additional_cost: shippingBreakdown.value.range_rule_additional_cost,
        range_rule_label: shippingBreakdown.value.range_rule_label,
        total_shipping_cost: summary.value.shipping_cost,
      },
      billing: {
        name: selectedAddress.value.recipient_name || user.value?.name || '',
        email: user.value?.email || shippingData.value?.user_email || '',
        phone: selectedAddress.value.recipient_phone || '',
        address: buildCheckoutAddressLine(selectedAddress.value),
        city: selectedAddress.value.city || selectedAddress.value.neighborhood || 'Medellín',
      },
      items: orderItems.value,
    }))

    if (confirmationEmailWarning) {
      localStorage.setItem('angelow_checkout_warning', confirmationEmailWarning)
    } else {
      localStorage.removeItem('angelow_checkout_warning')
    }

    router.push({ name: 'confirmation' })
  } catch (error) {
    const apiMessage = resolveCheckoutApiErrorMessage(error)

    if (createdOrderId) {
      errorMessage.value = apiMessage || 'El pedido quedó registrado, pero no pudimos guardar el pago. Revisa tu pedido en tu cuenta para continuar.'
    } else {
      errorMessage.value = apiMessage || 'No se pudo confirmar el pedido.'
    }
  } finally {
    infoMessage.value = ''
    submitting.value = false
  }
}

function onItemImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'product')
}

function parseStoredJson(rawValue) {
  try {
    return JSON.parse(rawValue)
  } catch {
    return null
  }
}
</script>

<style scoped>
.payment-page-root {
  width: 100%;
  padding: 2rem 0 3.5rem;
}

.payment-page-shell {
  width: min(100%, 1480px);
  margin: 0 auto;
  padding: 0 1.5rem;
  animation: paymentPageFadeIn 0.4s ease;
}

.payment-page-divider {
  height: 1px;
  background: #e0e0e0;
  margin-bottom: 2rem;
}

.payment-page-status {
  margin-bottom: 1.4rem;
}

.payment-page-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 36rem;
  gap: 3rem;
  align-items: start;
}

.payment-page-sections {
  display: grid;
  gap: 2.5rem;
}

.payment-section-card,
.payment-sidebar-box,
.payment-empty-state {
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.payment-section-card {
  padding: 2.4rem;
  transition: box-shadow 0.3s ease;
}

.payment-section-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.payment-section-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.4rem;
  margin-bottom: 2rem;
  padding-bottom: 1.6rem;
  border-bottom: 1px solid #e0e0e0;
}

.payment-section-head h2 {
  margin: 0 0 0.6rem;
  color: #333333;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.payment-section-head h2 i {
  color: #0077b6;
}

.payment-section-head p {
  margin: 0;
  color: #666666;
  font-size: 1.45rem;
  line-height: 1.55;
}

.payment-filled-action,
.payment-outline-action,
.payment-outline-pill,
.payment-primary-pill,
.payment-remove-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.85rem;
  min-height: 5rem;
  border-radius: 999px;
  text-decoration: none;
  font-size: 1.5rem;
  font-weight: 700;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.payment-filled-action,
.payment-primary-pill {
  padding: 0.9rem 1.8rem;
  border: none;
  background: #0077b6;
  color: #ffffff;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.payment-filled-action:hover,
.payment-primary-pill:hover {
  background: #005b8c;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}

.payment-outline-action,
.payment-outline-pill {
  padding: 0.9rem 1.8rem;
  border: 2px solid #0077b6;
  color: #0077b6;
  background: #ffffff;
}

.payment-outline-action:hover,
.payment-outline-pill:hover {
  transform: translateX(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.payment-remove-action {
  padding: 0.9rem 1.4rem;
  border: none;
  background: #ff3333;
  color: #ffffff;
  cursor: pointer;
}

.payment-remove-action:hover {
  background: #d52c2c;
}

.payment-bank-card {
  padding: 2rem;
  border-radius: 2rem;
  background: linear-gradient(180deg, rgba(0, 119, 182, 0.08), rgba(0, 119, 182, 0.02));
  border: 1px solid rgba(0, 119, 182, 0.12);
}

.payment-bank-headline {
  display: flex;
  gap: 1.4rem;
  align-items: center;
  margin-bottom: 1.8rem;
}

.payment-bank-icon {
  width: 5.6rem;
  height: 5.6rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.12);
  color: #0077b6;
  font-size: 2rem;
}

.payment-bank-copy h3 {
  margin: 0 0 0.45rem;
  color: #005b8c;
  font-size: 2rem;
  font-weight: 700;
}

.payment-bank-copy p {
  margin: 0;
  color: #55616d;
  font-size: 1.45rem;
  line-height: 1.55;
}

.payment-bank-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.8rem;
}

.payment-bank-item {
  padding: 1.3rem 1.4rem;
  border-radius: 1.6rem;
  background: #ffffff;
  border: 1px solid #dbe5ed;
  display: grid;
  gap: 0.45rem;
}

.payment-bank-label {
  color: #6b7280;
  font-size: 1.18rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 700;
}

.payment-bank-item strong {
  color: #334155;
  font-size: 1.45rem;
}

.payment-instructions-box {
  padding: 1.5rem 1.6rem;
  border-radius: 1.8rem;
  background: #ffffff;
  border: 1px solid #dbe5ed;
}

.payment-instructions-box h4 {
  margin: 0 0 1rem;
  color: #333333;
  font-size: 1.45rem;
  font-weight: 700;
}

.payment-instructions-box ol {
  margin: 0;
  padding-left: 1.8rem;
  color: #555f69;
  font-size: 1.35rem;
  line-height: 1.7;
}

.payment-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.6rem;
}

.payment-field {
  display: grid;
  gap: 0.6rem;
}

.payment-field span,
.payment-upload-label {
  color: #334155;
  font-size: 1.38rem;
  font-weight: 700;
}

.payment-field input,
.payment-field select {
  width: 100%;
  min-height: 5.2rem;
  padding: 1.1rem 1.3rem;
  border: 1px solid #d6dce2;
  border-radius: 1.4rem;
  color: #333333;
  font-size: 1.4rem;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.payment-field input:focus,
.payment-field select:focus {
  outline: none;
  border-color: #0077b6;
  box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.12);
}

.payment-field-control--error,
.payment-upload-dropzone--error {
  border-color: #d52c2c !important;
}

.payment-field-error {
  margin-top: 0.4rem;
  color: #c62828;
  font-size: 1.22rem;
  font-weight: 600;
}

.payment-field-error--fixed {
  min-height: 1.8rem;
  display: block;
}

.payment-upload-block {
  display: grid;
  gap: 1rem;
}

.payment-upload-dropzone {
  position: relative;
  width: 100%;
  border: 1px dashed #b9c6d3;
  border-radius: 1.8rem;
  padding: 1.5rem;
  background: #f9fbfd;
  display: flex;
  align-items: center;
  gap: 1.4rem;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.25s ease, background 0.25s ease;
}

.payment-upload-dropzone:hover {
  border-color: #0077b6;
  background: #f2f8fc;
}

.payment-upload-native {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.payment-proof-preview,
.payment-proof-placeholder {
  width: 8rem;
  height: 8rem;
  flex-shrink: 0;
  border-radius: 1.4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.08);
  border: 1px solid #dbe5ed;
  overflow: hidden;
}

.payment-proof-preview {
  object-fit: cover;
}

.payment-proof-placeholder {
  color: #0077b6;
  font-size: 2.2rem;
}

.payment-proof-meta {
  display: grid;
  gap: 0.35rem;
}

.payment-proof-meta strong {
  color: #334155;
  font-size: 1.45rem;
}

.payment-proof-meta span {
  color: #64748b;
  font-size: 1.3rem;
}

.payment-upload-actions {
  display: flex;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.payment-terms-box {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  margin-top: 1.2rem;
  padding: 1.3rem 1.4rem;
  border-radius: 1.6rem;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  color: #55616d;
  font-size: 1.34rem;
  line-height: 1.6;
}

.payment-terms-box input {
  margin-top: 0.25rem;
}

.payment-summary-grid {
  display: grid;
  gap: 1.2rem;
}

.payment-summary-block {
  padding: 1.7rem;
  border-radius: 1.8rem;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
}

.payment-summary-block h3 {
  margin: 0 0 1rem;
  color: #333333;
  font-size: 1.55rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.payment-summary-block h3 i {
  color: #0077b6;
}

.payment-summary-block p {
  margin: 0 0 0.5rem;
  color: #55616d;
  font-size: 1.35rem;
  line-height: 1.6;
}

.payment-summary-note {
  color: #0077b6 !important;
  font-weight: 600;
}

.payment-shipping-breakdown {
  margin-top: 1rem;
  display: grid;
  gap: 0.55rem;
}

.payment-shipping-breakdown p {
  margin: 0;
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: #5a6571;
  font-size: 1.28rem;
}

.payment-shipping-breakdown p strong {
  color: #0f7abf;
}

.payment-shipping-breakdown__extra span,
.payment-shipping-breakdown__extra strong {
  color: #005b8c !important;
  font-weight: 700;
}

.payment-products-list {
  display: grid;
  gap: 1rem;
}

.payment-product-row {
  display: flex;
  gap: 1rem;
  padding: 1.1rem;
  border-radius: 1.5rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.payment-product-media {
  width: 6.2rem;
  height: 6.2rem;
  overflow: hidden;
  border-radius: 1.3rem;
  flex-shrink: 0;
  border: 1px solid #dbe5ed;
  background: #ffffff;
}

.payment-product-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.payment-product-copy {
  min-width: 0;
  flex: 1;
}

.payment-product-copy h4 {
  margin: 0 0 0.45rem;
  color: #334155;
  font-size: 1.45rem;
  font-weight: 700;
}

.payment-product-copy p {
  margin: 0 0 0.45rem;
  color: #64748b;
  font-size: 1.23rem;
}

.payment-product-meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: #55616d;
  font-size: 1.2rem;
}

.payment-product-meta strong {
  color: #0077b6;
  font-size: 1.28rem;
}

.payment-sidebar-column {
  position: sticky;
  top: 12rem;
}

.payment-sidebar-box {
  padding: 2.5rem;
}

.payment-sidebar-box h2 {
  margin: 0 0 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  color: #333333;
  font-size: 1.8rem;
  font-weight: 700;
}

.payment-sidebar-rows {
  display: grid;
  gap: 1.2rem;
}

.payment-sidebar-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  color: #444444;
  font-size: 1.46rem;
}

.payment-sidebar-row strong {
  color: #0077b6;
  text-align: right;
}

.payment-sidebar-row--discount strong {
  color: #4bb543;
}

.payment-sidebar-row--highlight span,
.payment-sidebar-row--highlight strong {
  color: #005b8c;
  font-weight: 700;
}

.payment-sidebar-row--total {
  padding-top: 1.5rem;
  border-top: 2px dashed #e0e0e0;
  color: #005b8c;
  font-size: 1.8rem;
  font-weight: 700;
}

.payment-sidebar-row--total strong {
  color: #005b8c;
}

.payment-sidebar-actions {
  display: grid;
  gap: 1.2rem;
  margin: 2rem 0;
}

.payment-primary-pill:disabled {
  opacity: 0.75;
  cursor: wait;
  transform: none;
}

.payment-security-note {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  padding: 1.4rem;
  border-radius: 1.6rem;
  background: rgba(0, 119, 182, 0.05);
  border: 1px solid #e0e0e0;
  color: #666666;
  font-size: 1.3rem;
  line-height: 1.55;
}

.payment-security-note i {
  color: #0077b6;
  font-size: 1.6rem;
  margin-top: 0.1rem;
}

.payment-empty-state {
  max-width: 60rem;
  margin: 1rem auto 0;
  padding: 4rem 2rem;
  text-align: center;
}

.payment-empty-icon {
  width: 8.4rem;
  height: 8.4rem;
  margin: 0 auto 1.8rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.12);
  color: #0077b6;
  font-size: 2.8rem;
}

.payment-empty-state h2 {
  margin: 0 0 1rem;
  color: #333333;
  font-size: 2.2rem;
  font-weight: 700;
}

.payment-empty-state p {
  margin: 0 0 1.6rem;
  color: #666666;
  font-size: 1.5rem;
  line-height: 1.6;
}

@keyframes paymentPageFadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 1080px) {
  .payment-page-layout {
    grid-template-columns: 1fr;
  }

  .payment-sidebar-column {
    position: static;
    top: auto;
  }
}

@media (max-width: 768px) {
  .payment-page-root {
    padding-top: 1.4rem;
    padding-bottom: 2.4rem;
  }

  .payment-page-shell {
    padding: 0 1rem;
  }

  .payment-section-card,
  .payment-sidebar-box,
  .payment-empty-state {
    padding: 1.8rem 1.4rem;
  }

  .payment-section-head {
    flex-direction: column;
  }

  .payment-bank-grid,
  .payment-form-grid {
    grid-template-columns: 1fr;
  }

  .payment-upload-dropzone {
    flex-direction: column;
    text-align: center;
  }
}
</style>
