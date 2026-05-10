<template>
  <main class="cart-page-root">
    <section class="cart-page-shell">
      <header class="cart-page-header">
        <div class="cart-page-title">
          <div class="cart-page-icon">
            <i class="fas fa-shopping-cart" />
          </div>
          <h1 class="cart-page-heading">Tu Carrito</h1>
        </div>

        <div class="cart-page-steps" aria-label="Pasos del checkout">
          <div class="cart-page-step cart-page-step--active">
            <span>1</span>
            <p>Carrito</p>
          </div>
          <div class="cart-page-step">
            <span>2</span>
            <p>Envío</p>
          </div>
          <div class="cart-page-step">
            <span>3</span>
            <p>Pago</p>
          </div>
          <div class="cart-page-step">
            <span>4</span>
            <p>Confirmación</p>
          </div>
        </div>
      </header>

      <div class="cart-page-divider" />

      <CheckoutShimmer v-if="loading" />
      <p v-else-if="errorMessage" class="error-box cart-page-status">{{ errorMessage }}</p>

      <section v-else-if="rawCartItems.length === 0" class="cart-empty-state">
        <div class="cart-empty-icon">
          <i class="fas fa-shopping-cart" />
        </div>
        <h2>Tu carrito está vacío</h2>
        <p>Explora nuestra tienda y descubre productos increíbles.</p>
        <RouterLink :to="{ name: 'store' }" class="cart-secondary-action cart-secondary-action--filled">
          <i class="fas fa-arrow-left" />
          <span>Continuar comprando</span>
        </RouterLink>
      </section>

      <section v-else class="cart-page-layout">
        <article class="cart-items-panel">
          <!-- Alerta de validación: se muestra si el usuario intenta proceder sin productos seleccionados -->
          <CheckoutValidationAlert
            :visible="selectionValidationError"
            title="Selecciona productos para continuar:"
            :errors="selectionValidationErrors"
          />
          <div class="cart-selection-toolbar">
            <label class="cart-selection-toggle" :class="{ 'cart-selection-toggle--disabled': !hasSelectableItems }">
              <span class="cart-checkbox">
                <input
                  type="checkbox"
                  :checked="allSelectableSelected"
                  :disabled="!hasSelectableItems"
                  aria-label="Seleccionar todos los productos disponibles"
                  @change="toggleAllSelections($event)"
                />
                <span class="cart-checkbox__visual" />
              </span>
              <span class="cart-selection-toggle__copy">Todos los productos disponibles</span>
            </label>

            <p class="cart-selection-toolbar__summary">
              <strong>{{ selectedProductCount }}</strong>
              {{ selectedProductCount === 1 ? 'producto listo para pago' : 'productos listos para pago' }}
              <span v-if="blockedProductCount > 0"> · {{ blockedProductCount }} requieren ajuste o quedaron agotados</span>
            </p>
          </div>

          <header class="cart-items-head">
            <span class="cart-items-head__selection" aria-hidden="true" />
            <span>Producto</span>
            <span>Precio</span>
            <span>Cantidad</span>
            <span>Total</span>
            <span class="cart-items-head__ghost" aria-hidden="true" />
          </header>

          <div class="cart-items-body">
            <article
              v-for="entry in cartEntries"
              :key="entry.itemId"
              class="cart-item-row"
              :class="rowClasses(entry)"
            >
              <label class="cart-item-select" :class="{ 'cart-item-select--blocked': !entry.availability.isSelectable }">
                <span class="cart-checkbox">
                  <input
                    type="checkbox"
                    :checked="entry.selected"
                    :disabled="!entry.availability.isSelectable || isItemBusy(entry.itemId)"
                    :aria-label="`Seleccionar ${entry.item.product_name} para el pago`"
                    @change="toggleItemSelection(entry.itemId, $event)"
                  />
                  <span class="cart-checkbox__visual" />
                </span>
              </label>

              <div class="cart-item-main">
                <RouterLink :to="productRoute(entry.item)" class="cart-item-media" :class="{ 'cart-item-media--sold-out': entry.availability.soldOut }">
                  <img
                    :src="resolveMediaUrl(entry.item.product_image, 'product')"
                    :alt="entry.item.product_name"
                    @error="onItemImageError($event, entry.item.product_image)"
                  />

                  <span
                    v-if="entry.availability.soldOut || entry.availability.lowStock"
                    class="cart-item-media-flag"
                    :class="{
                      'cart-item-media-flag--sold-out': entry.availability.soldOut,
                      'cart-item-media-flag--low-stock': !entry.availability.soldOut && entry.availability.lowStock,
                    }"
                  >
                    {{ entry.availability.soldOut ? 'Agotado' : imageStockOverlayText(entry.availability) }}
                  </span>
                </RouterLink>

                <div class="cart-item-copy">
                  <h2 class="cart-item-name">
                    <RouterLink :to="productRoute(entry.item)">
                      {{ entry.item.product_name }}
                    </RouterLink>
                  </h2>

                  <div v-if="entry.item.color_name || entry.item.size_name" class="cart-item-variants">
                    <span v-if="entry.item.color_name" class="cart-variant-pill">
                      Color: {{ entry.item.color_name }}
                    </span>
                    <span v-if="entry.item.size_name" class="cart-variant-pill">
                      Talla: {{ entry.item.size_name }}
                    </span>
                  </div>

                  <div class="cart-item-stock-badges">
                    <span
                      v-if="entry.availability.soldOut"
                      class="cart-stock-badge cart-stock-badge--sold-out"
                    >
                      Agotado
                    </span>
                    <span
                      v-else-if="entry.availability.exceedsStock"
                      class="cart-stock-badge cart-stock-badge--warning"
                    >
                      Ajusta a {{ entry.availability.availableStock }} {{ formatUnitLabel(entry.availability.availableStock) }}
                    </span>
                    <span
                      v-if="!entry.availability.isSelectable"
                      class="cart-stock-badge cart-stock-badge--muted"
                    >
                      No se enviará al pago
                    </span>
                  </div>

                  <transition name="cart-inline-feedback">
                    <p
                      v-if="inlineFeedbackForItem(entry.itemId)"
                      class="cart-item-feedback"
                      :class="feedbackToneClass(inlineFeedbackForItem(entry.itemId)?.tone)"
                    >
                      {{ inlineFeedbackForItem(entry.itemId)?.message }}
                    </p>
                    <p
                      v-else-if="itemAvailabilityMessage(entry.availability)"
                      class="cart-item-feedback"
                      :class="feedbackToneClass(entry.availability.soldOut ? 'muted' : 'warning')"
                    >
                      {{ itemAvailabilityMessage(entry.availability) }}
                    </p>
                  </transition>
                </div>
              </div>

              <div class="cart-item-price cart-data-cell" data-label="Precio">
                {{ formatPrice(entry.item.price) }}
              </div>

              <div class="cart-item-quantity cart-data-cell" data-label="Cantidad">
                <div class="cart-quantity-stack">
                  <div
                    class="cart-quantity-box"
                    :class="{
                      'cart-quantity-box--warning': entry.availability.exceedsStock || Boolean(inlineFeedbackForItem(entry.itemId)),
                      'cart-quantity-box--muted': entry.availability.soldOut,
                    }"
                  >
                    <button
                      type="button"
                      class="cart-quantity-btn"
                      :disabled="isItemBusy(entry.itemId) || entry.availability.soldOut || Number(entry.item.quantity || 1) <= 1"
                      :aria-label="`Disminuir cantidad de ${entry.item.product_name}`"
                      @click="decreaseQuantity(entry.item)"
                    >
                      <i class="fas fa-minus" />
                    </button>

                    <input
                      :value="entry.item.quantity"
                      type="number"
                      min="1"
                      :max="entry.availability.maxQuantity"
                      class="cart-quantity-input"
                      :disabled="isItemBusy(entry.itemId) || entry.availability.soldOut"
                      :aria-label="`Cantidad de ${entry.item.product_name}`"
                      @change="onQuantityInput(entry.item, $event)"
                    />

                    <button
                      type="button"
                      class="cart-quantity-btn"
                      :disabled="isItemBusy(entry.itemId) || !entry.availability.canIncrease"
                      :aria-label="`Aumentar cantidad de ${entry.item.product_name}`"
                      @click="increaseQuantity(entry.item)"
                    >
                      <i class="fas fa-plus" />
                    </button>
                  </div>

                  <small
                    v-if="entry.availability.lowStock && !entry.availability.soldOut"
                    class="cart-quantity-hint"
                  >
                    Máximo {{ entry.availability.availableStock }} {{ formatUnitLabel(entry.availability.availableStock) }}.
                  </small>
                </div>
              </div>

              <div class="cart-item-total cart-data-cell" data-label="Total">
                {{ formatPrice(entry.lineTotal) }}
              </div>

              <div class="cart-item-remove">
                <button
                  type="button"
                  class="cart-remove-btn"
                  :disabled="isItemBusy(entry.itemId)"
                  :aria-label="`Eliminar ${entry.item.product_name} del carrito`"
                  @click="deleteItem(entry.itemId)"
                >
                  <i class="fas fa-trash" />
                </button>
              </div>
            </article>
          </div>

          <footer class="cart-items-footer">
            <RouterLink :to="{ name: 'store' }" class="cart-secondary-action">
              <i class="fas fa-arrow-left" />
              <span>Continuar comprando</span>
            </RouterLink>
          </footer>
        </article>

        <aside class="cart-order-panel">
          <div class="cart-order-box">
            <h2 class="cart-order-title">Resumen del Pedido</h2>

            <div class="cart-order-rows">
              <div class="cart-order-row cart-order-row--selection">
                <span>Productos seleccionados</span>
                <strong>{{ selectedUnitCount }}</strong>
              </div>

              <div class="cart-order-row">
                <span>Subtotal</span>
                <strong>{{ formatPrice(selectedSubtotal) }}</strong>
              </div>

              <div class="cart-order-row">
                <span>Envío</span>
                <strong class="cart-order-shipping-note">Se calcula en el siguiente paso con los productos seleccionados</strong>
              </div>

              <div class="cart-order-row cart-order-row--total">
                <span>Total</span>
                <strong>{{ formatPrice(selectedSubtotal) }}</strong>
              </div>
            </div>

            <p v-if="blockedProductCount > 0" class="cart-order-note cart-order-note--warning">
              Ajusta o elimina los productos sin stock para volver a incluirlos.
            </p>
            <p v-else-if="!canProceedToShipping" class="cart-order-note">
              Selecciona al menos un producto disponible para continuar.
            </p>

            <button type="button" class="cart-primary-action" :disabled="!canProceedToShipping" @click="goToShipping">
              <span>
                {{ canProceedToShipping ? `Proceder al pago (${selectedUnitCount})` : 'Selecciona productos disponibles' }}
              </span>
              <i class="fas fa-arrow-right" />
            </button>
          </div>
        </aside>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import CheckoutValidationAlert from '../../../modules/checkout/components/CheckoutValidationAlert.vue'
import CheckoutShimmer from '../../../modules/checkout/components/CheckoutShimmer.vue'
import { useAppShell } from '../../../composables/useAppShell'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useSession } from '../../../composables/useSession'
import { getCart, removeCartItem, updateCartItem } from '../../../services/cartApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import {
  buildCartSelectionSummary,
  resolveCartItemAvailability,
  setAllStoredCartSelections,
  setStoredCartItemSelection,
  synchronizeCartSelectionMap,
} from '../utils/cartSelection'

const EMPTY_CART = {
  items: [],
  item_count: 0,
  subtotal: 0,
}

const INLINE_FEEDBACK_DURATION = 3600

const router = useRouter()
const { sessionId, user } = useSession()
const { setCartCount } = useAppShell()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const errorMessage = ref('')
// Control del banner de validación al intentar continuar sin selección
const selectionValidationError = ref(false)
const cart = ref({ ...EMPTY_CART })
const selectionMap = ref({})
const busyItemIds = ref({})
const inlineFeedback = ref({})

const inlineFeedbackTimers = new Map()

const rawCartItems = computed(() => (Array.isArray(cart.value?.items) ? cart.value.items : []))
const selectionState = computed(() => buildCartSelectionSummary(rawCartItems.value, selectionMap.value))
const cartEntries = computed(() => selectionState.value.entries)
const selectedSubtotal = computed(() => selectionState.value.selectedSubtotal)
const selectedUnitCount = computed(() => selectionState.value.selectedUnits)
const selectedProductCount = computed(() => selectionState.value.selectedProductCount)
const blockedProductCount = computed(() => selectionState.value.blockedProductCount)
const allSelectableSelected = computed(() => selectionState.value.allSelectableSelected)
const hasSelectableItems = computed(() => selectionState.value.hasSelectableItems)
const canProceedToShipping = computed(() => selectedProductCount.value > 0)

// Errores de validación para el banner de CheckoutValidationAlert
const selectionValidationErrors = computed(() => {
  if (blockedProductCount.value > 0 && selectedProductCount.value === 0) {
    return [{ icon: 'fas fa-triangle-exclamation', text: 'Los productos seleccionados no tienen stock suficiente. Ajusta las cantidades o elimínalos del carrito.' }]
  }
  return [{ icon: 'fas fa-shopping-cart', text: 'Selecciona al menos un producto disponible para continuar al envío.' }]
})

function buildCartQuery() {
  return {
    user_id: user.value?.id || undefined,
    session_id: user.value?.id ? undefined : sessionId.value,
  }
}

async function syncCartState() {
  const cartRes = await getCart(buildCartQuery())
  const nextCart = cartRes?.data

  cart.value = nextCart && typeof nextCart === 'object'
    ? { ...EMPTY_CART, ...nextCart }
    : { ...EMPTY_CART }

  selectionMap.value = synchronizeCartSelectionMap(rawCartItems.value)
  setCartCount(cart.value.item_count || 0)
}

async function loadCart() {
  loading.value = true
  errorMessage.value = ''

  try {
    await syncCartState()
    notifyStockAlerts(rawCartItems.value)
  } catch {
    errorMessage.value = 'No se pudo cargar el carrito.'
    cart.value = { ...EMPTY_CART }
    selectionMap.value = synchronizeCartSelectionMap([])
    setCartCount(0)
  } finally {
    loading.value = false
  }
}

async function onQuantityChange(item) {
  const itemId = Number(item?.item_id || 0)
  const nextQuantity = Math.max(1, Math.min(Number.parseInt(item.quantity, 10) || 1, maxQuantityForItem(item)))
  item.quantity = nextQuantity
  setBusyState(itemId, true)

  try {
    await updateCartItem(itemId, nextQuantity)
    await syncCartState()
    clearInlineFeedback(itemId)
  } catch (error) {
    const message = extractErrorMessage(error, 'No se pudo actualizar la cantidad.')
    showInlineFeedback(itemId, message, { tone: 'danger' })
    showSnackbar({ type: 'warning', title: 'Cantidad no actualizada', message })

    try {
      await syncCartState()
    } catch {
      errorMessage.value = 'No se pudo sincronizar el carrito.'
    }
  } finally {
    setBusyState(itemId, false)
  }
}

function onQuantityInput(item, event) {
  const itemId = Number(item?.item_id || 0)
  const availability = resolveCartItemAvailability(item)
  if (availability.soldOut) {
    return
  }

  const currentQuantity = Math.max(1, Number(item?.quantity || 1))
  const parsedQuantity = Number.parseInt(event?.target?.value, 10)
  const desiredQuantity = Number.isFinite(parsedQuantity) ? parsedQuantity : currentQuantity
  const normalizedQuantity = Math.max(1, Math.min(desiredQuantity, maxQuantityForItem(item)))

  if (desiredQuantity > maxQuantityForItem(item)) {
    showQuantityLimitFeedback(item)
  }

  if (normalizedQuantity === currentQuantity) {
    if (event?.target) {
      event.target.value = String(currentQuantity)
    }
    return
  }

  item.quantity = normalizedQuantity
  clearInlineFeedback(itemId)
  onQuantityChange(item)
}

function increaseQuantity(item) {
  const availability = resolveCartItemAvailability(item)

  if (availability.soldOut) {
    showInlineFeedback(Number(item?.item_id || 0), 'Este producto está agotado y se excluyó del pago.', { tone: 'muted' })
    return
  }

  if (!availability.canIncrease) {
    showQuantityLimitFeedback(item)
    return
  }

  item.quantity = Number(item.quantity || 1) + 1
  clearInlineFeedback(Number(item?.item_id || 0))
  onQuantityChange(item)
}

function decreaseQuantity(item) {
  const itemId = Number(item?.item_id || 0)
  const currentQuantity = Math.max(1, Number(item?.quantity || 1))
  if (currentQuantity <= 1) {
    return
  }

  item.quantity = currentQuantity - 1
  clearInlineFeedback(itemId)
  onQuantityChange(item)
}

async function deleteItem(itemId) {
  setBusyState(itemId, true)

  try {
    await removeCartItem(itemId)
    clearInlineFeedback(itemId)
    await syncCartState()
  } catch (error) {
    const message = extractErrorMessage(error, 'No se pudo eliminar el producto.')
    showSnackbar({ type: 'error', title: 'No se pudo eliminar', message })
  } finally {
    setBusyState(itemId, false)
  }
}

function toggleItemSelection(itemId, event) {
  selectionMap.value = setStoredCartItemSelection(itemId, Boolean(event?.target?.checked), selectionMap.value)
  // Ocultar el banner si ya hay productos seleccionados
  if (selectionValidationError.value && canProceedToShipping.value) {
    selectionValidationError.value = false
  }
}

function toggleAllSelections(event) {
  selectionMap.value = setAllStoredCartSelections(rawCartItems.value, Boolean(event?.target?.checked), selectionMap.value)
  if (selectionValidationError.value && canProceedToShipping.value) {
    selectionValidationError.value = false
  }
}

async function goToShipping() {
  if (!canProceedToShipping.value) {
    selectionValidationError.value = true
    await nextTick()
    const banner = document.querySelector('.checkout-validation-alert')
    if (banner) {
      banner.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
    return
  }

  selectionValidationError.value = false
  router.push({ name: 'shipping' })
}

function onItemImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'product')
}

function maxQuantityForItem(item) {
  return resolveCartItemAvailability(item).maxQuantity
}

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatUnitLabel(value) {
  return Number(value || 0) === 1 ? 'unidad' : 'unidades'
}

function lowStockBadgeText(availability) {
  return availability.availableStock === 1
    ? 'Última unidad'
    : `Quedan ${availability.availableStock}`
}

// Texto del overlay sobre la imagen del producto (estilo Mercado Libre)
function imageStockOverlayText(availability) {
  if (availability.availableStock === 1) return '¡Última unidad!'
  return `Últimas ${availability.availableStock} uds.`
}

// Snackbar informativo de stock al cargar el carrito
function notifyStockAlerts(items) {
  const soldOutCount = items.filter(i => resolveCartItemAvailability(i).soldOut).length
  const lowStockCount = items.filter((i) => {
    const av = resolveCartItemAvailability(i)
    return av.lowStock && !av.soldOut
  }).length

  if (soldOutCount > 0) {
    showSnackbar({
      type: 'warning',
      title: soldOutCount === 1 ? 'Producto agotado' : 'Productos agotados',
      message:
        soldOutCount === 1
          ? 'Un producto se quedó sin stock y no se incluirá en el pago.'
          : `${soldOutCount} productos se quedaron sin stock y no se incluirán en el pago.`,
    })
  } else if (lowStockCount > 0) {
    showSnackbar({
      type: 'info',
      title: 'Stock limitado',
      message:
        lowStockCount === 1
          ? 'Un producto tiene pocas unidades disponibles.'
          : `${lowStockCount} productos tienen pocas unidades disponibles.`,
    })
  }
}

function itemAvailabilityMessage(availability) {
  if (availability.soldOut) {
    return 'Este producto se quedó sin stock y salió del pago automáticamente.'
  }

  if (availability.exceedsStock) {
    return `Ajusta la cantidad. Solo quedan ${availability.availableStock} ${formatUnitLabel(availability.availableStock)} disponibles.`
  }

  return ''
}

function productRoute(item) {
  const slug = String(item?.product_slug || '').trim()
  if (slug) {
    return { name: 'product', params: { slug } }
  }

  return { name: 'store' }
}

function feedbackToneClass(tone) {
  if (tone === 'muted') return 'cart-item-feedback--muted'
  if (tone === 'warning') return 'cart-item-feedback--warning'
  return 'cart-item-feedback--danger'
}

function inlineFeedbackForItem(itemId) {
  return inlineFeedback.value[itemId] || null
}

function showQuantityLimitFeedback(item) {
  const availability = resolveCartItemAvailability(item)
  const itemId = Number(item?.item_id || 0)

  if (availability.availableStock <= 0) {
    showInlineFeedback(itemId, 'Este producto ya no tiene unidades disponibles.', { tone: 'danger' })
    return
  }

  showInlineFeedback(
    itemId,
    `Solo puedes continuar con ${availability.availableStock} ${formatUnitLabel(availability.availableStock)} en este producto.`,
    { tone: 'danger' },
  )
}

function showInlineFeedback(itemId, message, options = {}) {
  if (!itemId || !message) {
    return
  }

  clearInlineFeedback(itemId)

  const tone = String(options?.tone || 'danger').trim() || 'danger'
  const token = `${Date.now()}-${Math.random()}`

  inlineFeedback.value = {
    ...inlineFeedback.value,
    [itemId]: { message, tone, token },
  }

  const timeoutId = window.setTimeout(() => {
    if (inlineFeedback.value[itemId]?.token === token) {
      clearInlineFeedback(itemId)
    }
  }, INLINE_FEEDBACK_DURATION)

  inlineFeedbackTimers.set(itemId, timeoutId)
}

function clearInlineFeedback(itemId) {
  const timeoutId = inlineFeedbackTimers.get(itemId)
  if (timeoutId) {
    window.clearTimeout(timeoutId)
    inlineFeedbackTimers.delete(itemId)
  }

  if (!Object.prototype.hasOwnProperty.call(inlineFeedback.value, itemId)) {
    return
  }

  const nextFeedback = { ...inlineFeedback.value }
  delete nextFeedback[itemId]
  inlineFeedback.value = nextFeedback
}

function setBusyState(itemId, busy) {
  if (!itemId) {
    return
  }

  const nextBusyState = { ...busyItemIds.value }
  if (busy) {
    nextBusyState[itemId] = true
  } else {
    delete nextBusyState[itemId]
  }

  busyItemIds.value = nextBusyState
}

function isItemBusy(itemId) {
  return Boolean(busyItemIds.value[itemId])
}

function rowClasses(entry) {
  return {
    'cart-item-row--unselected': !entry.selected && entry.availability.isSelectable,
    'cart-item-row--sold-out': entry.availability.soldOut,
    'cart-item-row--warning': entry.availability.exceedsStock,
    'cart-item-row--attention': Boolean(inlineFeedbackForItem(entry.itemId)),
  }
}

function extractErrorMessage(error, fallback) {
  const apiError = String(error?.response?.data?.error || '').trim()
  if (apiError) {
    return apiError
  }

  const apiMessage = String(error?.response?.data?.message || '').trim()
  if (apiMessage) {
    return apiMessage
  }

  return fallback
}

onMounted(loadCart)
onUnmounted(() => {
  for (const timeoutId of inlineFeedbackTimers.values()) {
    window.clearTimeout(timeoutId)
  }
  inlineFeedbackTimers.clear()
})
</script>

<style scoped>
.cart-page-root {
  width: 100%;
  padding: 2rem 0 3.5rem;
}

.cart-page-shell {
  width: min(100%, 1480px);
  margin: 0 auto;
  padding: 0 1.5rem;
  animation: cartPageFadeIn 0.4s ease;
}

.cart-page-header {
  display: grid;
  gap: 2rem;
  margin-bottom: 1rem;
}

.cart-page-title {
  display: flex;
  align-items: center;
  gap: 1.4rem;
}

.cart-page-icon {
  width: 78px;
  height: 78px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.12);
  color: #0077b6;
  font-size: 2.4rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cart-page-heading {
  margin: 0;
  color: #333333;
  font-size: 3.2rem;
  font-weight: 700;
  line-height: 1.1;
}

.cart-page-steps {
  position: relative;
  display: flex;
  justify-content: space-between;
  width: min(100%, 760px);
  margin: 0 auto;
}

.cart-page-steps::before {
  content: '';
  position: absolute;
  top: 1.6rem;
  left: 0;
  right: 0;
  height: 2px;
  background: #e0e0e0;
  z-index: 0;
}

.cart-page-step {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.8rem;
}

.cart-page-step span {
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

.cart-page-step p {
  margin: 0;
  color: #666666;
  font-size: 1.4rem;
  font-weight: 500;
}

.cart-page-step--active span {
  background: #0077b6;
  color: #ffffff;
  transform: scale(1.08);
  box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.12);
  animation: cartStepPulse 2s ease-in-out infinite;
}

.cart-page-step--active p {
  color: #333333;
  font-weight: 600;
}

.cart-page-divider {
  height: 1px;
  background: #e0e0e0;
  margin-bottom: 2rem;
}

.cart-page-status {
  margin-top: 1rem;
}

.cart-page-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 34rem;
  gap: 3rem;
  align-items: start;
}

.cart-items-panel {
  overflow: hidden;
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cart-selection-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.6rem;
  padding: 1.7rem 2.2rem;
  border-bottom: 1px solid #e9eff4;
  background: #f9fbfd;
}

.cart-selection-toggle {
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  color: #17324d;
  font-size: 1.45rem;
  font-weight: 700;
}

.cart-selection-toggle--disabled {
  opacity: 0.6;
}

.cart-selection-toolbar__summary {
  margin: 0;
  color: #557087;
  font-size: 1.35rem;
  text-align: right;
}

.cart-selection-toolbar__summary strong {
  color: #17324d;
}

.cart-checkbox {
  position: relative;
  display: inline-flex;
  width: 2rem;
  height: 2rem;
  flex-shrink: 0;
}

.cart-checkbox input {
  position: absolute;
  inset: 0;
  margin: 0;
  opacity: 0;
  cursor: pointer;
}

.cart-checkbox__visual {
  width: 100%;
  height: 100%;
  border: 1.8px solid #bfd0dd;
  border-radius: 0.65rem;
  background: #ffffff;
  transition: all 0.2s ease;
}

.cart-checkbox__visual::after {
  content: '';
  position: absolute;
  top: 0.25rem;
  left: 0.66rem;
  width: 0.46rem;
  height: 0.92rem;
  border: solid transparent;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
  transition: border-color 0.2s ease;
}

.cart-checkbox input:checked + .cart-checkbox__visual {
  border-color: #0077b6;
  background: #0077b6;
  box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.12);
}

.cart-checkbox input:checked + .cart-checkbox__visual::after {
  border-color: #ffffff;
}

.cart-checkbox input:focus-visible + .cart-checkbox__visual {
  box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.18);
}

.cart-checkbox input:disabled {
  cursor: not-allowed;
}

.cart-checkbox input:disabled + .cart-checkbox__visual {
  background: #f1f5f8;
  border-color: #d5e0e8;
}

.cart-items-head {
  display: grid;
  grid-template-columns: 4.6rem 2fr 1fr 1.2fr 1fr 5rem;
  gap: 1.5rem;
  align-items: center;
  padding: 2rem 2.2rem;
  background: #0077b6;
  color: #ffffff;
  font-size: 1.4rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.cart-items-head span:nth-child(n + 3) {
  text-align: center;
}

.cart-items-head__selection,
.cart-items-head__ghost {
  display: block;
}

.cart-items-body {
  background: #ffffff;
}

.cart-item-row {
  display: grid;
  grid-template-columns: 4.6rem 2fr 1fr 1.2fr 1fr 5rem;
  grid-template-areas: 'select main price quantity total remove';
  gap: 1.5rem;
  align-items: center;
  padding: 2.4rem 2.2rem;
  border-bottom: 1px solid #e0e0e0;
  transition: background-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
  animation: cartRowFadeIn 0.35s ease;
}

.cart-item-row:last-child {
  border-bottom: none;
}

.cart-item-row:hover {
  background: #f6fbff;
  transform: translateY(-2px);
}

.cart-item-row--unselected {
  opacity: 0.78;
}

.cart-item-row--sold-out {
  background: #f7f7f8;
}

.cart-item-row--warning,
.cart-item-row--attention {
  background: #fff4f1;
  box-shadow: inset 0 0 0 1px rgba(217, 92, 69, 0.18);
}

.cart-item-select {
  grid-area: select;
  display: flex;
  justify-content: center;
}

.cart-item-select--blocked {
  opacity: 0.75;
}

.cart-item-main {
  grid-area: main;
  display: flex;
  align-items: center;
  gap: 1.8rem;
  min-width: 0;
}

.cart-item-media {
  position: relative;
  width: 12rem;
  height: 12rem;
  flex-shrink: 0;
  overflow: hidden;
  border: 1px solid #e0e0e0;
  border-radius: 1.6rem;
  background: #f9f9f9;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  text-decoration: none;
}

.cart-item-media::before {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0, 119, 182, 0.08);
  opacity: 0;
  transition: opacity 0.25s ease;
}

.cart-item-row:hover .cart-item-media::before {
  opacity: 1;
}

.cart-item-media--sold-out::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(24, 31, 43, 0.35);
}

.cart-item-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.25s ease;
}

.cart-item-row:hover .cart-item-media img {
  transform: scale(1.04);
}

/* Overlay de stock sobre imagen del producto - estilo Mercado Libre */
.cart-item-media-flag {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 2;
  padding: 0.42rem 0.5rem;
  border-radius: 0 0 1.5rem 1.5rem;
  color: #ffffff;
  font-size: 1.1rem;
  font-weight: 700;
  text-align: center;
  letter-spacing: 0.02em;
  line-height: 1.3;
}

.cart-item-media-flag--sold-out {
  background: rgba(24, 31, 43, 0.88);
}

.cart-item-media-flag--low-stock {
  background: rgba(162, 94, 0, 0.9);
}

.cart-item-copy {
  min-width: 0;
}

.cart-item-name {
  margin: 0 0 0.9rem;
  font-size: 1.8rem;
  font-weight: 600;
  line-height: 1.25;
}

.cart-item-name a {
  color: #333333;
  text-decoration: none;
}

.cart-item-name a:hover {
  color: #0077b6;
}

.cart-item-variants,
.cart-item-stock-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
}

.cart-item-stock-badges {
  margin-top: 1rem;
}

.cart-variant-pill,
.cart-stock-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.45rem 1.2rem;
  border-radius: 999px;
  font-size: 1.2rem;
  font-weight: 700;
}

.cart-variant-pill {
  border: 1px solid #e0e0e0;
  background: #f9f9f9;
  color: #666666;
  transition: all 0.25s ease;
}

.cart-item-row:hover .cart-variant-pill {
  background: #0077b6;
  border-color: #0077b6;
  color: #ffffff;
}

.cart-stock-badge--sold-out {
  background: #2b3640;
  color: #ffffff;
}

.cart-stock-badge--warning {
  background: #fff1eb;
  color: #c63d2f;
}

.cart-stock-badge--low {
  background: #fff5d8;
  color: #9a6400;
}

.cart-stock-badge--muted {
  background: #edf3f7;
  color: #52697c;
}

.cart-item-feedback {
  margin: 1rem 0 0;
  font-size: 1.28rem;
  line-height: 1.45;
}

.cart-item-feedback--danger {
  color: #c63d2f;
}

.cart-item-feedback--warning {
  color: #a25e00;
}

.cart-item-feedback--muted {
  color: #52697c;
}

.cart-data-cell {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333333;
  text-align: center;
}

.cart-item-price {
  grid-area: price;
}

.cart-item-total {
  grid-area: total;
  color: #005b8c;
}

.cart-item-quantity {
  grid-area: quantity;
  display: flex;
  justify-content: center;
}

.cart-quantity-stack {
  display: grid;
  gap: 0.7rem;
}

.cart-quantity-box {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem;
  border: 1px solid transparent;
  border-radius: 999px;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.cart-quantity-box--warning {
  border-color: rgba(217, 92, 69, 0.28);
  background: rgba(255, 235, 229, 0.7);
}

.cart-quantity-box--muted {
  background: #f1f5f8;
}

.cart-quantity-btn {
  width: 3.6rem;
  height: 3.6rem;
  border: 1px solid #e0e0e0;
  border-radius: 50%;
  background: #ffffff;
  color: #666666;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
}

.cart-quantity-btn:hover:not(:disabled) {
  background: #0077b6;
  border-color: #0077b6;
  color: #ffffff;
  transform: scale(1.05);
}

.cart-quantity-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  box-shadow: none;
}

.cart-quantity-input {
  width: 5.8rem;
  height: 4.2rem;
  border: 1px solid #e0e0e0;
  border-radius: 1.2rem;
  background: #ffffff;
  color: #333333;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cart-quantity-input:focus {
  outline: none;
  border-color: #0077b6;
  box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.12);
}

.cart-quantity-input:disabled {
  background: #f1f5f8;
  color: #7c8a97;
  box-shadow: none;
}

.cart-quantity-input::-webkit-outer-spin-button,
.cart-quantity-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.cart-quantity-hint {
  display: block;
  color: #7d6e35;
  font-size: 1.15rem;
  font-weight: 700;
  text-align: center;
}

.cart-item-remove {
  grid-area: remove;
  display: flex;
  justify-content: center;
}

.cart-remove-btn {
  width: 3.8rem;
  height: 3.8rem;
  border: 1px solid #e0e0e0;
  border-radius: 50%;
  background: #ffffff;
  color: #777777;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
}

.cart-remove-btn:hover:not(:disabled) {
  background: #ff3333;
  border-color: #ff3333;
  color: #ffffff;
  transform: rotate(12deg) scale(1.05);
}

.cart-remove-btn:disabled {
  cursor: wait;
  opacity: 0.6;
}

.cart-items-footer {
  padding: 2rem;
  border-top: 1px solid #e0e0e0;
  background: #f9f9f9;
  display: flex;
  justify-content: center;
}

.cart-secondary-action,
.cart-primary-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.9rem;
  min-height: 5.4rem;
  border-radius: 999px;
  text-decoration: none;
  font-size: 1.6rem;
  font-weight: 700;
  transition: all 0.25s ease;
}

.cart-secondary-action {
  padding: 0.9rem 2.2rem;
  border: 2px solid #0077b6;
  color: #0077b6;
  background: #ffffff;
}

.cart-secondary-action:hover {
  transform: translateX(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.cart-secondary-action--filled {
  background: #0077b6;
  color: #ffffff;
}

.cart-secondary-action--filled:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}

.cart-primary-action {
  width: 100%;
  padding: 0.9rem 1.8rem;
  border: none;
  background: #0077b6;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  cursor: pointer;
}

.cart-primary-action:hover:not(:disabled) {
  background: #005b8c;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}

.cart-primary-action:disabled {
  background: #b7c8d5;
  color: #eef4f8;
  cursor: not-allowed;
  box-shadow: none;
}

.cart-primary-action i,
.cart-secondary-action i {
  transition: transform 0.25s ease;
}

.cart-primary-action:hover:not(:disabled) i {
  transform: translateX(4px);
}

.cart-secondary-action:hover i {
  transform: translateX(-4px);
}

.cart-order-panel {
  position: sticky;
  top: 12rem;
}

.cart-order-box {
  padding: 2.6rem;
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: box-shadow 0.25s ease;
}

.cart-order-box:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.cart-order-title {
  margin: 0 0 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  color: #333333;
  font-size: 1.8rem;
  font-weight: 700;
}

.cart-order-rows {
  display: grid;
  gap: 1.4rem;
  margin-bottom: 1.6rem;
}

.cart-order-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.2rem;
  color: #444444;
  font-size: 1.5rem;
}

.cart-order-row strong {
  color: #0077b6;
  font-weight: 700;
  text-align: right;
}

.cart-order-row--selection strong {
  color: #17324d;
}

.cart-order-row--total {
  padding-top: 1.6rem;
  margin-top: 0.4rem;
  border-top: 2px dashed #e0e0e0;
  color: #005b8c;
  font-size: 1.8rem;
  font-weight: 700;
}

.cart-order-row--total strong {
  color: #005b8c;
}

.cart-order-shipping-note {
  max-width: 18rem;
  line-height: 1.45;
}

.cart-order-note {
  margin: 0 0 1.5rem;
  color: #5b7082;
  font-size: 1.28rem;
  line-height: 1.45;
}

.cart-order-note--warning {
  color: #b3553a;
}

.cart-empty-state {
  max-width: 60rem;
  margin: 1rem auto 0;
  padding: 4rem 2rem;
  border: 1px solid #e0e0e0;
  border-radius: 2.4rem;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  text-align: center;
}

.cart-empty-icon {
  width: 10rem;
  height: 10rem;
  margin: 0 auto 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.12);
  color: #0077b6;
  font-size: 3.4rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cart-empty-state h2 {
  margin: 0 0 1rem;
  color: #333333;
  font-size: 2.2rem;
  font-weight: 700;
}

.cart-empty-state p {
  margin: 0 0 2.4rem;
  color: #666666;
  font-size: 1.6rem;
  line-height: 1.6;
}

.cart-inline-feedback-enter-active,
.cart-inline-feedback-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.cart-inline-feedback-enter-from,
.cart-inline-feedback-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@keyframes cartPageFadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes cartRowFadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes cartStepPulse {
  0%,
  100% {
    box-shadow: 0 0 0 0 rgba(0, 119, 182, 0.4);
  }

  50% {
    box-shadow: 0 0 0 10px rgba(0, 119, 182, 0);
  }
}

@media (max-width: 1024px) {
  .cart-page-layout {
    grid-template-columns: 1fr;
  }

  .cart-order-panel {
    position: static;
    top: auto;
  }
}

@media (max-width: 900px) {
  .cart-page-heading {
    font-size: 2.8rem;
  }

  .cart-selection-toolbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .cart-selection-toolbar__summary {
    text-align: left;
  }

  .cart-items-head {
    display: none;
  }

  .cart-item-row {
    grid-template-columns: 1fr auto;
    grid-template-areas:
      'select remove'
      'main main'
      'price price'
      'quantity quantity'
      'total total';
    gap: 1.2rem;
  }

  .cart-item-select {
    justify-content: flex-start;
  }

  .cart-data-cell,
  .cart-item-remove,
  .cart-item-quantity {
    text-align: left;
    justify-content: flex-start;
  }

  .cart-data-cell::before {
    content: attr(data-label);
    display: block;
    margin-bottom: 0.6rem;
    color: #666666;
    font-size: 1.2rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .cart-quantity-hint {
    text-align: left;
  }
}

@media (max-width: 640px) {
  .cart-page-root {
    padding-top: 1.2rem;
    padding-bottom: 2rem;
  }

  .cart-page-shell {
    padding: 0 0.8rem;
  }

  .cart-page-title {
    gap: 0.9rem;
  }

  .cart-page-icon {
    width: 5.6rem;
    height: 5.6rem;
    font-size: 1.8rem;
  }

  .cart-page-heading {
    font-size: 2rem;
  }

  .cart-page-steps {
    gap: 0.5rem;
  }

  .cart-page-step p {
    font-size: 1.05rem;
  }

  .cart-selection-toolbar,
  .cart-order-box {
    padding-left: 1.2rem;
    padding-right: 1.2rem;
  }

  /* Layout ML-style: [checkbox] [imagen+info] [eliminar] en la misma fila */
  .cart-item-row {
    grid-template-columns: 2.2rem 1fr auto;
    grid-template-areas:
      'select main remove'
      '. price .'
      '. quantity .'
      '. total .';
    gap: 0.6rem 0.8rem;
    padding: 1.2rem 1rem;
    align-items: start;
  }

  .cart-item-select {
    padding-top: 0.5rem;
    justify-content: flex-start;
  }

  .cart-item-remove {
    padding-top: 0.3rem;
    justify-content: flex-end;
  }

  .cart-item-main {
    align-items: flex-start;
    gap: 0.9rem;
  }

  .cart-item-media {
    width: 8rem;
    height: 8rem;
    border-radius: 1.2rem;
    flex-shrink: 0;
  }

  .cart-item-name {
    font-size: 1.45rem;
    margin-bottom: 0.5rem;
  }

  /* Ocultar etiquetas data-label en móvil para diseño más limpio */
  .cart-data-cell::before {
    display: none;
  }

  .cart-item-price {
    text-align: left;
    font-size: 1.3rem;
    font-weight: 500;
    color: #666666;
  }

  .cart-item-quantity {
    justify-content: flex-start;
  }

  .cart-item-total {
    text-align: left;
    font-size: 1.5rem;
    font-weight: 700;
    color: #005b8c;
  }

  /* Controles de cantidad más compactos en móvil */
  .cart-quantity-btn {
    width: 3rem;
    height: 3rem;
  }

  .cart-quantity-input {
    width: 4.6rem;
    height: 3.6rem;
    font-size: 1.4rem;
  }

  .cart-quantity-hint {
    text-align: left;
    font-size: 1.1rem;
  }

  .cart-order-row {
    font-size: 1.4rem;
  }

  .cart-order-row--total {
    font-size: 1.7rem;
  }

  .cart-primary-action,
  .cart-secondary-action {
    font-size: 1.5rem;
  }
}
</style>
