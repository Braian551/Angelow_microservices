<template>
  <TopAnnouncementBar :announcement="homeData.top_bar" />
  <SiteHeader :settings="homeData.settings" :cart-count="cartCount" />

  <main>
    <HomeHeroSlider :slides="homeData.sliders || []" />

    <div class="section-container">
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
          />
        </div>
      </section>

      <PromoBanner :banner="homeData.promo_banner" />
      <CollectionGrid :collections="collections.slice(0, 3)" />
    </div>
  </main>

  <SiteFooter :settings="homeData.settings" />
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import TopAnnouncementBar from '../../../components/home/TopAnnouncementBar.vue'
import SiteHeader from '../../../components/layout/SiteHeader.vue'
import SiteFooter from '../../../components/layout/SiteFooter.vue'
import CategoryGrid from '../components/CategoryGrid.vue'
import CollectionGrid from '../components/CollectionGrid.vue'
import HomeHeroSlider from '../components/HomeHeroSlider.vue'
import PromoBanner from '../components/PromoBanner.vue'
import ProductCard from '../../catalog/components/ProductCard.vue'
import { getCategories, getCollections, getHomeData, getProducts } from '../../../services/catalogApi'
import { getCart } from '../../../services/cartApi'
import { useSession } from '../../../composables/useSession'

const router = useRouter()
const { sessionId, user } = useSession()

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
const cartCount = ref(0)

onMounted(async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const [homeRes, productsRes, categoriesRes, collectionsRes] = await Promise.all([
      getHomeData(),
      getProducts({ per_page: 8 }),
      getCategories(),
      getCollections(),
    ])

    homeData.value = homeRes?.data || homeData.value
    products.value = productsRes?.data?.products || []
    categories.value = categoriesRes?.data || []
    collections.value = collectionsRes?.data || []

    const cartRes = await getCart({
      user_id: user.value?.id || undefined,
      session_id: user.value?.id ? undefined : sessionId.value,
    })
    cartCount.value = Number(cartRes?.data?.item_count || 0)
  } catch {
    errorMessage.value = 'No se pudo cargar la pagina inicial.'
  } finally {
    loading.value = false
  }
})

function openProduct(product) {
  router.push({ name: 'product', params: { slug: product.slug } })
}
</script>
