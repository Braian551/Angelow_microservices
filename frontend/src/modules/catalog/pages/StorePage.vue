<template>
  <main class="store-page-container container">
    <div class="store-layout">
      <!-- Sidebar Filters -->
      <aside class="products-filters-sidebar">
        <div class="filter-header">
          <h2>Filtros</h2>
          <button type="button" class="btn-clear" @click="clearFilters">Limpiar</button>
        </div>

        <div class="filter-group">
          <h3 class="filter-title" @click="toggleFilter('category')">
            Categorías
            <i class="fas fa-chevron-down" :class="{ 'fa-rotate-180': !openFilters.category }"></i>
          </h3>
          <div v-show="openFilters.category" class="filter-options">
            <label class="filter-option">
              <input type="radio" v-model="filters.category" value="" @change="applyFilters" />
              <span>Todas</span>
            </label>
            <label v-for="cat in categories" :key="cat.id" class="filter-option">
              <input type="radio" v-model="filters.category" :value="cat.id" @change="applyFilters" />
              <span>{{ cat.name }}</span>
            </label>
          </div>
        </div>

        <div class="filter-group">
          <h3 class="filter-title" @click="toggleFilter('gender')">
            Género
            <i class="fas fa-chevron-down" :class="{ 'fa-rotate-180': !openFilters.gender }"></i>
          </h3>
          <div v-show="openFilters.gender" class="filter-options">
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="" @change="applyFilters" />
              <span>Todos</span>
            </label>
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="nina" @change="applyFilters" />
              <span>Niñas</span>
            </label>
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="nino" @change="applyFilters" />
              <span>Niños</span>
            </label>
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="bebe" @change="applyFilters" />
              <span>Bebés</span>
            </label>
          </div>
        </div>

        <div class="filter-group">
          <h3 class="filter-title" @click="toggleFilter('price')">
            Precio
            <i class="fas fa-chevron-down" :class="{ 'fa-rotate-180': !openFilters.price }"></i>
          </h3>
          <div v-show="openFilters.price" class="filter-options price-filter">
            <div class="price-inputs">
              <input type="number" v-model.lazy="filters.min_price" placeholder="Mínimo" @change="applyFilters" />
              <span>-</span>
              <input type="number" v-model.lazy="filters.max_price" placeholder="Máximo" @change="applyFilters" />
            </div>
          </div>
        </div>

        <div class="filter-group">
          <h3 class="filter-title" @click="toggleFilter('offers')">
            Ofertas especiales
            <i class="fas fa-chevron-down" :class="{ 'fa-rotate-180': !openFilters.offers }"></i>
          </h3>
          <div v-show="openFilters.offers" class="filter-options">
            <label class="filter-option">
              <input type="checkbox" v-model="filters.offers" true-value="1" false-value="" @change="applyFilters" />
              <span>Solo en oferta</span>
            </label>
          </div>
        </div>

        <div class="filter-group" v-if="collections.length > 0">
          <h3 class="filter-title" @click="toggleFilter('collection')">
            Colecciones
            <i class="fas fa-chevron-down" :class="{ 'fa-rotate-180': !openFilters.collection }"></i>
          </h3>
          <div v-show="openFilters.collection" class="filter-options">
            <label class="filter-option">
              <input type="radio" v-model="filters.collection" value="" @change="applyFilters" />
              <span>Todas</span>
            </label>
            <label v-for="col in collections" :key="col.id" class="filter-option">
              <input type="radio" v-model="filters.collection" :value="col.id" @change="applyFilters" />
              <span>{{ col.name }}</span>
            </label>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <section class="products-main">
        <div class="products-toolbar">
          <p class="products-count">{{ total }} productos encontrados</p>
          <div class="products-sort">
            <label for="sort-select">Ordenar por:</label>
            <select id="sort-select" v-model="filters.sort" @change="applyFilters">
              <option value="newest">Más recientes</option>
              <option value="popular">Más populares</option>
              <option value="price_asc">Precio: de menor a mayor</option>
              <option value="price_desc">Precio: de mayor a menor</option>
              <option value="name_asc">Nombre: A-Z</option>
              <option value="name_desc">Nombre: Z-A</option>
            </select>
          </div>
        </div>

        <div class="products-grid">
          <template v-if="loading">
            <!-- Shimmer Loading -->
            <article class="product-card shimmer" v-for="i in 12" :key="'shimmer'+i">
              <div class="shimmer-img"></div>
              <div class="product-info">
                <div class="shimmer-line short"></div>
                <div class="shimmer-line long"></div>
                <div class="shimmer-line medium"></div>
              </div>
            </article>
          </template>

          <template v-else-if="errorMessage">
            <p class="error-box">{{ errorMessage }}</p>
          </template>

          <template v-else-if="products.length > 0">
            <ProductCard
              v-for="product in products"
              :key="product.id"
              :product="product"
              @add-cart="openProduct"
              @wishlist-change="onWishlistChange"
            />
          </template>

          <template v-else>
            <p class="no-products">No se encontraron productos con los filtros seleccionados.</p>
          </template>
        </div>

        <!-- Pagination -->
        <section v-if="totalPages > 1" class="pagination-wrapper">
          <button type="button" class="btn-page" :disabled="page <= 1" @click="changePage(page - 1)">
            <i class="fas fa-chevron-left"></i>
          </button>
          
          <template v-for="p in visiblePages" :key="p">
            <span v-if="p === '...'" class="page-dots">...</span>
            <button v-else type="button" class="btn-page" :class="{ active: p === page }" @click="changePage(p)">
              {{ p }}
            </button>
          </template>

          <button type="button" class="btn-page" :disabled="page >= totalPages" @click="changePage(page + 1)">
            <i class="fas fa-chevron-right"></i>
          </button>
        </section>
      </section>
    </div>
  </main>
</template>

<script setup>
import { onMounted, ref, reactive, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard from '../components/ProductCard.vue'
import { getProducts, getCategories, getCollections } from '../../../services/catalogApi'
import { useSession } from '../../../composables/useSession'
import './StorePage.css'

const route = useRoute()
const router = useRouter()
const { user } = useSession()

const loading = ref(false)
const errorMessage = ref('')
const products = ref([])
const categories = ref([])
const collections = ref([])

const page = ref(1)
const totalPages = ref(1)
const total = ref(0)

const filters = reactive({
  category: route.query.category || '',
  gender: route.query.gender || '',
  min_price: route.query.min_price || '',
  max_price: route.query.max_price || '',
  offers: route.query.offers || '',
  collection: route.query.collection || '',
  sort: route.query.sort || 'newest',
})

const openFilters = reactive({
  category: true,
  gender: true,
  price: true,
  offers: true,
  collection: true,
})

function toggleFilter(key) {
  openFilters[key] = !openFilters[key]
}

function clearFilters() {
  filters.category = ''
  filters.gender = ''
  filters.min_price = ''
  filters.max_price = ''
  filters.offers = ''
  filters.collection = ''
  
  // Also clear search query
  const query = { ...route.query }
  delete query.search
  router.push({ name: 'store', query })
}

function applyFilters() {
  const query = { ...route.query }
  
  for (const [key, value] of Object.entries(filters)) {
    if (value) {
      query[key] = value
    } else {
      delete query[key]
    }
  }
  
  query.page = '1'

  router.push({ name: 'store', query })
}

function syncFiltersFromRoute() {
  filters.category = route.query.category || ''
  filters.gender = route.query.gender || ''
  filters.min_price = route.query.min_price || ''
  filters.max_price = route.query.max_price || ''
  filters.offers = route.query.offers || ''
  filters.collection = route.query.collection || ''
  filters.sort = route.query.sort || 'newest'
}

async function loadInitialData() {
  try {
    const [catsRes, colsRes] = await Promise.all([
      getCategories(),
      getCollections()
    ])
    categories.value = catsRes?.data || []
    collections.value = colsRes?.data || []
  } catch (error) {
    console.error('Error loading filters data', error)
  }
}

async function loadProducts() {
  loading.value = true
  errorMessage.value = ''
  page.value = Number(route.query.page || 1)

  try {
    const apiFilters = {
      page: page.value,
      per_page: 12,
      search: route.query.search || undefined,
      category: filters.category || undefined,
      collection: filters.collection || undefined,
      gender: filters.gender || undefined,
      min_price: filters.min_price || undefined,
      max_price: filters.max_price || undefined,
      offers: filters.offers || undefined,
      sort: filters.sort || undefined,
      user_id: user.value?.id || undefined,
      user_email: user.value?.email || undefined,
    }
    const response = await getProducts(apiFilters)
    products.value = response?.data?.products || []
    total.value = Number(response?.data?.total || 0)
    totalPages.value = Number(response?.data?.total_pages || 1)
  } catch {
    products.value = []
    total.value = 0
    totalPages.value = 1
    errorMessage.value = 'No se pudo cargar el catálogo.'
  } finally {
    loading.value = false
  }
}

const visiblePages = computed(() => {
  const current = page.value
  const last = totalPages.value
  const delta = 2
  const left = current - delta
  const right = current + delta + 1
  const range = []
  const rangeWithDots = []
  let l

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || i >= left && i < right) {
      range.push(i)
    }
  }

  for (let i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  }

  return rangeWithDots
})

function changePage(nextPage) {
  if (nextPage < 1 || nextPage > totalPages.value) return
  
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

onMounted(async () => {
  syncFiltersFromRoute()
  await loadInitialData()
  await loadProducts()
})

watch(
  () => route.query,
  async () => {
    syncFiltersFromRoute()
    await loadProducts()
  },
)
</script>
