<template>
  <main>
    <HomeHeroSlider :slides="homeData.sliders || []" :loading="loading" />

    <CategoryGrid :categories="categories.slice(0, 4)" />

    <section class="featured-products">
      <div class="section-header">
        <h2 class="section-title">Productos destacados</h2>
        <RouterLink :to="{ name: 'store' }" class="view-all">Ver todos</RouterLink>
      </div>

      <p v-if="loading" class="loading-box">Cargando productos...</p>
      <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>
      <div v-else class="products-grid">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
          @add-cart="openProduct"
          @wishlist-change="onWishlistChange"
        />
      </div>
    </section>

    <PromoBanner :banner="homeData.promo_banner" />
    <CollectionGrid :collections="collections.slice(0, 3)" />
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import CategoryGrid from '../components/CategoryGrid.vue'
import CollectionGrid from '../components/CollectionGrid.vue'
import HomeHeroSlider from '../components/HomeHeroSlider.vue'
import PromoBanner from '../components/PromoBanner.vue'
import ProductCard from '../../catalog/components/ProductCard.vue'
import { useSession } from '../../../composables/useSession'
import { getCategories, getCollections, getHomeData, getProducts } from '../../../services/catalogApi'
import '../views/HomeView.css'

const router = useRouter()
const { user } = useSession()
const HOME_FEATURED_PRODUCTS_LIMIT = 4

const loading = ref(true)
const errorMessage = ref('')
const homeData = ref({
  settings: {},
  sliders: [],
  top_bar: null,
  promo_banner: null,
})
const products = ref([])
const categories = ref([])
const collections = ref([])

onMounted(async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const [homeRes, productsRes, categoriesRes, collectionsRes] = await Promise.all([
      getHomeData(),
      getProducts({
        page: 1,
        per_page: HOME_FEATURED_PRODUCTS_LIMIT,
        user_id: user.value?.id || undefined,
        user_email: user.value?.email || undefined,
      }),
      getCategories(),
      getCollections(),
    ])

    homeData.value = homeRes?.data || homeData.value
    products.value = (productsRes?.data?.products || []).slice(0, HOME_FEATURED_PRODUCTS_LIMIT)
    categories.value = categoriesRes?.data || []
    collections.value = collectionsRes?.data || []
  } catch {
    errorMessage.value = 'No se pudo cargar la página inicial.'
  } finally {
    loading.value = false
  }
})

function openProduct(product) {
  router.push({ name: 'product', params: { slug: product.slug } })
}

function onWishlistChange(payload) {
  const productId = Number(payload?.productId)
  const favorite = Boolean(payload?.isFavorite)

  products.value = products.value.map((item) => {
    if (Number(item.id) !== productId) {
      return item
    }

    return {
      ...item,
      is_favorite: favorite ? 1 : 0,
    }
  })
}
</script>
