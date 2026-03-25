<template>
  <SiteHeader :settings="settings" :cart-count="cart.item_count || 0" />

  <main class="section-container">
    <section class="cart-header">
      <h1 class="section-title">Carrito</h1>
      <p>{{ cart.item_count || 0 }} productos</p>
    </section>

    <p v-if="loading" class="loading-box">Cargando carrito...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <section v-else-if="(cart.items || []).length === 0" class="empty-cart">
      <p>Tu carrito esta vacio.</p>
      <RouterLink :to="{ name: 'store' }" class="btn">Ir a tienda</RouterLink>
    </section>

    <section v-else class="cart-grid">
      <article v-for="item in cart.items" :key="item.item_id" class="cart-item-card">
        <img :src="resolveMediaUrl(item.product_image, '/logo.png')" :alt="item.product_name" />
        <div class="cart-item-info">
          <h3>{{ item.product_name }}</h3>
          <p>{{ item.size_name }} - {{ item.color_name }}</p>
          <p>{{ formatPrice(item.price) }}</p>
          <div class="cart-item-actions">
            <input v-model.number="item.quantity" type="number" min="1" @change="onQuantityChange(item)" />
            <button type="button" @click="deleteItem(item.item_id)">Eliminar</button>
          </div>
        </div>
      </article>

      <aside class="cart-summary">
        <h2>Resumen</h2>
        <p>Subtotal: {{ formatPrice(cart.subtotal || 0) }}</p>
        <RouterLink :to="{ name: 'shipping' }" class="btn">Continuar a envio</RouterLink>
      </aside>
    </section>
  </main>

  <SiteFooter :settings="settings" />
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import SiteHeader from '../../../components/layout/SiteHeader.vue'
import SiteFooter from '../../../components/layout/SiteFooter.vue'
import { getHomeData } from '../../../services/catalogApi'
import { getCart, removeCartItem, updateCartItem } from '../../../services/cartApi'
import { useSession } from '../../../composables/useSession'
import { resolveMediaUrl } from '../../../utils/media'

const { sessionId, user } = useSession()

const loading = ref(true)
const errorMessage = ref('')
const settings = ref({})
const cart = ref({
  items: [],
  item_count: 0,
  subtotal: 0,
})

async function loadCart() {
  loading.value = true
  errorMessage.value = ''
  try {
    const [homeRes, cartRes] = await Promise.all([
      getHomeData(),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
    ])
    settings.value = homeRes?.data?.settings || {}
    cart.value = cartRes?.data || cart.value
  } catch {
    errorMessage.value = 'No se pudo cargar el carrito.'
  } finally {
    loading.value = false
  }
}

async function onQuantityChange(item) {
  try {
    await updateCartItem(item.item_id, Number(item.quantity || 1))
    await loadCart()
  } catch {
    errorMessage.value = 'No se pudo actualizar la cantidad.'
  }
}

async function deleteItem(itemId) {
  try {
    await removeCartItem(itemId)
    await loadCart()
  } catch {
    errorMessage.value = 'No se pudo eliminar el producto.'
  }
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
.cart-grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
  gap: 1.5rem;
}

.cart-item-card {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.cart-item-card img {
  width: 100%;
  border-radius: 8px;
}

.cart-item-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.cart-item-actions input {
  width: 80px;
  padding: 0.5rem;
}

.cart-summary {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  height: fit-content;
}

.empty-cart {
  margin-top: 2rem;
}

@media (max-width: 900px) {
  .cart-grid {
    grid-template-columns: 1fr;
  }
}
</style>
