<template>
  <section class="wishlist-page-container">
    <div class="wishlist-header">
      <h1><i class="fas fa-heart" /> Mi Lista de Deseos</h1>
      <p>Guarda tus productos favoritos aquí</p>
    </div>

    <template v-if="loading">
      <section class="account-card">
        <p class="loading-box">Cargando favoritos...</p>
      </section>
    </template>

    <template v-else-if="errorMessage">
      <section class="account-card">
        <p class="error-box">{{ errorMessage }}</p>
      </section>
    </template>

    <template v-else-if="products.length > 0">
      <section class="wishlist-controls">
        <div class="wishlist-actions">
          <div class="total-products">
            <p>{{ products.length }} producto{{ products.length === 1 ? '' : 's' }} en tu lista</p>
          </div>

          <button type="button" class="clear-all-btn" :disabled="busy" @click="confirmClearAll">
            <i class="fas fa-trash" />
            Limpiar lista
          </button>
        </div>

        <div class="wishlist-stats">
          <article class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-heart" />
            </div>
            <div class="stat-content">
              <span class="stat-value">{{ products.length }}</span>
              <span class="stat-label">Producto{{ products.length === 1 ? '' : 's' }} guardado{{ products.length === 1 ? '' : 's' }}</span>
            </div>
          </article>
        </div>
      </section>

      <section class="products-grid">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
          @add-cart="openProduct"
          @wishlist-change="onWishlistChange"
        />
      </section>
    </template>

    <div v-else class="empty-wishlist">
      <div class="empty-wishlist-content">
        <i class="fas fa-heart-broken" />
        <h2>Tu lista de deseos está vacía</h2>
        <p>Agrega productos a tu lista de deseos para guardarlos aquí</p>
        <RouterLink :to="{ name: 'store' }" class="btn">
          <i class="fas fa-shopping-bag" />
          Explorar productos
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import ProductCard from '../../catalog/components/ProductCard.vue'
import { useSession } from '../../../composables/useSession'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { getWishlist, toggleWishlist } from '../../../services/wishlistApi'
import '../views/WishlistView.css'

const router = useRouter()
const { user, isLoggedIn } = useSession()
const { showAlert } = useAlertSystem()

const loading = ref(true)
const busy = ref(false)
const errorMessage = ref('')
const products = ref([])

onMounted(async () => {
  await loadWishlist()
})

async function loadWishlist() {
  loading.value = true
  errorMessage.value = ''

  try {
    if (!isLoggedIn.value || !user.value?.id) {
      products.value = []
      return
    }

    const wishlistResponse = await getWishlist(currentUserId(), currentUserEmail())

    products.value = (Array.isArray(wishlistResponse?.data) ? wishlistResponse.data : []).map((item) => ({
      ...item,
      is_favorite: 1,
      avg_rating: Number(item.avg_rating || 0),
      review_count: Number(item.review_count || 0),
    }))
  } catch {
    errorMessage.value = 'No se pudieron cargar los favoritos.'
  } finally {
    loading.value = false
  }
}

function openProduct(product) {
  router.push({ name: 'product', params: { slug: product.slug } })
}

function onWishlistChange(event) {
  if (event?.isFavorite) return
  products.value = products.value.filter((item) => Number(item.id) !== Number(event.productId))
}

function confirmClearAll() {
  if (!products.value.length || busy.value) return

  showAlert({
    type: 'question',
    title: 'Limpiar lista de deseos',
    message: '¿Deseas eliminar todos los productos de tus favoritos?',
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Limpiar lista',
        style: 'danger',
        callback: async () => {
          await clearAllWishlist()
        },
      },
    ],
  })
}

async function clearAllWishlist() {
  if (!products.value.length || busy.value) return

  busy.value = true

  try {
    const currentProducts = [...products.value]

    for (const product of currentProducts) {
      await toggleWishlist({
        user_id: currentUserId(),
        user_email: currentUserEmail(),
        product_id: Number(product.id),
      })
    }

    products.value = []

    showAlert({
      type: 'success',
      title: 'Lista actualizada',
      message: 'Se eliminaron todos los productos de tus favoritos.',
      autoCloseSeconds: 3,
    })
  } catch {
    showAlert({
      type: 'error',
      title: 'No se pudo completar',
      message: 'No fue posible limpiar toda la lista. Intenta de nuevo.',
    })

    await loadWishlist()
  } finally {
    busy.value = false
  }
}

function currentUserId() {
  return String(user.value?.id || '').trim() || undefined
}

function currentUserEmail() {
  return String(user.value?.email || '').trim() || undefined
}
</script>
