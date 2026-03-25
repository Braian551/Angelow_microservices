<template>
  <SiteHeader :settings="settings" :cart-count="cartCount" :initial-search="String(route.query.search || '')" />

  <main class="section-container">
    <section class="products-header">
      <h1 class="section-title">Tienda</h1>
      <p class="products-count">{{ total }} productos encontrados</p>
    </section>

    <section class="products-grid">
      <p v-if="loading" class="loading-box">Cargando catalogo...</p>
      <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>
      <ProductCard
        v-for="product in products"
        v-else
        :key="product.id"
        :product="product"
        @add-cart="openProduct"
      />
    </section>

    <section v-if="totalPages > 1" class="pagination">
      <button type="button" :disabled="page <= 1" @click="changePage(page - 1)">Anterior</button>
      <span>Pagina {{ page }} de {{ totalPages }}</span>
      <button type="button" :disabled="page >= totalPages" @click="changePage(page + 1)">Siguiente</button>
    </section>
  </main>

  <SiteFooter :settings="settings" />
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SiteHeader from '../../../components/layout/SiteHeader.vue'
import SiteFooter from '../../../components/layout/SiteFooter.vue'
import ProductCard from '../components/ProductCard.vue'
import { getHomeData, getProducts } from '../../../services/catalogApi'
import { getCart } from '../../../services/cartApi'
import { useSession } from '../../../composables/useSession'

const route = useRoute()
const router = useRouter()
const { sessionId, user } = useSession()

const settings = ref({})
const loading = ref(false)
const errorMessage = ref('')
const products = ref([])
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)
const cartCount = ref(0)

async function loadSettingsAndCart() {
  try {
    const [homeRes, cartRes] = await Promise.all([
      getHomeData(),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
    ])
    settings.value = homeRes?.data?.settings || {}
    cartCount.value = Number(cartRes?.data?.item_count || 0)
  } catch {
    settings.value = {}
  }
}

async function loadProducts() {
  loading.value = true
  errorMessage.value = ''
  page.value = Number(route.query.page || 1)

  try {
    const filters = {
      page: page.value,
      per_page: 12,
      search: route.query.search || undefined,
      category: route.query.category || undefined,
      collection: route.query.collection || undefined,
      gender: route.query.gender || undefined,
      offers: route.query.offers || undefined,
    }
    const response = await getProducts(filters)
    products.value = response?.data?.products || []
    total.value = Number(response?.data?.total || 0)
    totalPages.value = Number(response?.data?.total_pages || 1)
  } catch {
    products.value = []
    total.value = 0
    totalPages.value = 1
    errorMessage.value = 'No se pudo cargar el catalogo.'
  } finally {
    loading.value = false
  }
}

function changePage(nextPage) {
  router.push({
    name: 'store',
    query: {
      ...route.query,
      page: String(nextPage),
    },
  })
}

function openProduct(product) {
  router.push({ name: 'product', params: { slug: product.slug } })
}

onMounted(async () => {
  await loadSettingsAndCart()
  await loadProducts()
})

watch(
  () => route.query,
  async () => {
    await loadProducts()
  },
)
</script>
