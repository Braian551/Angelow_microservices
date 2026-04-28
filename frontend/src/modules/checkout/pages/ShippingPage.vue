<template>
  <main class="shipping-page-root">
    <section class="shipping-page-shell">
      <CheckoutFlowHeader
        title="Finalizar Compra"
        icon-class="fas fa-shipping-fast"
        :active-step="2"
      />

      <div class="shipping-page-divider" />

      <p v-if="loading" class="loading-box shipping-page-status">Cargando datos de envío...</p>
      <p v-else-if="errorMessage" class="error-box shipping-page-status">{{ errorMessage }}</p>

      <section v-else-if="cartItems.length === 0" class="shipping-empty-state">
        <div class="shipping-empty-icon">
          <i class="fas fa-shopping-bag" />
        </div>
        <h2>Tu carrito está vacío</h2>
        <p>Agrega productos para continuar con el proceso de compra.</p>
        <RouterLink :to="{ name: 'store' }" class="shipping-filled-action">
          <i class="fas fa-arrow-left" />
          <span>Ir a la tienda</span>
        </RouterLink>
      </section>

      <form v-else class="shipping-page-form" @submit.prevent="continueToPayment">
        <div class="shipping-page-layout">
          <div class="shipping-page-sections">
            <section class="shipping-section-card">
              <header class="shipping-section-head">
                <div>
                  <h2>
                    <i class="fas fa-map-marker-alt" />
                    Dirección de Envío
                  </h2>
                  <p>Selecciona la dirección donde quieres recibir tu pedido.</p>
                </div>

                <RouterLink :to="{ name: 'account-addresses' }" class="shipping-outline-action">
                  <i class="fas fa-plus" />
                  <span>Gestionar direcciones</span>
                </RouterLink>
              </header>

              <div v-if="addresses.length === 0" class="shipping-empty-block">
                <div class="shipping-empty-block__icon">
                  <i class="fas fa-map-marker-alt" />
                </div>
                <h3>No tienes direcciones guardadas</h3>
                <p>Agrega una dirección para continuar con una experiencia igual a Angelow.</p>
                <RouterLink :to="{ name: 'account-addresses' }" class="shipping-filled-action shipping-filled-action--compact">
                  <i class="fas fa-plus" />
                  <span>Agregar dirección</span>
                </RouterLink>
              </div>

              <div v-else class="shipping-address-list">
                <button
                  v-for="address in addresses"
                  :key="address.id"
                  type="button"
                  class="shipping-address-card"
                  :class="{
                    'shipping-address-card--default': address.is_default,
                    'shipping-address-card--selected': address.id === selectedAddressId,
                  }"
                  :aria-pressed="address.id === selectedAddressId"
                  @click="selectAddress(address.id)"
                >
                  <div class="shipping-address-headline">
                    <div class="shipping-address-icon">
                      <i :class="iconCheckoutAddressType(address.address_type)" />
                    </div>

                    <div class="shipping-address-copy">
                      <h3>{{ address.alias }}</h3>
                      <span class="shipping-address-type">
                        {{ labelCheckoutAddressType(address.address_type) }}
                      </span>
                    </div>

                    <span v-if="address.is_default" class="shipping-address-badge">
                      <i class="fas fa-star" />
                      Principal
                    </span>
                  </div>

                  <div class="shipping-address-details">
                    <div class="shipping-address-line">
                      <i class="fas fa-user" />
                      <p>{{ address.recipient_name }} ({{ address.recipient_phone }})</p>
                    </div>

                    <div class="shipping-address-line">
                      <i class="fas fa-location-dot" />
                      <p>{{ buildCheckoutAddressLine(address) }}</p>
                    </div>

                    <div v-if="buildCheckoutZoneLine(address)" class="shipping-address-line">
                      <i class="fas fa-city" />
                      <p>{{ buildCheckoutZoneLine(address) }}</p>
                    </div>

                    <div class="shipping-address-line">
                      <i class="fas fa-building" />
                      <p>{{ labelCheckoutBuilding(address) }}</p>
                    </div>

                    <div v-if="address.delivery_instructions" class="shipping-address-line">
                      <i class="fas fa-circle-info" />
                      <p>{{ address.delivery_instructions }}</p>
                    </div>
                  </div>
                </button>
              </div>

              <p v-if="selectionErrors.address" class="shipping-field-error">
                {{ selectionErrors.address }}
              </p>
            </section>

            <section class="shipping-section-card">
              <header class="shipping-section-head">
                <div>
                  <h2>
                    <i class="fas fa-truck" />
                    Método de Envío
                  </h2>
                  <p>Elige el tiempo y costo de entrega que mejor se adapte a tu compra.</p>
                </div>
              </header>

              <div v-if="methods.length === 0" class="shipping-empty-block shipping-empty-block--soft">
                <div class="shipping-empty-block__icon">
                  <i class="fas fa-truck-fast" />
                </div>
                <h3>No hay métodos de envío disponibles</h3>
                <p>Vuelve a intentarlo en unos minutos o contacta soporte.</p>
              </div>

              <div v-else class="shipping-method-list">
                <button
                  v-for="method in methods"
                  :key="method.id"
                  type="button"
                  class="shipping-method-card"
                  :class="{ 'shipping-method-card--selected': method.id === selectedShippingMethodId }"
                  :aria-pressed="method.id === selectedShippingMethodId"
                  @click="selectShippingMethod(method.id)"
                >
                  <div class="shipping-method-icon">
                    <i class="fas fa-box-open" />
                  </div>

                  <div class="shipping-method-copy">
                    <h3>{{ method.name }}</h3>
                    <p>{{ method.description || 'Entrega segura con seguimiento de tu pedido.' }}</p>
                    <span v-if="method.delivery_time" class="shipping-method-time">
                      <i class="fas fa-clock" />
                      {{ method.delivery_time }}
                    </span>

                    <div class="shipping-method-breakdown">
                      <span>
                        Costo base: {{ methodBaseCost(method) > 0 ? formatCheckoutPrice(methodBaseCost(method)) : 'Gratis' }}
                      </span>
                      <span v-if="methodRangeAdditionalCost(method) > 0" class="shipping-method-breakdown__extra">
                        +{{ formatCheckoutPrice(methodRangeAdditionalCost(method)) }} por rango {{ methodRangeRuleLabel(method) || 'de compra' }}
                      </span>
                    </div>
                  </div>

                  <div class="shipping-method-cost">
                    <small>Total envío</small>
                    <strong>{{ methodBaseCost(method) > 0 ? formatCheckoutPrice(methodBaseCost(method)) : 'Gratis' }}</strong>
                  </div>
                </button>
              </div>

              <p v-if="selectionErrors.shipping" class="shipping-field-error">
                {{ selectionErrors.shipping }}
              </p>
            </section>

            <section class="shipping-section-card">
              <header class="shipping-section-head">
                <div>
                  <h2>
                    <i class="fas fa-note-sticky" />
                    Notas del Pedido
                  </h2>
                  <p>Cuéntanos algo importante para la entrega. Este campo es opcional.</p>
                </div>
              </header>

              <label class="shipping-notes-field" for="shipping-notes">
                <textarea
                  id="shipping-notes"
                  v-model.trim="orderNotes"
                  rows="4"
                  placeholder="Ejemplo: llamar antes de llegar, entregar a portería, horario recomendado..."
                />
              </label>
            </section>
          </div>

          <aside class="shipping-summary-column">
            <div class="shipping-summary-box">
              <h2 class="shipping-summary-title">Resumen del Pedido</h2>

              <div class="shipping-summary-items">
                <h3>Productos ({{ itemCount }})</h3>

                <div class="shipping-summary-list">
                  <article
                    v-for="item in cartItems"
                    :key="item.item_id || `${item.product_id}-${item.product_name}`"
                    class="shipping-summary-item"
                  >
                    <RouterLink :to="productRoute(item)" class="shipping-summary-media">
                      <img
                        :src="resolveMediaUrl(item.product_image, 'product')"
                        :alt="item.product_name"
                        @error="onItemImageError($event, item.product_image)"
                      />
                    </RouterLink>

                    <div class="shipping-summary-copy">
                      <h4>
                        <RouterLink :to="productRoute(item)">
                          {{ item.product_name }}
                        </RouterLink>
                      </h4>

                      <div v-if="buildCheckoutVariantName(item)" class="shipping-summary-variants">
                        <span>{{ buildCheckoutVariantName(item) }}</span>
                      </div>

                      <div class="shipping-summary-meta">
                        <span>{{ item.quantity }} x</span>
                        <strong>{{ formatCheckoutPrice(item.price) }}</strong>
                      </div>
                    </div>
                  </article>
                </div>
              </div>

              <div class="shipping-summary-rows">
                <div class="shipping-summary-row">
                  <span>Subtotal</span>
                  <strong>{{ formatCheckoutPrice(subtotal) }}</strong>
                </div>

                <div class="shipping-summary-row">
                  <span>Envío base (método)</span>
                  <strong>{{ shippingMethodBaseCost > 0 ? formatCheckoutPrice(shippingMethodBaseCost) : 'Gratis' }}</strong>
                </div>

                <div v-if="shippingRangeAdditionalCost > 0" class="shipping-summary-row shipping-summary-row--highlight">
                  <span>{{ shippingRangeSummaryLabel }}</span>
                  <strong>+{{ formatCheckoutPrice(shippingRangeAdditionalCost) }}</strong>
                </div>

                <div class="shipping-summary-row">
                  <span>Envío total</span>
                  <strong>{{ shippingCost > 0 ? formatCheckoutPrice(shippingCost) : 'Gratis' }}</strong>
                </div>

                <div v-if="discountAmount > 0" class="shipping-summary-row shipping-summary-row--discount">
                  <span>{{ discountSummaryLabel }}</span>
                  <strong>-{{ formatCheckoutPrice(discountAmount) }}</strong>
                </div>

                <div class="shipping-summary-row shipping-summary-row--total">
                  <span>Total</span>
                  <strong>{{ formatCheckoutPrice(orderTotal) }}</strong>
                </div>
              </div>

              <div class="shipping-discount-box">
                <h3>¿Tienes un código de descuento?</h3>

                <div class="shipping-discount-form">
                  <input
                    v-model.trim="discountCode"
                    type="text"
                    placeholder="Ingresa tu código"
                    class="shipping-discount-input"
                    @input="clearDiscountFeedback"
                  />

                  <div class="shipping-discount-actions">
                    <button
                      type="button"
                      class="shipping-discount-button"
                      :disabled="applyingDiscount"
                      @click="applyDiscount"
                    >
                      {{ applyingDiscount ? 'Validando...' : discountSource === 'code' && discountAmount > 0 ? 'Cambiar' : 'Aplicar' }}
                    </button>

                    <button
                      v-if="discountSource === 'code' && discountAmount > 0"
                      type="button"
                      class="shipping-discount-remove"
                      aria-label="Eliminar descuento"
                      @click="removeDiscount"
                    >
                      <i class="fas fa-times" />
                    </button>
                  </div>
                </div>

                <p
                  v-if="discountFeedback"
                  class="shipping-discount-message"
                  :class="{
                    'shipping-discount-message--success': discountFeedbackType === 'success',
                    'shipping-discount-message--error': discountFeedbackType === 'error',
                  }"
                >
                  {{ discountFeedback }}
                </p>
              </div>

              <div class="shipping-summary-actions">
                <RouterLink :to="{ name: 'cart' }" class="shipping-outline-pill">
                  <i class="fas fa-arrow-left" />
                  <span>Volver al carrito</span>
                </RouterLink>

                <button type="submit" class="shipping-primary-pill">
                  <span>Proceder al pago</span>
                  <i class="fas fa-arrow-right" />
                </button>
              </div>

              <div class="shipping-security-note">
                <i class="fas fa-shield-alt" />
                <span>Tu información está protegida y tu resumen seguirá disponible en el siguiente paso.</span>
              </div>
            </div>
          </aside>
        </div>
      </form>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import CheckoutFlowHeader from '../components/CheckoutFlowHeader.vue'
import { useAppShell } from '../../../composables/useAppShell'
import { useSession } from '../../../composables/useSession'
import { getCart } from '../../../services/cartApi'
import { validateBulkDiscount, validateDiscountCode } from '../../../services/discountApi'
import { estimateShipping, getShippingMethods, getShippingRules, getUserAddresses } from '../../../services/shippingApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import {
  buildCheckoutAddressLine,
  buildCheckoutVariantName,
  buildCheckoutZoneLine,
  formatCheckoutPrice,
  iconCheckoutAddressType,
  labelCheckoutAddressType,
  labelCheckoutBuilding,
  normalizeCheckoutAddress,
  normalizeCheckoutCartItem,
  normalizeCheckoutMethod,
} from '../utils/checkoutHelpers'

const EMPTY_CART = {
  items: [],
  item_count: 0,
  subtotal: 0,
}

const router = useRouter()
const { sessionId, user } = useSession()
const { setCartCount } = useAppShell()

const loading = ref(true)
const errorMessage = ref('')
const orderNotes = ref('')
const discountCode = ref('')
const discountAmount = ref(0)
const discountSource = ref('none')
const bulkDiscountMeta = ref(null)
const discountFeedback = ref('')
const discountFeedbackType = ref('success')
const applyingDiscount = ref(false)

const cart = ref({ ...EMPTY_CART })
const addresses = ref([])
const methods = ref([])
const rules = ref([])
const selectedAddressId = ref(null)
const selectedShippingMethodId = ref(null)
const selectionErrors = ref({
  address: '',
  shipping: '',
})

const cartItems = computed(() => {
  const rawItems = Array.isArray(cart.value?.items) ? cart.value.items : []
  return rawItems.map(normalizeCheckoutCartItem)
})

const subtotal = computed(() => Number(cart.value?.subtotal || 0))
const itemCount = computed(() => cartItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0))

const selectedAddress = computed(() => {
  return addresses.value.find((address) => address.id === selectedAddressId.value) || null
})

const selectedShippingMethod = computed(() => {
  return methods.value.find((method) => method.id === selectedShippingMethodId.value) || null
})

const activeShippingRule = computed(() => findMatchingShippingRule(subtotal.value, rules.value))
const selectedShippingBreakdown = computed(() => resolveMethodShippingBreakdown(
  selectedShippingMethod.value,
  subtotal.value,
  activeShippingRule.value,
))
const shippingMethodBaseCost = computed(() => selectedShippingBreakdown.value.method_cost)
const shippingRangeAdditionalCost = computed(() => selectedShippingBreakdown.value.range_rule_additional_cost)
const shippingRangeSummaryLabel = computed(() => {
  const ruleLabel = String(selectedShippingBreakdown.value.range_rule_label || '').trim()
  return ruleLabel ? `Ajuste por rango (${ruleLabel})` : 'Ajuste por rango de compra'
})
const shippingCost = computed(() => selectedShippingBreakdown.value.total_cost)
const discountSummaryLabel = computed(() => {
  if (discountSource.value === 'code' && discountCode.value) {
    return `Descuento (${discountCode.value})`
  }

  if (discountSource.value === 'bulk') {
    const label = String(bulkDiscountMeta.value?.label || '').trim()
    return label ? `Descuento por cantidad (${label})` : 'Descuento por cantidad'
  }

  return discountCode.value ? `Descuento (${discountCode.value})` : 'Descuento'
})
const orderTotal = computed(() => Math.max(0, subtotal.value + shippingCost.value - discountAmount.value))

onMounted(loadPage)

async function loadPage() {
  loading.value = true
  errorMessage.value = ''

  try {
    const [cartRes, addressesRes, rulesRes] = await Promise.all([
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
      getUserAddresses(user.value?.id || undefined, user.value?.email || ''),
      getShippingRules().catch(() => ({ data: [] })),
    ])

    cart.value = cartRes?.data && typeof cartRes.data === 'object'
      ? { ...EMPTY_CART, ...cartRes.data }
      : { ...EMPTY_CART }

    addresses.value = Array.isArray(addressesRes?.data)
      ? addressesRes.data.map(normalizeCheckoutAddress)
      : []

    rules.value = Array.isArray(rulesRes?.data)
      ? rulesRes.data.map(normalizeShippingRule)
      : []

    const methodsRes = await getShippingMethods({
      subtotal: Number(cart.value?.subtotal || 0),
      city: preferredShippingCity(),
    })

    let nextMethods = Array.isArray(methodsRes?.data)
      ? methodsRes.data.map(normalizeCheckoutMethod)
      : []

    if (nextMethods.length === 0) {
      const estimateRes = await estimateShipping({
        subtotal: Number(cart.value?.subtotal || 0),
        city: 'Medellín',
      })

      const rangeRuleAdditionalCost = Number(
        estimateRes?.range_rule_additional_cost
        ?? estimateRes?.shipping_cost
        ?? 0,
      )

      nextMethods = [
        normalizeCheckoutMethod({
          id: -1,
          name: 'Envío estándar',
          description: 'Costo estimado según el total actual de tu pedido.',
          delivery_time: 'Coordinaremos el despacho contigo',
          base_cost: 0,
          method_cost: 0,
          range_rule_additional_cost: rangeRuleAdditionalCost,
          range_rule_label: String(estimateRes?.range_rule_label || '').trim(),
          applied_cost: rangeRuleAdditionalCost,
        }),
      ]
    }

    methods.value = nextMethods

    setCartCount(cart.value.item_count || 0)
    restoreSavedState()
    await syncAutomaticBulkDiscount()
  } catch {
    errorMessage.value = 'No se pudo cargar el checkout de envío.'
    cart.value = { ...EMPTY_CART }
    setCartCount(0)
  } finally {
    loading.value = false
  }
}

function restoreSavedState() {
  const rawSaved = localStorage.getItem('angelow_checkout_shipping')
  const saved = rawSaved ? parseStoredJson(rawSaved) : null

  discountCode.value = String(saved?.discount_code || '').trim()
  discountAmount.value = Number(saved?.discount_amount || 0)
  discountSource.value = ['code', 'bulk'].includes(String(saved?.discount_source || '').trim())
    ? String(saved.discount_source).trim()
    : (discountCode.value ? 'code' : 'none')
  bulkDiscountMeta.value = saved?.bulk_discount && typeof saved.bulk_discount === 'object'
    ? saved.bulk_discount
    : null
  orderNotes.value = String(saved?.notes || '').trim()

  const savedAddressId = Number(saved?.selected_address_id || saved?.selected_address?.id || 0)
  const savedMethodId = Number(saved?.selected_shipping_method_id || saved?.selected_shipping_method?.id || 0)

  selectedAddressId.value = addresses.value.some((address) => address.id === savedAddressId)
    ? savedAddressId
    : preferredAddressId()

  selectedShippingMethodId.value = methods.value.some((method) => method.id === savedMethodId)
    ? savedMethodId
    : preferredShippingMethodId()
}

function preferredAddressId() {
  return addresses.value.find((address) => address.is_default)?.id || addresses.value[0]?.id || null
}

function preferredShippingMethodId() {
  const prioritizedMethod = methods.value.find((method) => /est[aá]ndar|domicilio/i.test(method.name))
  return prioritizedMethod?.id || methods.value[0]?.id || null
}

function preferredShippingCity() {
  const defaultAddress = addresses.value.find((address) => Boolean(address.is_default))
  const firstAddress = defaultAddress || addresses.value[0] || null
  const city = String(firstAddress?.city || '').trim()
  return city || undefined
}

function selectAddress(addressId) {
  selectedAddressId.value = addressId
  selectionErrors.value.address = ''
}

function selectShippingMethod(methodId) {
  selectedShippingMethodId.value = methodId
  selectionErrors.value.shipping = ''
}

function normalizeShippingRule(rule = {}) {
  const hasMaxPrice = rule?.max_price !== null && rule?.max_price !== undefined && rule?.max_price !== ''
  const minPrice = Number(rule?.min_price || 0)
  const maxPrice = hasMaxPrice ? Number(rule.max_price) : null
  const rangeLabel = String(rule?.range_label || '').trim()

  return {
    id: Number(rule?.id || 0),
    min_price: minPrice,
    max_price: maxPrice,
    shipping_cost: Math.max(0, Number(rule?.shipping_cost || 0)),
    range_label: rangeLabel || formatRuleRangeLabel(minPrice, maxPrice),
    active: Boolean(rule?.active ?? rule?.is_active ?? true),
  }
}

function formatRuleRangeLabel(minPrice, maxPrice) {
  const minLabel = formatCheckoutPrice(minPrice || 0)
  if (maxPrice === null || maxPrice === undefined || maxPrice === '') {
    return `desde ${minLabel}`
  }

  return `${minLabel} a ${formatCheckoutPrice(maxPrice)}`
}

function findMatchingShippingRule(orderSubtotal, availableRules = []) {
  if (!Array.isArray(availableRules) || availableRules.length === 0) {
    return null
  }

  const subtotalAmount = Number(orderSubtotal || 0)

  return availableRules
    .filter((rule) => Boolean(rule?.active))
    .filter((rule) => subtotalAmount >= Number(rule?.min_price || 0))
    .filter((rule) => rule?.max_price === null || subtotalAmount <= Number(rule.max_price || 0))
    .sort((left, right) => Number(right?.min_price || 0) - Number(left?.min_price || 0))[0] || null
}

function resolveMethodShippingBreakdown(method, orderSubtotal, activeRule) {
  if (!method) {
    return {
      method_cost: 0,
      range_rule_additional_cost: 0,
      range_rule_applied: false,
      range_rule_label: '',
      total_cost: 0,
    }
  }

  const subtotalAmount = Number(orderSubtotal || 0)
  const methodBaseCost = Math.max(0, Number(method?.base_cost || 0))
  const freeThresholdAmount = Number(method?.free_shipping_minimum || 0)
  const hasFreeThreshold = Number.isFinite(freeThresholdAmount) && freeThresholdAmount > 0

  const hasStoredMethodCost = method?.method_cost !== null
    && method?.method_cost !== undefined
    && method?.method_cost !== ''

  const methodCost = hasStoredMethodCost
    ? Math.max(0, Number(method.method_cost || 0))
    : (hasFreeThreshold && subtotalAmount >= freeThresholdAmount ? 0 : methodBaseCost)

  const hasStoredRuleAdditional = method?.range_rule_additional_cost !== null
    && method?.range_rule_additional_cost !== undefined
    && method?.range_rule_additional_cost !== ''

  const rangeRuleAdditionalCost = hasStoredRuleAdditional
    ? Math.max(0, Number(method.range_rule_additional_cost || 0))
    : (activeRule ? Math.max(0, Number(activeRule.shipping_cost || 0)) : 0)

  const hasStoredAppliedCost = method?.applied_cost !== null
    && method?.applied_cost !== undefined
    && method?.applied_cost !== ''

  const fallbackTotalCost = Math.max(0, methodCost + rangeRuleAdditionalCost)
  const storedAppliedCost = hasStoredAppliedCost
    ? Math.max(0, Number(method.applied_cost || 0))
    : null

  // Priorizamos el desglose explícito (base + adicional) para evitar mostrar totales legacy incorrectos.
  const totalCost = fallbackTotalCost

  const storedRuleLabel = String(method?.range_rule_label || '').trim()
  const ruleLabel = storedRuleLabel || (activeRule ? formatRuleRangeLabel(activeRule.min_price, activeRule.max_price) : '')

  return {
    method_cost: methodCost,
    range_rule_additional_cost: rangeRuleAdditionalCost,
    range_rule_applied: Boolean(
      method?.range_rule_applied
      || method?.rule_id
      || activeRule,
    ),
    range_rule_label: ruleLabel,
    total_cost: totalCost,
    stored_applied_cost: storedAppliedCost,
  }
}

function methodBaseCost(method) {
  return resolveMethodShippingBreakdown(method, subtotal.value, activeShippingRule.value).method_cost
}

function methodRangeAdditionalCost(method) {
  return resolveMethodShippingBreakdown(method, subtotal.value, activeShippingRule.value).range_rule_additional_cost
}

function methodRangeRuleLabel(method) {
  return resolveMethodShippingBreakdown(method, subtotal.value, activeShippingRule.value).range_rule_label
}

async function syncAutomaticBulkDiscount() {
  if (discountCode.value) {
    discountSource.value = 'code'
    return
  }

  const subtotalAmount = Math.max(0, Number(subtotal.value || 0))
  const quantityAmount = Math.max(0, Number(itemCount.value || 0))

  if (subtotalAmount <= 0 || quantityAmount <= 0) {
    bulkDiscountMeta.value = null
    discountAmount.value = 0
    discountSource.value = 'none'
    return
  }

  try {
    const result = await validateBulkDiscount({
      item_count: quantityAmount,
      order_total: subtotalAmount,
    })

    if (result?.valid && result?.bulk_discount) {
      const bulkDiscountAmount = Math.max(0, Math.round(Number(result.bulk_discount.discount_amount || 0)))
      bulkDiscountMeta.value = result.bulk_discount
      discountAmount.value = bulkDiscountAmount
      discountSource.value = bulkDiscountAmount > 0 ? 'bulk' : 'none'
      return
    }

    bulkDiscountMeta.value = null
    discountAmount.value = 0
    discountSource.value = 'none'
  } catch {
    bulkDiscountMeta.value = null
    if (!discountCode.value) {
      discountAmount.value = 0
      discountSource.value = 'none'
    }
  }
}

function extractDiscountErrorMessage(error, fallbackMessage) {
  return String(error?.response?.data?.message || fallbackMessage)
}

async function applyDiscount() {
  if (!discountCode.value) {
    discountFeedback.value = 'Ingresa un código para validarlo.'
    discountFeedbackType.value = 'error'
    return
  }

  const previousDiscountAmount = discountAmount.value
  const previousDiscountSource = discountSource.value
  const previousBulkDiscountMeta = bulkDiscountMeta.value

  applyingDiscount.value = true
  discountFeedback.value = ''

  try {
    const result = await validateDiscountCode({
      code: discountCode.value,
      user_id: user.value?.id || null,
      order_total: subtotal.value,
      item_count: itemCount.value,
    })

    if (!result?.valid || !result?.discount) {
      if (previousDiscountSource === 'bulk') {
        discountAmount.value = previousDiscountAmount
        discountSource.value = 'bulk'
        bulkDiscountMeta.value = previousBulkDiscountMeta
      } else {
        discountAmount.value = 0
        discountSource.value = 'none'
      }

      discountFeedback.value = String(result?.message || 'El código no es válido o ya no está disponible.')
      discountFeedbackType.value = 'error'
      return
    }

    const discountData = result.discount
    const discountAmountFromApi = Number(discountData?.discount_amount)

    let resolvedDiscountAmount = 0

    if (Number.isFinite(discountAmountFromApi) && discountAmountFromApi > 0) {
      resolvedDiscountAmount = discountAmountFromApi
    } else {
      const discountValue = Number(discountData?.discount_value || 0)
      const discountType = String(discountData?.discount_type || discountData?.type || '').toLowerCase()

      resolvedDiscountAmount = discountType === 'fixed'
        ? Math.min(subtotal.value, Math.max(0, discountValue))
        : Math.round((subtotal.value * Math.max(0, Math.min(100, discountValue))) / 100)
    }

    discountAmount.value = Math.max(0, Math.round(resolvedDiscountAmount))
    discountSource.value = 'code'
    bulkDiscountMeta.value = result?.bulk_discount && typeof result.bulk_discount === 'object'
      ? result.bulk_discount
      : previousBulkDiscountMeta
    discountFeedback.value = `¡Descuento aplicado! Ahorras ${formatCheckoutPrice(discountAmount.value)}.`
    discountFeedbackType.value = 'success'
  } catch (error) {
    if (previousDiscountSource === 'bulk') {
      discountAmount.value = previousDiscountAmount
      discountSource.value = 'bulk'
      bulkDiscountMeta.value = previousBulkDiscountMeta
    } else {
      discountAmount.value = 0
      discountSource.value = 'none'
    }

    discountFeedback.value = extractDiscountErrorMessage(error, 'No fue posible validar el descuento en este momento.')
    discountFeedbackType.value = 'error'
  } finally {
    applyingDiscount.value = false
  }
}

async function removeDiscount() {
  discountCode.value = ''
  discountAmount.value = 0
  discountSource.value = 'none'

  await syncAutomaticBulkDiscount()

  if (discountSource.value === 'bulk' && discountAmount.value > 0) {
    discountFeedback.value = `Se aplicó el descuento por cantidad. Ahorras ${formatCheckoutPrice(discountAmount.value)}.`
  } else {
    discountFeedback.value = 'El descuento fue removido del resumen.'
  }

  discountFeedbackType.value = 'success'
}

function clearDiscountFeedback() {
  discountFeedback.value = ''
}

async function continueToPayment() {
  let hasError = false

  if (!selectedAddress.value) {
    selectionErrors.value.address = 'Debes seleccionar una dirección de envío.'
    hasError = true
  }

  if (!selectedShippingMethod.value) {
    selectionErrors.value.shipping = 'Debes seleccionar un método de envío.'
    hasError = true
  }

  if (cartItems.value.length === 0) {
    errorMessage.value = 'Tu carrito está vacío. Regresa al carrito para continuar.'
    return
  }

  if (hasError) {
    errorMessage.value = 'Revisa los campos obligatorios antes de continuar.'
    return
  }

  if (!discountCode.value.trim()) {
    await syncAutomaticBulkDiscount()
  }

  errorMessage.value = ''

  const shippingBreakdown = selectedShippingBreakdown.value

  localStorage.setItem('angelow_checkout_shipping', JSON.stringify({
    selected_address_id: selectedAddress.value?.id || null,
    selected_address: selectedAddress.value,
    selected_shipping_method_id: selectedShippingMethod.value?.id || null,
    selected_shipping_method: selectedShippingMethod.value
      ? {
          ...selectedShippingMethod.value,
          applied_cost: shippingCost.value,
          method_cost: shippingBreakdown.method_cost,
          range_rule_additional_cost: shippingBreakdown.range_rule_additional_cost,
          range_rule_applied: shippingBreakdown.range_rule_applied,
          range_rule_label: shippingBreakdown.range_rule_label,
        }
      : null,
    shipping_breakdown: {
      method_cost: shippingBreakdown.method_cost,
      range_rule_additional_cost: shippingBreakdown.range_rule_additional_cost,
      range_rule_applied: shippingBreakdown.range_rule_applied,
      range_rule_label: shippingBreakdown.range_rule_label,
      shipping_cost: shippingCost.value,
    },
    notes: orderNotes.value.trim(),
    discount_code: discountCode.value.trim(),
    discount_amount: discountAmount.value,
    discount_source: discountSource.value,
    bulk_discount: bulkDiscountMeta.value,
    subtotal: subtotal.value,
    shipping_cost: shippingCost.value,
    total: orderTotal.value,
    items_snapshot: cartItems.value,
    session_id: sessionId.value,
    user_id: user.value?.id || null,
    user_email: user.value?.email || null,
  }))

  router.push({ name: 'payment' })
}

function onItemImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'product')
}

function productRoute(item) {
  const slug = String(item?.product_slug || '').trim()
  if (slug) {
    return { name: 'product', params: { slug } }
  }

  return { name: 'store' }
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
.shipping-page-root {
  width: 100%;
  padding: 2rem 0 3.5rem;
}

.shipping-page-shell {
  width: min(100%, 1480px);
  margin: 0 auto;
  padding: 0 1.5rem;
  animation: shippingPageFadeIn 0.4s ease;
}

.shipping-page-divider {
  height: 1px;
  background: #e0e0e0;
  margin-bottom: 2rem;
}

.shipping-page-status {
  margin-top: 1rem;
}

.shipping-page-form {
  width: 100%;
}

.shipping-page-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 36rem;
  gap: 3rem;
  align-items: start;
}

.shipping-page-sections {
  display: grid;
  gap: 2.5rem;
}

.shipping-section-card,
.shipping-summary-box,
.shipping-empty-state {
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.shipping-section-card {
  padding: 2.4rem;
  transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.shipping-section-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.shipping-section-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.4rem;
  margin-bottom: 2rem;
  padding-bottom: 1.6rem;
  border-bottom: 1px solid #e0e0e0;
}

.shipping-section-head h2 {
  margin: 0 0 0.6rem;
  color: #333333;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.shipping-section-head h2 i {
  color: #0077b6;
}

.shipping-section-head p {
  margin: 0;
  color: #666666;
  font-size: 1.45rem;
  line-height: 1.55;
}

.shipping-outline-action,
.shipping-filled-action,
.shipping-outline-pill,
.shipping-primary-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.9rem;
  min-height: 5.2rem;
  border-radius: 999px;
  text-decoration: none;
  font-size: 1.5rem;
  font-weight: 700;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.shipping-outline-action,
.shipping-outline-pill {
  padding: 0.9rem 1.8rem;
  border: 2px solid #0077b6;
  color: #0077b6;
  background: #ffffff;
}

.shipping-outline-action:hover,
.shipping-outline-pill:hover {
  transform: translateX(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.shipping-filled-action,
.shipping-primary-pill {
  padding: 0.9rem 1.8rem;
  border: none;
  background: #0077b6;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  cursor: pointer;
}

.shipping-filled-action:hover,
.shipping-primary-pill:hover {
  background: #005b8c;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}

.shipping-filled-action--compact {
  min-height: 4.8rem;
  font-size: 1.4rem;
}

.shipping-address-list,
.shipping-method-list {
  display: grid;
  gap: 1.4rem;
}

.shipping-address-card,
.shipping-method-card {
  width: 100%;
  border: 2px solid #e0e0e0;
  border-radius: 1.8rem;
  padding: 2rem;
  background: #ffffff;
  text-align: left;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.shipping-address-card:hover,
.shipping-method-card:hover {
  border-color: #0077b6;
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.shipping-address-card--default {
  background: rgba(0, 119, 182, 0.03);
}

.shipping-address-card--selected,
.shipping-method-card--selected {
  border-color: #0077b6;
  background: #e6f2ff;
  box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.1);
}

.shipping-address-headline,
.shipping-method-card {
  display: flex;
  align-items: flex-start;
  gap: 1.4rem;
}

.shipping-method-card {
  justify-content: space-between;
}

.shipping-address-icon,
.shipping-method-icon {
  width: 5rem;
  height: 5rem;
  flex-shrink: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.12);
  color: #0077b6;
  font-size: 1.8rem;
}

.shipping-address-copy,
.shipping-method-copy {
  flex: 1;
  min-width: 0;
}

.shipping-address-copy h3,
.shipping-method-copy h3 {
  margin: 0 0 0.5rem;
  color: #005b8c;
  font-size: 1.8rem;
  font-weight: 700;
}

.shipping-address-type,
.shipping-address-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 1.1rem;
  border-radius: 999px;
  font-size: 1.2rem;
  font-weight: 600;
}

.shipping-address-type {
  background: #0077b6;
  color: #ffffff;
}

.shipping-address-badge {
  background: #4bb543;
  color: #ffffff;
  flex-shrink: 0;
}

.shipping-address-details {
  display: grid;
  gap: 0.9rem;
  margin-top: 1.4rem;
}

.shipping-address-line {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  color: #666666;
  font-size: 1.4rem;
}

.shipping-address-line i {
  width: 1.6rem;
  margin-top: 0.2rem;
  color: #0077b6;
  flex-shrink: 0;
}

.shipping-address-line p,
.shipping-method-copy p,
.shipping-empty-state p,
.shipping-empty-block p,
.shipping-notes-field textarea {
  margin: 0;
}

.shipping-method-copy p {
  color: #666666;
  font-size: 1.4rem;
  line-height: 1.55;
}

.shipping-method-time {
  margin-top: 0.8rem;
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  color: #0077b6;
  font-size: 1.3rem;
  font-weight: 600;
}

.shipping-method-breakdown {
  margin-top: 0.8rem;
  display: grid;
  gap: 0.35rem;
  color: #5c6773;
  font-size: 1.22rem;
  line-height: 1.45;
}

.shipping-method-breakdown__extra {
  color: #0077b6;
  font-weight: 700;
}

.shipping-method-cost {
  display: grid;
  justify-items: end;
  gap: 0.35rem;
  flex-shrink: 0;
  text-align: right;
}

.shipping-method-cost small {
  color: #6b7280;
  font-size: 1.1rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  font-weight: 700;
}

.shipping-method-cost strong {
  color: #0077b6;
  font-size: 1.8rem;
  font-weight: 700;
}

.shipping-notes-field textarea {
  width: 100%;
  min-height: 12rem;
  padding: 1.3rem 1.4rem;
  border: 1px solid #d6dce2;
  border-radius: 1.6rem;
  color: #333333;
  font-size: 1.45rem;
  line-height: 1.6;
  resize: vertical;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.shipping-notes-field textarea:focus,
.shipping-discount-input:focus {
  outline: none;
  border-color: #0077b6;
  box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.12);
}

.shipping-summary-column {
  position: sticky;
  top: 12rem;
}

.shipping-summary-box {
  padding: 2.5rem;
  overflow: hidden;
}

.shipping-summary-title {
  margin: 0 0 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  color: #333333;
  font-size: 1.8rem;
  font-weight: 700;
}

.shipping-summary-items {
  margin-bottom: 2rem;
}

.shipping-summary-items h3,
.shipping-discount-box h3 {
  margin: 0 0 1.4rem;
  color: #333333;
  font-size: 1.5rem;
  font-weight: 700;
}

.shipping-summary-list {
  display: grid;
  gap: 1.2rem;
  max-height: 33rem;
  overflow-y: auto;
  padding-right: 0.6rem;
}

.shipping-summary-list::-webkit-scrollbar {
  width: 0.6rem;
}

.shipping-summary-list::-webkit-scrollbar-thumb {
  background: #0077b6;
  border-radius: 999px;
}

.shipping-summary-item {
  display: flex;
  gap: 1.2rem;
  padding: 1.2rem;
  border-radius: 1.6rem;
  background: #f9f9f9;
  transition: background 0.3s ease;
}

.shipping-summary-item:hover {
  background: rgba(0, 119, 182, 0.06);
}

.shipping-summary-media {
  width: 6.6rem;
  height: 6.6rem;
  overflow: hidden;
  border-radius: 1.4rem;
  flex-shrink: 0;
  border: 1px solid #e0e0e0;
  background: #ffffff;
}

.shipping-summary-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.shipping-summary-copy {
  min-width: 0;
  flex: 1;
}

.shipping-summary-copy h4 {
  margin: 0 0 0.6rem;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.35;
}

.shipping-summary-copy h4 a {
  color: #333333;
  text-decoration: none;
}

.shipping-summary-copy h4 a:hover {
  color: #0077b6;
}

.shipping-summary-variants span {
  display: inline-flex;
  padding: 0.35rem 0.9rem;
  border: 1px solid #d9e1e7;
  border-radius: 999px;
  background: #ffffff;
  color: #666666;
  font-size: 1.18rem;
  margin-bottom: 0.7rem;
}

.shipping-summary-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.9rem;
  color: #666666;
  font-size: 1.25rem;
}

.shipping-summary-meta strong {
  color: #0077b6;
  font-size: 1.35rem;
}

.shipping-summary-rows {
  display: grid;
  gap: 1.2rem;
  margin-bottom: 2rem;
}

.shipping-summary-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.2rem;
  color: #444444;
  font-size: 1.48rem;
}

.shipping-summary-row strong {
  color: #0077b6;
  text-align: right;
}

.shipping-summary-row--discount strong {
  color: #4bb543;
}

.shipping-summary-row--highlight span {
  color: #005b8c;
  font-weight: 600;
}

.shipping-summary-row--highlight strong {
  color: #005b8c;
  font-weight: 700;
}

.shipping-summary-row--total {
  padding-top: 1.5rem;
  border-top: 2px dashed #e0e0e0;
  color: #005b8c;
  font-size: 1.8rem;
  font-weight: 700;
}

.shipping-summary-row--total strong {
  color: #005b8c;
}

.shipping-discount-box {
  padding: 1.8rem;
  border: 1px solid #e0e0e0;
  border-radius: 1.8rem;
  background: #f9f9f9;
}

.shipping-discount-form {
  display: flex;
  gap: 0.8rem;
  align-items: stretch;
}

.shipping-discount-input {
  flex: 1;
  min-width: 0;
  padding: 1.1rem 1.3rem;
  border: 1px solid #d6dce2;
  border-radius: 1.4rem;
  font-size: 1.4rem;
  color: #333333;
}

.shipping-discount-actions {
  display: flex;
  gap: 0.6rem;
  flex-shrink: 0;
}

.shipping-discount-button,
.shipping-discount-remove {
  border-radius: 1.4rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
}

.shipping-discount-button {
  padding: 0 1.4rem;
  border: 2px solid #0077b6;
  background: #ffffff;
  color: #0077b6;
}

.shipping-discount-button:hover:not(:disabled) {
  background: #0077b6;
  color: #ffffff;
}

.shipping-discount-button:disabled {
  opacity: 0.7;
  cursor: wait;
}

.shipping-discount-remove {
  width: 4.6rem;
  border: none;
  background: #ff3333;
  color: #ffffff;
}

.shipping-discount-remove:hover {
  background: #d52c2c;
}

.shipping-discount-message {
  margin: 1rem 0 0;
  padding: 1rem 1.1rem;
  border-radius: 1.2rem;
  font-size: 1.28rem;
  font-weight: 600;
}

.shipping-discount-message--success {
  background: rgba(75, 181, 67, 0.12);
  color: #2e7d32;
  border: 1px solid rgba(75, 181, 67, 0.3);
}

.shipping-discount-message--error {
  background: rgba(255, 51, 51, 0.1);
  color: #c62828;
  border: 1px solid rgba(255, 51, 51, 0.2);
}

.shipping-summary-actions {
  display: grid;
  gap: 1.2rem;
  margin: 2rem 0;
}

.shipping-security-note {
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

.shipping-security-note i {
  color: #0077b6;
  font-size: 1.6rem;
  margin-top: 0.1rem;
}

.shipping-empty-state {
  max-width: 60rem;
  margin: 1rem auto 0;
  padding: 4rem 2rem;
  text-align: center;
}

.shipping-empty-icon,
.shipping-empty-block__icon {
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

.shipping-empty-state h2,
.shipping-empty-block h3 {
  margin: 0 0 1rem;
  color: #333333;
  font-size: 2.2rem;
  font-weight: 700;
}

.shipping-empty-state p,
.shipping-empty-block p {
  color: #666666;
  font-size: 1.5rem;
  line-height: 1.6;
}

.shipping-empty-block {
  padding: 2rem;
  border-radius: 1.8rem;
  background: #f9f9f9;
  text-align: center;
}

.shipping-empty-block--soft {
  margin-top: 0.4rem;
}

.shipping-field-error {
  margin: 1.2rem 0 0;
  color: #c62828;
  font-size: 1.25rem;
  font-weight: 600;
}

@keyframes shippingPageFadeIn {
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
  .shipping-page-layout {
    grid-template-columns: 1fr;
  }

  .shipping-summary-column {
    position: static;
    top: auto;
  }
}

@media (max-width: 768px) {
  .shipping-page-root {
    padding-top: 1.4rem;
    padding-bottom: 2.4rem;
  }

  .shipping-page-shell {
    padding: 0 1rem;
  }

  .shipping-section-card,
  .shipping-summary-box,
  .shipping-empty-state {
    padding: 1.8rem 1.4rem;
  }

  .shipping-section-head {
    flex-direction: column;
  }

  .shipping-method-card,
  .shipping-address-headline {
    flex-direction: column;
  }

  .shipping-address-badge {
    align-self: flex-start;
  }

  .shipping-discount-form {
    flex-direction: column;
  }

  .shipping-discount-actions {
    width: 100%;
  }

  .shipping-discount-button,
  .shipping-discount-remove {
    min-height: 4.8rem;
  }

  .shipping-discount-button {
    flex: 1;
  }
}
</style>
