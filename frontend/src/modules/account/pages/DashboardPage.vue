<template>
  <section class="dashboard-header">
    <h1>Bienvenido a tu cuenta</h1>
    <p>Aquí puedes gestionar tus pedidos, direcciones y preferencias.</p>
  </section>

  <section class="dashboard-summary">
    <article class="summary-card">
      <div class="summary-icon">
        <i class="fas fa-shopping-bag" />
      </div>
      <div class="summary-content">
        <h3>Pedidos</h3>
        <p>{{ ordersCountLabel }}</p>
        <RouterLink :to="{ name: 'account-orders' }">Ver historial</RouterLink>
      </div>
    </article>

    <article class="summary-card">
      <div class="summary-icon">
        <i class="fas fa-map-marker-alt" />
      </div>
      <div class="summary-content">
        <h3>Direcciones</h3>
        <p>{{ addressesCountLabel }}</p>
        <RouterLink :to="{ name: 'account-addresses' }">Gestionar direcciones</RouterLink>
      </div>
    </article>

    <article class="summary-card">
      <div class="summary-icon">
        <i class="fas fa-heart" />
      </div>
      <div class="summary-content">
        <h3>Favoritos</h3>
        <p>{{ favoritesCountLabel }}</p>
        <RouterLink :to="{ name: 'account-wishlist' }">Ver favoritos</RouterLink>
      </div>
    </article>
  </section>

  <section class="account-card">
    <header class="section-header">
      <h2>Pedidos recientes</h2>
      <RouterLink :to="{ name: 'account-orders' }" class="view-all">Ver todos</RouterLink>
    </header>

    <p v-if="loading" class="loading-box">Cargando pedidos...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <div v-else-if="recentOrders.length === 0" class="empty-state">
      <i class="fas fa-box-open" />
      <p>Aún no has realizado ningún pedido.</p>
      <RouterLink :to="{ name: 'store' }" class="btn-primary-small">Ir a la tienda</RouterLink>
    </div>

    <div v-else class="orders-list">
      <article v-for="order in recentOrders" :key="order.id" class="order-card">
        <div class="order-header">
          <div class="order-title">
            <h3>Pedido #{{ order.order_number }}</h3>
            <span class="order-date">{{ formatDate(order.created_at) }}</span>
          </div>
          <span class="status-badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
        </div>

        <div class="order-details">
          <div class="order-info">
            <i class="fas fa-box" />
            <span>{{ order.items_count || 0 }} producto(s)</span>
          </div>
          <div class="order-total">{{ formatPrice(order.total) }}</div>
        </div>

        <div class="order-actions">
          <RouterLink :to="{ name: 'account-orders', query: { order: order.id } }" class="btn-view-order">
            Ver detalles
          </RouterLink>
        </div>
      </article>
    </div>
  </section>

  <section v-if="favoriteProducts.length > 0" class="account-card">
    <header class="section-header">
      <h2>Tus favoritos</h2>
      <RouterLink :to="{ name: 'account-wishlist' }" class="view-all">Ver favoritos</RouterLink>
    </header>

    <div class="products-grid">
      <ProductCard
        v-for="product in favoriteProducts"
        :key="product.id"
        :product="product"
        @add-cart="openProduct"
        @wishlist-change="onWishlistChange"
      />
    </div>
  </section>

  <section v-if="recommendedShowcase.length > 0" class="account-card">
    <header class="section-header">
      <h2>Recomendaciones para ti</h2>
      <RouterLink :to="{ name: 'store' }" class="view-all">Ver tienda</RouterLink>
    </header>

    <div class="products-grid">
      <ProductCard
        v-for="product in recommendedShowcase"
        :key="product.id"
        :product="product"
        @add-cart="openProduct"
        @wishlist-change="onWishlistChange"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import ProductCard from '../../catalog/components/ProductCard.vue'
import { getProducts } from '../../../services/catalogApi'
import { getOrders } from '../../../services/orderApi'
import { getUserAddresses } from '../../../services/shippingApi'
import { getWishlist } from '../../../services/wishlistApi'
import { useSession } from '../../../composables/useSession'
import { getOrderStatusLabel, normalizeOrderStatus } from '../../../utils/orderPresentation'

const router = useRouter()
const { user, isLoggedIn } = useSession()

const loading = ref(true)
const errorMessage = ref('')
const orders = ref([])
const addresses = ref([])
const favorites = ref([])
const recommendedProducts = ref([])

const favoriteProducts = computed(() => favorites.value.slice(0, 6))

const recommendedShowcase = computed(() => {
  const favoriteIds = new Set(favorites.value.map((item) => Number(item.id || 0)))

  return recommendedProducts.value
    .filter((item) => !favoriteIds.has(Number(item.id || 0)))
    .slice(0, 6)
})

const recentOrders = computed(() => orders.value.slice(0, 3))

const ordersCountLabel = computed(() => {
  const total = orders.value.length
  return `${total} pedido${total === 1 ? '' : 's'} realizado${total === 1 ? '' : 's'}`
})

const addressesCountLabel = computed(() => {
  const total = addresses.value.length
  return `${total} dirección${total === 1 ? '' : 'es'} guardada${total === 1 ? '' : 's'}`
})

const favoritesCountLabel = computed(() => {
  const total = favorites.value.length
  return `${total} producto${total === 1 ? '' : 's'} guardado${total === 1 ? '' : 's'}`
})

onMounted(async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    if (!isLoggedIn.value) {
      return
    }

    const userId = String(user.value?.id || '').trim()
    const userEmail = String(user.value?.email || '').trim()

    const [
      ordersResult,
      addressesResult,
      wishlistResult,
      productsResult,
    ] = await Promise.allSettled([
      getOrders({
        user_id: userId || undefined,
        user_email: userEmail || undefined,
      }),
      getUserAddresses(userId, userEmail),
      getWishlist(userId || undefined, userEmail || undefined),
      getProducts({
        per_page: 6,
        user_id: userId || undefined,
        user_email: userEmail || undefined,
      }),
    ])

    orders.value = ordersResult.status === 'fulfilled' && Array.isArray(ordersResult.value?.data)
      ? ordersResult.value.data
      : []

    addresses.value = addressesResult.status === 'fulfilled' && Array.isArray(addressesResult.value?.data)
      ? addressesResult.value.data
      : []

    favorites.value = wishlistResult.status === 'fulfilled' && Array.isArray(wishlistResult.value?.data)
      ? wishlistResult.value.data.map((item) => ({
        ...item,
        is_favorite: 1,
      }))
      : []

    recommendedProducts.value = productsResult.status === 'fulfilled'
      && Array.isArray(productsResult.value?.data?.products)
      ? productsResult.value.data.products
      : []

    if (
      ordersResult.status === 'rejected'
      && addressesResult.status === 'rejected'
      && wishlistResult.status === 'rejected'
    ) {
      errorMessage.value = 'No se pudieron cargar los datos del dashboard.'
    }
  } catch {
    errorMessage.value = 'No se pudieron cargar los datos principales de tu cuenta.'
  } finally {
    loading.value = false
  }
})

function openProduct(product) {
  router.push({ name: 'product', params: { slug: product.slug } })
}

function onWishlistChange(payload) {
  const productId = Number(payload?.productId)
  const isFavorite = Boolean(payload?.isFavorite)

  favorites.value = favorites.value
    .map((item) => {
      if (Number(item.id) !== productId) {
        return item
      }

      return {
        ...item,
        is_favorite: isFavorite ? 1 : 0,
      }
    })
    .filter((item) => Number(item.is_favorite) === 1)

  recommendedProducts.value = recommendedProducts.value.map((item) => {
    if (Number(item.id) !== productId) {
      return item
    }

    return {
      ...item,
      is_favorite: isFavorite ? 1 : 0,
    }
  })
}

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('es-CO')
}

function statusLabel(status) {
  return getOrderStatusLabel(status) || status || 'Sin estado'
}

function statusClass(status) {
  const normalizedStatus = normalizeOrderStatus(status)
  const rawStatus = String(status || '').trim().toLowerCase().replace(/\s+/g, '_').replace(/-/g, '_')

  if (normalizedStatus === 'pending') return 'status-pending'
  if (['in_review', 'processing'].includes(normalizedStatus)) return 'status-processing'
  if (['paid', 'confirmed', 'shipped'].includes(rawStatus)) return `status-${rawStatus}`
  if (['delivered', 'completed'].includes(normalizedStatus)) return `status-${normalizedStatus}`
  if (['cancelled', 'canceled', 'failed', 'refunded'].includes(rawStatus)) return 'status-cancelled'

  return 'status-processing'
}
</script>
