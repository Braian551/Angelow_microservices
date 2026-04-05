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
            <p>Env&iacute;o</p>
          </div>
          <div class="cart-page-step">
            <span>3</span>
            <p>Pago</p>
          </div>
          <div class="cart-page-step">
            <span>4</span>
            <p>Confirmaci&oacute;n</p>
          </div>
        </div>
      </header>

      <div class="cart-page-divider" />

      <p v-if="loading" class="loading-box cart-page-status">Cargando carrito...</p>
      <p v-else-if="errorMessage" class="error-box cart-page-status">{{ errorMessage }}</p>

      <section v-else-if="cartItems.length === 0" class="cart-empty-state">
        <div class="cart-empty-icon">
          <i class="fas fa-shopping-cart" />
        </div>
        <h2>Tu carrito est&aacute; vac&iacute;o</h2>
        <p>&iexcl;Explora nuestra tienda y descubre productos incre&iacute;bles!</p>
        <RouterLink :to="{ name: 'store' }" class="cart-secondary-action cart-secondary-action--filled">
          <i class="fas fa-arrow-left" />
          <span>Continuar comprando</span>
        </RouterLink>
      </section>

      <section v-else class="cart-page-layout">
        <article class="cart-items-panel">
          <header class="cart-items-head">
            <span>Producto</span>
            <span>Precio</span>
            <span>Cantidad</span>
            <span>Total</span>
            <span class="cart-items-head__ghost" aria-hidden="true" />
          </header>

          <div class="cart-items-body">
            <article v-for="item in cartItems" :key="item.item_id" class="cart-item-row">
              <div class="cart-item-main">
                <RouterLink :to="productRoute(item)" class="cart-item-media">
                  <img
                    :src="resolveMediaUrl(item.product_image, 'product')"
                    :alt="item.product_name"
                    @error="onItemImageError($event, item.product_image)"
                  />
                </RouterLink>

                <div class="cart-item-copy">
                  <h2 class="cart-item-name">
                    <RouterLink :to="productRoute(item)">
                      {{ item.product_name }}
                    </RouterLink>
                  </h2>

                  <div v-if="item.color_name || item.size_name" class="cart-item-variants">
                    <span v-if="item.color_name" class="cart-variant-pill">
                      Color: {{ item.color_name }}
                    </span>
                    <span v-if="item.size_name" class="cart-variant-pill">
                      Talla: {{ item.size_name }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="cart-item-price cart-data-cell" data-label="Precio">
                {{ formatPrice(item.price) }}
              </div>

              <div class="cart-item-quantity cart-data-cell" data-label="Cantidad">
                <div class="cart-quantity-box">
                  <button
                    type="button"
                    class="cart-quantity-btn"
                    :aria-label="`Disminuir cantidad de ${item.product_name}`"
                    @click="decreaseQuantity(item)"
                  >
                    <i class="fas fa-minus" />
                  </button>

                  <input
                    :value="item.quantity"
                    type="number"
                    min="1"
                    :max="item.available_stock > 0 ? item.available_stock : undefined"
                    class="cart-quantity-input"
                    :aria-label="`Cantidad de ${item.product_name}`"
                    @change="onQuantityInput(item, $event)"
                  />

                  <button
                    type="button"
                    class="cart-quantity-btn"
                    :aria-label="`Aumentar cantidad de ${item.product_name}`"
                    @click="increaseQuantity(item)"
                  >
                    <i class="fas fa-plus" />
                  </button>
                </div>
              </div>

              <div class="cart-item-total cart-data-cell" data-label="Total">
                {{ formatPrice(itemTotal(item)) }}
              </div>

              <div class="cart-item-remove">
                <button
                  type="button"
                  class="cart-remove-btn"
                  :aria-label="`Eliminar ${item.product_name} del carrito`"
                  @click="deleteItem(item.item_id)"
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
              <div class="cart-order-row">
                <span>Subtotal</span>
                <strong>{{ formatPrice(orderSubtotal) }}</strong>
              </div>

              <div class="cart-order-row">
                <span>Env&iacute;o</span>
                <strong class="cart-order-shipping-note">Se calcula en el siguiente paso</strong>
              </div>

              <div class="cart-order-row cart-order-row--total">
                <span>Total</span>
                <strong>{{ formatPrice(orderSubtotal) }}</strong>
              </div>
            </div>

            <RouterLink :to="{ name: 'shipping' }" class="cart-primary-action">
              <span>Proceder al pago</span>
              <i class="fas fa-arrow-right" />
            </RouterLink>
          </div>
        </aside>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAppShell } from '../../../composables/useAppShell'
import { useSession } from '../../../composables/useSession'
import { getCart, removeCartItem, updateCartItem } from '../../../services/cartApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

const EMPTY_CART = {
  items: [],
  item_count: 0,
  subtotal: 0,
}

const { sessionId, user } = useSession()
const { setCartCount } = useAppShell()

const loading = ref(true)
const errorMessage = ref('')
const cart = ref({ ...EMPTY_CART })

const cartItems = computed(() => (Array.isArray(cart.value?.items) ? cart.value.items : []))
const orderSubtotal = computed(() => Number(cart.value?.subtotal || 0))

async function loadCart() {
  loading.value = true
  errorMessage.value = ''

  try {
    const cartRes = await getCart({
      user_id: user.value?.id || undefined,
      session_id: user.value?.id ? undefined : sessionId.value,
    })

    const nextCart = cartRes?.data
    cart.value = nextCart && typeof nextCart === 'object'
      ? { ...EMPTY_CART, ...nextCart }
      : { ...EMPTY_CART }

    setCartCount(cart.value.item_count || 0)
  } catch {
    errorMessage.value = 'No se pudo cargar el carrito.'
    cart.value = { ...EMPTY_CART }
    setCartCount(0)
  } finally {
    loading.value = false
  }
}

async function onQuantityChange(item) {
  const nextQuantity = Math.max(1, Number.parseInt(item.quantity, 10) || 1)

  try {
    await updateCartItem(item.item_id, nextQuantity)
    await loadCart()
  } catch {
    errorMessage.value = 'No se pudo actualizar la cantidad.'
  }
}

function onQuantityInput(item, event) {
  item.quantity = Math.max(1, Number.parseInt(event?.target?.value, 10) || Number(item.quantity || 1))
  onQuantityChange(item)
}

function increaseQuantity(item) {
  item.quantity = Number(item.quantity || 1) + 1
  onQuantityChange(item)
}

function decreaseQuantity(item) {
  item.quantity = Math.max(1, Number(item.quantity || 1) - 1)
  onQuantityChange(item)
}

async function deleteItem(itemId) {
  try {
    await removeCartItem(itemId)
    await loadCart()
  } catch {
    errorMessage.value = 'No se pudo eliminar el producto.'
  }
}

function onItemImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'product')
}

function itemTotal(item) {
  const total = Number(item.line_total ?? item.total ?? item.item_total)
  if (Number.isFinite(total) && total > 0) return total

  return Number(item.price || 0) * Number(item.quantity || 1)
}

function productRoute(item) {
  const slug = String(item.product_slug || '').trim()
  if (slug) {
    return { name: 'product', params: { slug } }
  }

  return { name: 'store' }
}

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

onMounted(loadCart)
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

.cart-items-head {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 5rem;
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

.cart-items-head span:nth-child(n + 2) {
  text-align: center;
}

.cart-items-head__ghost {
  display: block;
}

.cart-items-body {
  background: #ffffff;
}

.cart-item-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 5rem;
  gap: 1.5rem;
  align-items: center;
  padding: 2.4rem 2.2rem;
  border-bottom: 1px solid #e0e0e0;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  animation: cartRowFadeIn 0.35s ease;
}

.cart-item-row:last-child {
  border-bottom: none;
}

.cart-item-row:hover {
  background: #e6f2ff;
  transform: translateY(-2px);
}

.cart-item-main {
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
  background: linear-gradient(135deg, rgba(0, 119, 182, 0.1) 0%, rgba(0, 119, 182, 0) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.cart-item-row:hover .cart-item-media::before {
  opacity: 1;
}

.cart-item-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.cart-item-row:hover .cart-item-media img {
  transform: scale(1.05);
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

.cart-item-variants {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
}

.cart-variant-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.45rem 1.2rem;
  border: 1px solid #e0e0e0;
  border-radius: 999px;
  background: #f9f9f9;
  color: #666666;
  font-size: 1.2rem;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.cart-item-row:hover .cart-variant-pill {
  background: #0077b6;
  border-color: #0077b6;
  color: #ffffff;
}

.cart-data-cell {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333333;
  text-align: center;
}

.cart-item-total {
  color: #005b8c;
}

.cart-item-quantity {
  display: flex;
  justify-content: center;
}

.cart-quantity-box {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
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
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.cart-quantity-btn:hover {
  background: #0077b6;
  border-color: #0077b6;
  color: #ffffff;
  transform: scale(1.08);
}

.cart-quantity-input {
  width: 5.6rem;
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

.cart-quantity-input::-webkit-outer-spin-button,
.cart-quantity-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.cart-item-remove {
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
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.cart-remove-btn:hover {
  background: #ff3333;
  border-color: #ff3333;
  color: #ffffff;
  transform: rotate(360deg) scale(1.08);
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
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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
  background: #0077b6;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.cart-primary-action:hover {
  background: #005b8c;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}

.cart-primary-action i,
.cart-secondary-action i {
  transition: transform 0.3s ease;
}

.cart-primary-action:hover i {
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
  transition: box-shadow 0.3s ease;
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
  margin-bottom: 2rem;
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

  .cart-items-head {
    display: none;
  }

  .cart-item-row {
    grid-template-columns: 1fr;
    gap: 1.2rem;
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
}

@media (max-width: 640px) {
  .cart-page-root {
    padding-top: 1.4rem;
    padding-bottom: 2.4rem;
  }

  .cart-page-shell {
    padding: 0 1rem;
  }

  .cart-page-title {
    gap: 1rem;
  }

  .cart-page-icon {
    width: 6.2rem;
    height: 6.2rem;
    font-size: 2rem;
  }

  .cart-page-heading {
    font-size: 2.3rem;
  }

  .cart-page-steps {
    gap: 0.8rem;
  }

  .cart-page-step p {
    font-size: 1.1rem;
  }

  .cart-item-row,
  .cart-order-box {
    padding: 1.8rem 1.4rem;
  }

  .cart-item-main {
    align-items: flex-start;
    gap: 1.2rem;
  }

  .cart-item-media {
    width: 9.4rem;
    height: 9.4rem;
  }

  .cart-item-name {
    font-size: 1.6rem;
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
