<template>
  <main class="store-page-container container">
    <div class="store-layout">
      <aside class="products-filters-sidebar">
        <div class="filter-header">
          <h2>Filtros</h2>
          <button type="button" class="btn-clear" @click="clearFilters">Limpiar</button>
        </div>

        <div class="filter-group">
          <button type="button" class="filter-title" @click="toggleFilter('category')">
            <span>Categorías</span>
            <i class="fas fa-chevron-right" :class="{ 'is-open': openFilters.category }"></i>
          </button>
          <div class="filter-options store-filter-options" :class="{ 'is-open': openFilters.category }">
            <label class="filter-option">
              <input type="radio" v-model="filters.category" value="" @change="applyFilters" />
              <span>Todas</span>
            </label>
            <label v-for="cat in categories" :key="cat.id" class="filter-option">
              <input type="radio" v-model="filters.category" :value="String(cat.id)" @change="applyFilters" />
              <span>{{ cat.name }}</span>
            </label>
          </div>
        </div>

        <div class="filter-group">
          <button type="button" class="filter-title" @click="toggleFilter('gender')">
            <span>Género</span>
            <i class="fas fa-chevron-right" :class="{ 'is-open': openFilters.gender }"></i>
          </button>
          <div class="filter-options store-filter-options" :class="{ 'is-open': openFilters.gender }">
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="" @change="applyFilters" />
              <span>Todos</span>
            </label>
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="nina" @change="applyFilters" />
              <span>Niña</span>
            </label>
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="nino" @change="applyFilters" />
              <span>Niño</span>
            </label>
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="bebe" @change="applyFilters" />
              <span>Bebés</span>
            </label>
            <label class="filter-option">
              <input type="radio" v-model="filters.gender" value="unisex" @change="applyFilters" />
              <span>Unisex</span>
            </label>
          </div>
        </div>

        <div class="filter-group">
          <button type="button" class="filter-title" @click="toggleFilter('price')">
            <span>Rango de precios</span>
            <i class="fas fa-chevron-right" :class="{ 'is-open': openFilters.price }"></i>
          </button>
          <div class="filter-options store-filter-options price-filter" :class="{ 'is-open': openFilters.price }">
            <div class="price-range-slider">
              <input
                v-model.number="priceRange.min"
                type="range"
                class="price-slider min-price"
                :min="PRICE_RANGE_MIN"
                :max="PRICE_RANGE_MAX"
                step="1000"
                @input="onPriceRangeInput('min')"
                @change="applyPriceFilter"
              />
              <input
                v-model.number="priceRange.max"
                type="range"
                class="price-slider max-price"
                :min="PRICE_RANGE_MIN"
                :max="PRICE_RANGE_MAX"
                step="1000"
                @input="onPriceRangeInput('max')"
                @change="applyPriceFilter"
              />
              <div class="price-values">
                <span>{{ formatPriceValue(priceRange.min) }}</span>
                <span>{{ formatPriceValue(priceRange.max) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="filter-group">
          <button type="button" class="filter-title" @click="toggleFilter('offers')">
            <span>Ofertas</span>
            <i class="fas fa-chevron-right" :class="{ 'is-open': openFilters.offers }"></i>
          </button>
          <div class="filter-options store-filter-options" :class="{ 'is-open': openFilters.offers }">
            <label class="filter-option">
              <input type="checkbox" v-model="filters.offers" true-value="1" false-value="" @change="applyFilters" />
              <span>Solo en oferta</span>
            </label>
          </div>
        </div>

        <div class="filter-group" v-if="collections.length > 0">
          <button type="button" class="filter-title" @click="toggleFilter('collection')">
            <span>Colecciones</span>
            <i class="fas fa-chevron-right" :class="{ 'is-open': openFilters.collection }"></i>
          </button>
          <div class="filter-options store-filter-options" :class="{ 'is-open': openFilters.collection }">
            <label class="filter-option">
              <input type="radio" v-model="filters.collection" value="" @change="applyFilters" />
              <span>Todas</span>
            </label>
            <label v-for="col in collections" :key="col.id" class="filter-option">
              <input type="radio" v-model="filters.collection" :value="String(col.id)" @change="applyFilters" />
              <span>{{ col.name }}</span>
            </label>
          </div>
        </div>
      </aside>

      <section class="store-products-main">
        <div class="store-products-toolbar">
          <p class="store-products-count">
            {{ total }} producto{{ total === 1 ? '' : 's' }} encontrado{{ total === 1 ? '' : 's' }}
          </p>
          <div class="store-products-sort">
            <label for="sort-select">Ordenar por:</label>
            <select id="sort-select" v-model="filters.sort" @change="applyFilters">
              <option value="newest">Más recientes</option>
              <option value="popular">Más populares</option>
              <option value="price_asc">Precio: menor a mayor</option>
              <option value="price_desc">Precio: mayor a menor</option>
              <option value="name_asc">Nombre: A-Z</option>
              <option value="name_desc">Nombre: Z-A</option>
            </select>
          </div>
        </div>

        <div class="store-products-grid">
          <template v-if="loading">
            <article class="product-card shimmer" v-for="i in 12" :key="`shimmer-${i}`">
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

        <StorePagination :page="page" :total-pages="totalPages" @change="changePage" />
      </section>
    </div>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard from '../components/ProductCard.vue'
import StorePagination from '../components/StorePagination.vue'
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
const PRICE_RANGE_MIN = 0
const PRICE_RANGE_MAX = 200000

const page = ref(1)
const totalPages = ref(1)
const total = ref(0)
const STORE_FILTER_KEYS = ['category', 'gender', 'min_price', 'max_price', 'offers', 'collection', 'sort', 'page']

const filters = reactive({
  category: route.query.category || '',
  gender: route.query.gender || '',
  min_price: '',
  max_price: '',
  offers: route.query.offers || '',
  collection: route.query.collection || '',
  sort: route.query.sort || 'newest',
})

const priceRange = reactive({
  min: PRICE_RANGE_MIN,
  max: PRICE_RANGE_MAX,
})

const openFilters = reactive({
  category: false,
  gender: false,
  price: false,
  offers: false,
  collection: false,
})

function toggleFilter(key) {
  openFilters[key] = !openFilters[key]
}

function clampPrice(value, fallback) {
  const parsed = Number(value)
  if (!Number.isFinite(parsed)) {
    return fallback
  }

  return Math.max(PRICE_RANGE_MIN, Math.min(PRICE_RANGE_MAX, parsed))
}

function syncPriceFiltersFromRange() {
  filters.min_price = priceRange.min > PRICE_RANGE_MIN ? String(priceRange.min) : ''
  filters.max_price = priceRange.max < PRICE_RANGE_MAX ? String(priceRange.max) : ''
}

function syncPriceRangeFromRoute() {
  const minFromRoute = parseNumericFilter(route.query.min_price)
  const maxFromRoute = parseNumericFilter(route.query.max_price)

  const normalizedMin = clampPrice(minFromRoute ?? PRICE_RANGE_MIN, PRICE_RANGE_MIN)
  const normalizedMax = clampPrice(maxFromRoute ?? PRICE_RANGE_MAX, PRICE_RANGE_MAX)

  priceRange.min = Math.min(normalizedMin, normalizedMax)
  priceRange.max = Math.max(normalizedMin, normalizedMax)

  syncPriceFiltersFromRange()
}

function onPriceRangeInput(boundary) {
  const nextMin = clampPrice(priceRange.min, PRICE_RANGE_MIN)
  const nextMax = clampPrice(priceRange.max, PRICE_RANGE_MAX)

  if (boundary === 'min') {
    priceRange.min = Math.min(nextMin, nextMax)
    priceRange.max = Math.max(nextMax, priceRange.min)
  } else {
    priceRange.max = Math.max(nextMax, nextMin)
    priceRange.min = Math.min(nextMin, priceRange.max)
  }

  syncPriceFiltersFromRange()
}

function applyPriceFilter() {
  onPriceRangeInput('max')
  applyFilters()
}

function clearFilters() {
  filters.category = ''
  filters.gender = ''
  priceRange.min = PRICE_RANGE_MIN
  priceRange.max = PRICE_RANGE_MAX
  syncPriceFiltersFromRange()
  filters.offers = ''
  filters.collection = ''
  filters.sort = 'newest'

  const query = { ...route.query }
  STORE_FILTER_KEYS.forEach((key) => {
    delete query[key]
  })
  delete query.search

  router.push({ name: 'store', query })
}

function applyFilters() {
  const query = { ...route.query }

  STORE_FILTER_KEYS.forEach((key) => {
    delete query[key]
  })

  for (const [key, value] of Object.entries(filters)) {
    if (value === '' || value === null || value === undefined) {
      continue
    }

    if (key === 'sort' && String(value) === 'newest') {
      continue
    }

    query[key] = String(value)
  }

  router.push({ name: 'store', query })
}

function syncFiltersFromRoute() {
  filters.category = String(route.query.category || '')
  filters.gender = String(route.query.gender || '')
  syncPriceRangeFromRoute()
  filters.offers = String(route.query.offers || '') === '1' ? '1' : ''
  filters.collection = String(route.query.collection || '')
  filters.sort = String(route.query.sort || 'newest')
}

function parseNumericFilter(value) {
  const raw = String(value || '').trim()
  if (!raw) return undefined

  const parsed = Number(raw)
  if (!Number.isFinite(parsed)) return undefined

  return parsed
}

async function loadInitialData() {
  try {
    const [catsRes, colsRes] = await Promise.all([
      getCategories(),
      getCollections(),
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
  page.value = Math.max(1, Number(route.query.page || 1))

  try {
    const apiFilters = {
      page: page.value,
      per_page: 12,
      search: route.query.search || undefined,
      category: parseNumericFilter(filters.category),
      collection: parseNumericFilter(filters.collection),
      gender: filters.gender || undefined,
      min_price: parseNumericFilter(filters.min_price),
      max_price: parseNumericFilter(filters.max_price),
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

function formatPriceValue(value) {
  const normalizedValue = Number(value || 0)
  return `$${new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 0,
  }).format(normalizedValue)}`
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
