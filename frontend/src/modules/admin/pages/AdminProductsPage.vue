<template>
  <div class="admin-products-page">
    <AdminPageHeader
      icon="fas fa-box-open"
      title="Gestión de Productos"
      :breadcrumbs="[{ label: 'Productos' }]"
    />

    <AdminCard class="filters-card" :flush="true">
      <template #header>
        <div class="filters-header">
          <div class="filters-title">
            <i class="fas fa-sliders-h"></i>
            <h3>Filtros de búsqueda</h3>
          </div>
          <button type="button" class="filters-toggle" :class="{ collapsed: !showAdvanced }" @click="showAdvanced = !showAdvanced">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
      </template>

      <div class="search-bar">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model="search" type="text" class="search-input" placeholder="Buscar por nombre, SKU, etc..." @input="debouncedLoad">
          <button v-if="search" type="button" class="search-clear" @click="clearSearch">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <button type="button" class="search-submit-btn" @click="applyFilters">
          <i class="fas fa-search"></i>
          <span>Buscar</span>
        </button>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row">
          <div class="filter-group">
            <label for="category-filter"><i class="fas fa-tag"></i> Categoría</label>
            <select id="category-filter" v-model="categoryFilter" @change="applyFilters">
              <option value="">Todas las categorías</option>
              <option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name || category.nombre }}</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="status-filter"><i class="fas fa-toggle-on"></i> Estado</label>
            <select id="status-filter" v-model="statusFilter" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="gender-filter"><i class="fas fa-venus-mars"></i> Género</label>
            <select id="gender-filter" v-model="genderFilter" @change="applyFilters">
              <option value="">Todos los géneros</option>
              <option value="nino">Niño</option>
              <option value="nina">Niña</option>
              <option value="bebe">Bebé</option>
              <option value="unisex">Unisex</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="order-filter"><i class="fas fa-sort-amount-down"></i> Ordenar por</label>
            <select id="order-filter" v-model="sortOrder" @change="applyFilters">
              <option value="newest">Más recientes</option>
              <option value="name_asc">Nombre (A-Z)</option>
              <option value="name_desc">Nombre (Z-A)</option>
              <option value="price_asc">Precio (menor a mayor)</option>
              <option value="price_desc">Precio (mayor a menor)</option>
              <option value="stock_asc">Stock (menor a mayor)</option>
              <option value="stock_desc">Stock (mayor a menor)</option>
            </select>
          </div>
        </div>

        <div class="filters-actions-bar">
          <div class="active-filters">
            <i class="fas fa-filter"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>

          <div class="filters-buttons">
            <button type="button" class="btn-clear-filters" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar todo
            </button>
            <button type="button" class="btn-apply-filters" @click="applyFilters">
              <i class="fas fa-check-circle"></i>
              Aplicar filtros
            </button>
          </div>
        </div>
      </div>
    </AdminCard>

    <div class="results-summary">
      <div class="results-summary__info">
        <p>Mostrando {{ paginatedProducts.length }} de {{ filteredProducts.length }} productos</p>
        <span v-if="selectedProducts.length > 0" class="results-summary__selected">{{ selectedProducts.length }} seleccionado(s)</span>
      </div>
      <div class="quick-actions">
        <RouterLink :to="{ name: 'admin-product-create' }" class="btn btn-primary">
          <i class="fas fa-plus"></i> Nuevo Producto
        </RouterLink>
        <button class="btn btn-icon" type="button" @click="exportProducts">
          <i class="fas fa-file-export"></i> Exportar
        </button>
      </div>
    </div>

    <div class="products-container">
      <div v-if="loading" class="products-skeleton" aria-hidden="true">
        <div v-for="i in 6" :key="i" class="product-skeleton-card">
          <div class="skeleton skeleton-thumb"></div>
          <div class="skeleton-body">
            <div class="skeleton skeleton-line w-80"></div>
            <div class="skeleton skeleton-line w-60"></div>
            <div class="skeleton-tags">
              <span class="skeleton skeleton-pill"></span>
              <span class="skeleton skeleton-pill"></span>
            </div>
            <div class="skeleton skeleton-line w-70"></div>
            <div class="skeleton skeleton-line w-40"></div>
          </div>
          <div class="skeleton-actions">
            <span class="skeleton skeleton-btn"></span>
            <span class="skeleton skeleton-btn"></span>
          </div>
        </div>
      </div>

      <AdminEmptyState
        v-else-if="filteredProducts.length === 0"
        icon="fas fa-box-open"
        title="No se encontraron productos"
        description="Intenta ajustar tus filtros o agrega un nuevo producto."
      >
        <RouterLink :to="{ name: 'admin-product-create' }" class="btn btn-primary">
          <i class="fas fa-plus"></i> Agregar producto
        </RouterLink>
      </AdminEmptyState>

      <TransitionGroup v-else name="card-fade" tag="div" class="products-admin-grid">
        <AdminProductCard
          v-for="product in paginatedProducts"
          :key="product.id"
          :product="product"
          :selected="selectedProducts.includes(product.id)"
          @toggle-select="toggleSelection"
          @quick-view="openQuickView"
          @toggle-status="confirmToggleStatus"
          @image-error="onProductCardImageError"
        />
      </TransitionGroup>
    </div>

    <div v-if="totalPages > 1" class="pagination-container">
      <button class="pagination-item" :disabled="currentPage === 1" @click="goToPage(1)">
        <i class="fas fa-angle-double-left"></i>
      </button>
      <button class="pagination-item" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
        <i class="fas fa-angle-left"></i>
      </button>

      <template v-for="item in paginationItems" :key="String(item)">
        <span v-if="item === '...'" class="pagination-item dots">...</span>
        <button v-else class="pagination-item" :class="{ active: Number(item) === currentPage }" @click="goToPage(Number(item))">
          {{ item }}
        </button>
      </template>

      <button class="pagination-item" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">
        <i class="fas fa-angle-right"></i>
      </button>
      <button class="pagination-item" :disabled="currentPage === totalPages" @click="goToPage(totalPages)">
        <i class="fas fa-angle-double-right"></i>
      </button>
    </div>

    <AdminModal :show="showQuickView" title="Detalles del Producto" max-width="1110px" @close="closeQuickView">
      <div v-if="quickViewLoading" class="quick-view-loading">
        <p>Cargando detalles del producto...</p>
      </div>
      <div v-else-if="quickProduct" class="quick-view-content">
        <div class="quick-view-gallery">
          <div class="gallery-filters">
            <button
              v-for="filter in colorFilters"
              :key="filter.color"
              type="button"
              class="color-filter-btn"
              :class="{ active: activeColorFilter === filter.color }"
              :title="filter.color"
              @click="setColorFilter(filter.color)"
            >
              <span v-if="filter.hex" class="color-circle" :style="{ backgroundColor: filter.hex }"></span>
              <span class="color-text">{{ filter.label }}</span>
            </button>
          </div>

          <div class="main-image">
            <img :src="mainQuickImage" :alt="quickProduct.name" @error="onZoomImageError($event, mainQuickImage)">
            <button type="button" class="image-zoom-btn" @click="openZoom(mainQuickImage, quickProduct.name)">
              <i class="fas fa-expand"></i>
            </button>
          </div>

          <div v-if="visibleThumbs.length > 0" class="thumbnail-gallery-container">
            <button v-if="showThumbArrows" type="button" class="gallery-arrow left" @click="scrollThumbs(-1)"><i class="fas fa-chevron-left"></i></button>
            <div ref="thumbGalleryRef" class="thumbnail-gallery">
              <img
                v-for="image in visibleThumbs"
                :key="image.id || image.resolvedUrl"
                :src="image.resolvedUrl"
                :alt="image.alt_text || 'Miniatura'"
                class="thumbnail"
                :class="{ active: image.resolvedUrl === mainQuickImage }"
                @click="mainQuickImage = image.resolvedUrl"
              >
            </div>
            <button v-if="showThumbArrows" type="button" class="gallery-arrow right" @click="scrollThumbs(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
        </div>

        <div class="quick-view-info">
          <div class="product-header">
            <h2>{{ quickProduct.name }}</h2>
            <span class="product-id">ID: {{ quickProduct.id }}</span>
          </div>

          <div class="product-meta">
            <div class="meta-item">
              <i class="fas fa-tag"></i>
              <span>Categoría: {{ quickProduct.category_name || 'Sin categoría' }}</span>
            </div>
            <div class="meta-item">
              <i class="fas fa-palette"></i>
              <span>{{ quickVariantCount }} variante{{ quickVariantCount !== 1 ? 's' : '' }}</span>
            </div>
            <div class="meta-item">
              <i class="fas fa-boxes"></i>
              <span>Stock total: {{ quickTotalStock }} unidades</span>
            </div>
          </div>

          <div class="product-description">
            <h4>Descripción</h4>
            <p>{{ quickProduct.description || 'Sin descripción' }}</p>
          </div>

          <div class="product-pricing">
            <h4>Precios</h4>
            <p>Rango: {{ formatCurrency(quickMinPrice) }} - {{ formatCurrency(quickMaxPrice) }}</p>
          </div>

          <div v-if="quickSizeVariants.length > 0" class="variants-section">
            <h4>Variantes</h4>

            <div v-if="quickColors.length > 0" class="variant-group">
              <label>Colores:</label>
              <div class="color-options">
                <span v-for="color in quickColors" :key="color" class="color-tag">{{ color }}</span>
              </div>
            </div>

            <div v-if="quickSizes.length > 0" class="variant-group">
              <label>Tallas:</label>
              <div class="size-options">
                <span v-for="size in quickSizes" :key="size" class="size-tag">{{ size }}</span>
              </div>
            </div>

            <div v-if="showVariantTable" class="variant-table">
              <table>
                <thead>
                  <tr>
                    <th>Color</th>
                    <th>Talla</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="variant in quickSizeVariants" :key="variant.id">
                    <td>{{ variant.color_name || 'General' }}</td>
                    <td>{{ variant.size_name || 'Única' }}</td>
                    <td>{{ formatCurrency(variant.price) }}</td>
                    <td>{{ variant.quantity }}</td>
                    <td>
                      <span class="variant-status" :class="variant.is_active ? 'active' : 'inactive'">
                        {{ variant.is_active ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <RouterLink
          v-if="quickProduct"
          :to="{ name: 'admin-product-edit', params: { id: quickProduct.id } }"
          class="btn btn-primary"
          @click="closeQuickView"
        >
          <i class="fas fa-edit"></i> Editar producto
        </RouterLink>
        <button class="btn btn-secondary" type="button" @click="closeQuickView">Cerrar</button>
      </template>
    </AdminModal>

    <AdminModal :show="showZoom" :title="zoomTitle || 'Imagen del producto'" max-width="980px" @close="closeZoom">
      <div class="zoom-body">
        <img :src="zoomImage" :alt="zoomTitle" @error="onZoomImageError($event, zoomImage)">
      </div>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminProductCard from '../components/AdminProductCard.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const products = ref([])
const categories = ref([])
const loading = ref(true)
const search = ref('')
const categoryFilter = ref('')
const statusFilter = ref('')
const genderFilter = ref('')
const sortOrder = ref('newest')
const showAdvanced = ref(false)
const selectedProducts = ref([])
const currentPage = ref(1)
const perPage = ref(12)

const showQuickView = ref(false)
const quickViewLoading = ref(false)
const quickProduct = ref(null)
const quickImages = ref([])
const mainQuickImage = ref('')
const quickSizeVariants = ref([])
const quickTotalStock = ref(0)
const quickMinPrice = ref(0)
const quickMaxPrice = ref(0)
const activeColorFilter = ref('General')
const thumbGalleryRef = ref(null)

const showZoom = ref(false)
const zoomImage = ref('')
const zoomTitle = ref('')

const quickColors = computed(() => [...new Set(quickSizeVariants.value.map((variant) => variant.color_name).filter(Boolean))])
const quickSizes = computed(() => [...new Set(quickSizeVariants.value.map((variant) => variant.size_name).filter(Boolean))])
const quickVariantCount = computed(() => quickSizeVariants.value.length || Number(quickProduct.value?.variant_count || 0))
const showVariantTable = computed(() => quickSizeVariants.value.length > 1 || quickColors.value.length > 1 || quickSizes.value.length > 1)

const colorFilters = computed(() => {
  const filters = [{ color: 'General', label: 'Principal', hex: null }]
  const seen = new Set(['General'])

  quickImages.value.forEach((image) => {
    if (image.color_name && image.color_name !== 'General' && !seen.has(image.color_name)) {
      seen.add(image.color_name)
      filters.push({ color: image.color_name, label: image.color_name, hex: image.hex_code || '#CCC' })
    }
  })

  quickSizeVariants.value.forEach((variant) => {
    const color = variant.color_name || 'General'
    if (color !== 'General' && !seen.has(color)) {
      seen.add(color)
      filters.push({ color, label: color, hex: '#D0D7E2' })
    }
  })

  return filters
})

const visibleThumbs = computed(() => {
  if (activeColorFilter.value === 'General') {
    const generalImages = quickImages.value.filter((image) => !image.color_name || image.color_name === 'General' || image.is_primary)
    return generalImages.length > 0 ? generalImages : quickImages.value
  }

  const imagesByColor = quickImages.value.filter((image) => image.color_name === activeColorFilter.value)
  return imagesByColor.length > 0 ? imagesByColor : quickImages.value
})

const showThumbArrows = computed(() => visibleThumbs.value.length > 4)

const activeFilterCount = computed(() => {
  let count = 0
  if (categoryFilter.value) count++
  if (statusFilter.value) count++
  if (genderFilter.value) count++
  if (sortOrder.value !== 'newest') count++
  return count
})

const filteredProducts = computed(() => {
  let rows = products.value

  if (statusFilter.value) {
    rows = rows.filter((product) => (statusFilter.value === 'active' ? product.is_active : !product.is_active))
  }

  if (genderFilter.value) {
    rows = rows.filter((product) => normalizeGender(product.gender) === genderFilter.value)
  }

  const sortedRows = [...rows]
  switch (sortOrder.value) {
    case 'name_asc':
      sortedRows.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
      break
    case 'name_desc':
      sortedRows.sort((a, b) => (b.name || '').localeCompare(a.name || ''))
      break
    case 'price_asc':
      sortedRows.sort((a, b) => (a.min_price || a.price || 0) - (b.min_price || b.price || 0))
      break
    case 'price_desc':
      sortedRows.sort((a, b) => (b.max_price || b.price || 0) - (a.max_price || a.price || 0))
      break
    case 'stock_asc':
      sortedRows.sort((a, b) => a.stock - b.stock)
      break
    case 'stock_desc':
      sortedRows.sort((a, b) => b.stock - a.stock)
      break
    default:
      break
  }

  return sortedRows
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / perPage.value)))
const paginatedProducts = computed(() => filteredProducts.value.slice((currentPage.value - 1) * perPage.value, currentPage.value * perPage.value))

const paginationItems = computed(() => {
  const pages = totalPages.value
  const current = currentPage.value

  if (pages <= 7) {
    return Array.from({ length: pages }, (_, index) => index + 1)
  }

  const items = [1]
  const start = Math.max(2, current - 1)
  const end = Math.min(pages - 1, current + 1)

  if (start > 2) items.push('...')
  for (let page = start; page <= end; page += 1) items.push(page)
  if (end < pages - 1) items.push('...')
  items.push(pages)

  return items
})

function normalizeGender(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

function formatCurrency(value) {
  return `$${Number(value || 0).toLocaleString('es-CO')}`
}

function productImage(product) {
  return resolveMediaUrl(product.primary_image || product.image || product.product_image || product.imagen || product.image_url, 'product')
}

function normalizeVariant(rawVariant) {
  return {
    ...rawVariant,
    id: rawVariant.id || `${rawVariant.color_name || rawVariant.color || 'general'}-${rawVariant.size_name || rawVariant.size || 'unica'}-${rawVariant.price || rawVariant.precio || 0}`,
    color_name: rawVariant.color_name || rawVariant.color || '',
    size_name: rawVariant.size_name || rawVariant.size || '',
    price: Number(rawVariant.price ?? rawVariant.precio ?? 0),
    quantity: Number(rawVariant.quantity ?? rawVariant.stock ?? rawVariant.cantidad ?? 0),
    is_active: typeof rawVariant.is_active === 'boolean' ? rawVariant.is_active : Boolean(Number(rawVariant.is_active ?? rawVariant.activo ?? 1)),
  }
}

function buildQuickImages(product, imageRows) {
  const normalizedImages = imageRows
    .map((image) => ({
      ...image,
      color_name: image.color_name || 'General',
      resolvedUrl: resolveMediaUrl(image.url || image.image_path || image.image, 'product'),
      is_primary: Boolean(Number(image.is_primary ?? 0)),
    }))
    .filter((image) => image.resolvedUrl)

  const productImageUrl = product.image || resolveMediaUrl(product.rawImage, 'product')
  if (productImageUrl && !normalizedImages.some((image) => image.resolvedUrl === productImageUrl)) {
    normalizedImages.unshift({
      id: 'primary-image',
      color_name: 'General',
      resolvedUrl: productImageUrl,
      is_primary: true,
      alt_text: product.name,
    })
  }

  return normalizedImages
}

function normalizeProduct(rawProduct) {
  return {
    ...rawProduct,
    id: Number(rawProduct.id),
    name: rawProduct.name || rawProduct.nombre || 'Sin nombre',
    price: Number(rawProduct.price ?? rawProduct.precio ?? 0),
    min_price: Number(rawProduct.min_price ?? rawProduct.price ?? rawProduct.precio ?? 0),
    max_price: Number(rawProduct.max_price ?? rawProduct.price ?? rawProduct.precio ?? 0),
    variant_count: Number(rawProduct.variant_count ?? rawProduct.variants_count ?? 0),
    category_name: rawProduct.category_name || rawProduct.categoria_nombre || 'Sin categoría',
    stock: Number(rawProduct.stock ?? rawProduct.total_stock ?? 0),
    description: rawProduct.description || rawProduct.descripcion || '',
    gender: rawProduct.gender || rawProduct.genero || '',
    rawImage: rawProduct.primary_image || rawProduct.image || rawProduct.product_image || rawProduct.imagen || rawProduct.image_url || null,
    image: productImage(rawProduct),
    is_active: typeof rawProduct.is_active === 'boolean' ? rawProduct.is_active : Boolean(Number(rawProduct.activo ?? 1)),
  }
}

function clearSearch() {
  search.value = ''
  currentPage.value = 1
  loadProducts()
}

function toggleSelection({ id, checked }) {
  if (checked) {
    selectedProducts.value = [...new Set([...selectedProducts.value, id])]
    return
  }

  selectedProducts.value = selectedProducts.value.filter((selectedId) => selectedId !== id)
}

function setColorFilter(color) {
  activeColorFilter.value = color
  const filtered = visibleThumbs.value
  if (filtered.length > 0) {
    mainQuickImage.value = filtered[0].resolvedUrl
  }
}

function scrollThumbs(direction) {
  if (thumbGalleryRef.value) {
    thumbGalleryRef.value.scrollBy({ left: direction * 150, behavior: 'smooth' })
  }
}

function goToPage(page) {
  currentPage.value = Math.min(Math.max(1, page), totalPages.value)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function applyFilters() {
  currentPage.value = 1
  loadProducts()
}

function clearAllFilters() {
  search.value = ''
  categoryFilter.value = ''
  statusFilter.value = ''
  genderFilter.value = ''
  sortOrder.value = 'newest'
  selectedProducts.value = []
  currentPage.value = 1
  loadProducts()
}

function onProductCardImageError({ event, imagePath }) {
  handleMediaError(event, imagePath, 'product')
}

function onZoomImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'product')
}

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    loadProducts()
  }, 400)
}

async function loadProducts() {
  loading.value = true
  try {
    const adminParams = { limit: 200 }
    if (search.value) adminParams.search = search.value
    if (categoryFilter.value) adminParams.category = categoryFilter.value
    if (statusFilter.value) adminParams.status = statusFilter.value

    const response = await catalogHttp.get('/admin/products', { params: adminParams })
    const payload = response.data?.data || response.data || []
    const rows = Array.isArray(payload) ? payload : (payload.data || [])
    products.value = rows.map(normalizeProduct)
    selectedProducts.value = selectedProducts.value.filter((id) => products.value.some((product) => product.id === id))
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando productos' })
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const response = await catalogHttp.get('/admin/categories')
    const payload = response.data?.data || response.data || []
    categories.value = Array.isArray(payload) ? payload : (payload.data || [])
  } catch {
    try {
      const fallbackResponse = await catalogHttp.get('/categories')
      categories.value = fallbackResponse.data?.data || fallbackResponse.data || []
    } catch {
      categories.value = []
    }
  }
}

async function openQuickView(product) {
  showQuickView.value = true
  quickViewLoading.value = true
  quickProduct.value = product
  activeColorFilter.value = 'General'

  try {
    const response = await catalogHttp.get(`/admin/products/${product.id}`)
    const payload = response.data?.data || {}
    const productData = payload.product || product
    const imageRows = Array.isArray(payload.images) ? payload.images : []
    const sizeVariants = (Array.isArray(payload.size_variants) ? payload.size_variants : (Array.isArray(payload.variants) ? payload.variants : []))
      .map(normalizeVariant)

    quickProduct.value = normalizeProduct(productData)
    quickSizeVariants.value = sizeVariants
    quickTotalStock.value = Number(payload.total_stock ?? quickProduct.value.stock ?? sizeVariants.reduce((total, variant) => total + variant.quantity, 0))
    quickMinPrice.value = Number(payload.min_price ?? quickProduct.value.min_price ?? quickProduct.value.price ?? 0)
    quickMaxPrice.value = Number(payload.max_price ?? quickProduct.value.max_price ?? quickProduct.value.price ?? 0)

    quickImages.value = buildQuickImages(quickProduct.value, imageRows)
    if (quickImages.value.length === 0) {
      quickImages.value = [{ id: 0, resolvedUrl: quickProduct.value.image, color_name: 'General', is_primary: true }]
    }
    mainQuickImage.value = quickImages.value[0]?.resolvedUrl || quickProduct.value.image
  } catch {
    quickProduct.value = normalizeProduct(product)
    quickImages.value = [{ id: 0, resolvedUrl: quickProduct.value.image, color_name: 'General', is_primary: true }]
    mainQuickImage.value = quickProduct.value.image
    quickSizeVariants.value = []
    quickTotalStock.value = Number(quickProduct.value.stock || 0)
    quickMinPrice.value = Number(quickProduct.value.min_price || quickProduct.value.price || 0)
    quickMaxPrice.value = Number(quickProduct.value.max_price || quickProduct.value.price || 0)
    showSnackbar({ type: 'warning', message: 'No fue posible cargar todos los detalles del producto.' })
  } finally {
    quickViewLoading.value = false
  }
}

function closeQuickView() {
  showQuickView.value = false
  quickViewLoading.value = false
  quickProduct.value = null
  quickImages.value = []
  quickSizeVariants.value = []
  quickTotalStock.value = 0
  quickMinPrice.value = 0
  quickMaxPrice.value = 0
  mainQuickImage.value = ''
  activeColorFilter.value = 'General'
}

function openZoom(image, title) {
  zoomImage.value = image
  zoomTitle.value = title
  showZoom.value = true
}

function closeZoom() {
  showZoom.value = false
  zoomImage.value = ''
  zoomTitle.value = ''
}

function confirmToggleStatus(product) {
  const action = product.is_active ? 'desactivar' : 'activar'
  const actionLabel = product.is_active ? 'Desactivar' : 'Activar'

  showAlert({
    type: product.is_active ? 'warning' : 'success',
    title: `${actionLabel} producto`,
    message: `¿Estás seguro de ${action} "${product.name}"?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: actionLabel,
        style: product.is_active ? 'warning' : 'success',
        callback: () => toggleProductStatus(product),
      },
    ],
  })
}

async function toggleProductStatus(product) {
  try {
    const newStatus = !product.is_active
    await catalogHttp.patch(`/admin/products/${product.id}/status`, { is_active: newStatus })
    showSnackbar({ type: 'success', message: `Producto ${newStatus ? 'activado' : 'desactivado'}` })
    await loadProducts()
  } catch {
    showSnackbar({ type: 'error', message: 'Error al cambiar el estado del producto' })
  }
}

function exportProducts() {
  const rows = filteredProducts.value
  if (rows.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay productos para exportar' })
    return
  }

  const header = ['ID', 'Nombre', 'Categoría', 'Stock', 'Precio Min', 'Precio Max', 'Estado']
  const csvRows = [header.join(',')]

  rows.forEach((product) => {
    csvRows.push([
      product.id,
      `"${(product.name || '').replace(/"/g, '""')}"`,
      `"${(product.category_name || '').replace(/"/g, '""')}"`,
      product.stock,
      product.min_price || product.price || 0,
      product.max_price || product.price || 0,
      product.is_active ? 'Activo' : 'Inactivo',
    ].join(','))
  })

  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'productos.csv'
  link.click()
  URL.revokeObjectURL(url)
  showSnackbar({ type: 'success', message: 'Exportación generada correctamente' })
}

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>

<style scoped>
.filters-card {
  margin-bottom: 2rem;
  border: 1px solid #d9e8f4;
  border-radius: 26px;
  box-shadow: 0 14px 32px rgba(15, 55, 96, 0.08);
  overflow: hidden;
}

.filters-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 1.75rem 2rem;
  border-bottom: 1px solid #edf3f8;
}

.filters-title {
  display: flex;
  align-items: center;
  gap: 0.95rem;
}

.filters-title i {
  width: 3rem;
  height: 3rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  background: #eef7ff;
  color: #0077b6;
}

.filters-title h3 {
  margin: 0;
  font-size: 1.45rem;
  font-weight: 700;
  color: #24364b;
}

.filters-toggle {
  width: 3.25rem;
  height: 3.25rem;
  border: 1px solid #cfe2f2;
  background: #fff;
  color: #45617d;
  border-radius: 1.1rem;
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
  cursor: pointer;
}

.filters-toggle:hover {
  border-color: #0077b6;
  box-shadow: 0 10px 20px rgba(0, 119, 182, 0.12);
}

.filters-toggle.collapsed {
  transform: rotate(180deg);
}

.search-bar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 1rem;
  padding: 1.75rem 2rem;
}

.search-input-wrapper {
  position: relative;
}

.search-input {
  width: 100%;
  height: 4.25rem;
  padding: 0 3.25rem;
  border: 1px solid #cfe2f2;
  border-radius: 1.4rem;
  font-size: 1.08rem;
  color: #24364b;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.search-input:focus,
.filter-group select:focus {
  outline: none;
  border-color: #0077b6;
  box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.08);
}

.search-icon {
  position: absolute;
  left: 1.15rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6a96cf;
}

.search-clear {
  position: absolute;
  right: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #90a4b7;
  cursor: pointer;
}

.search-submit-btn {
  min-width: 9.5rem;
  height: 4.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  border: 1px solid #8bc7f0;
  background: #f3fbff;
  color: #0077b6;
  border-radius: 1.4rem;
  padding: 0 1.65rem;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.search-submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 28px rgba(0, 119, 182, 0.14);
  border-color: #0077b6;
}

.filters-advanced {
  padding: 0 2rem 2rem;
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(4, minmax(180px, 1fr));
  gap: 1rem;
}

.filter-group label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.55rem;
  font-size: 0.95rem;
  color: #4f657b;
  font-weight: 600;
}

.filter-group select {
  width: 100%;
  height: 3.3rem;
  border-radius: 1rem;
  border: 1px solid #cde0ef;
  padding: 0 0.95rem;
  color: #24364b;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.filters-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.3rem;
  border-top: 1px solid #edf2f7;
}

.active-filters,
.quick-actions,
.filters-buttons,
.results-summary__info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.active-filters {
  font-size: 0.95rem;
  color: #4f657b;
  font-weight: 600;
}

.btn-clear-filters {
  border: none;
  background: transparent;
  color: #0077b6;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.95rem;
  font-weight: 600;
}

.btn-apply-filters,
.quick-actions .btn,
.quick-actions button {
  min-height: 3.15rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  padding: 0 1.2rem;
  border-radius: 1rem;
  text-decoration: none;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease, background-color 0.25s ease, border-color 0.25s ease;
}

.btn-apply-filters,
.quick-actions .btn-primary {
  border: 1px solid #0077b6;
  background: #0077b6;
  color: #fff;
  box-shadow: 0 14px 24px rgba(0, 119, 182, 0.16);
}

.btn-apply-filters:hover,
.quick-actions .btn-primary:hover {
  background: #0068a1;
  border-color: #0068a1;
  transform: translateY(-2px);
  box-shadow: 0 18px 30px rgba(0, 119, 182, 0.2);
}

.results-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
  padding: 1.25rem 1.6rem;
  background: #fff;
  border: 1px solid #d9e8f4;
  border-radius: 1.65rem;
  box-shadow: 0 14px 28px rgba(15, 55, 96, 0.08);
}

.results-summary p {
  margin: 0;
  color: #0077b6;
  font-size: 1.12rem;
  font-weight: 700;
}

.results-summary__selected {
  color: #4f657b;
  font-size: 0.95rem;
  font-weight: 600;
}

.btn.btn-icon {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid #cde0ef;
  background: #fff;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  cursor: pointer;
  color: #0e5f99;
  font-weight: 600;
}

.btn.btn-icon:hover {
  border-color: #0077b6;
  color: #0077b6;
  transform: translateY(-2px);
  box-shadow: 0 16px 28px rgba(0, 119, 182, 0.12);
}

.products-admin-grid,
.products-skeleton {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
  padding: 20px 0;
}

.product-skeleton-card {
  border: 1px solid #b3d9ff;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 119, 182, 0.08);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.skeleton {
  background: linear-gradient(90deg, #edf2f7 25%, #f7fafc 50%, #edf2f7 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}

.skeleton-thumb {
  height: 160px;
}

.skeleton-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-line {
  height: 12px;
}

.skeleton-tags {
  display: flex;
  gap: 8px;
}

.skeleton-pill {
  height: 20px;
  width: 90px;
  border-radius: 999px;
}

.skeleton-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.skeleton-btn {
  height: 36px;
  border-radius: 10px;
}

.w-80 { width: 80%; }
.w-70 { width: 70%; }
.w-60 { width: 60%; }
.w-40 { width: 40%; }

.pagination-container {
  margin-top: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  flex-wrap: wrap;
}

.pagination-item {
  min-width: 40px;
  height: 40px;
  border: 1px solid #d5e3f0;
  background: #fff;
  color: #325372;
  border-radius: 0.8rem;
  cursor: pointer;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.7rem;
  transition: all 0.2s ease;
}

.pagination-item:hover:not(:disabled):not(.active):not(.dots) {
  border-color: #0077b6;
  color: #0077b6;
}

.pagination-item.active {
  background: #0077b6;
  color: #fff;
  border-color: #0077b6;
  box-shadow: 0 12px 22px rgba(0, 119, 182, 0.18);
}

.pagination-item:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.pagination-item.dots {
  border-style: dashed;
  cursor: default;
}

.quick-view-loading {
  min-height: 18rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #5a6f85;
  font-size: 1.05rem;
}

.quick-view-content {
  display: grid;
  grid-template-columns: 350px minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.quick-view-gallery {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.gallery-filters,
.color-options,
.size-options,
.product-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.color-filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #cde0ef;
  background: #fff;
  padding: 8px 12px;
  border-radius: 999px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: #0077b6;
  transition: all 0.25s ease;
}

.color-filter-btn.active,
.color-filter-btn:hover {
  border-color: #0077b6;
  background: #f0f8ff;
}

.color-circle {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  display: inline-block;
  border: 1px solid #c3c9d2;
}

.main-image {
  position: relative;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #d8e4f0;
  background: #f8fafc;
  box-shadow: 0 4px 12px rgba(0, 119, 182, 0.15);
}

.main-image img {
  width: 100%;
  height: 350px;
  object-fit: cover;
  display: block;
}

.image-zoom-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  border: none;
  background: rgba(0, 0, 0, 0.7);
  color: #fff;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
}

.thumbnail-gallery-container {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 4px;
}

.gallery-arrow {
  border: 1px solid #c9d8e8;
  background: rgba(255, 255, 255, 0.95);
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
}

.thumbnail-gallery {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  flex: 1;
  padding: 4px;
}

.thumbnail {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border: 2px solid transparent;
  border-radius: 6px;
  cursor: pointer;
  flex-shrink: 0;
}

.thumbnail.active,
.thumbnail:hover {
  border-color: #0a74b8;
}

.quick-view-info {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
}

.product-header h2 {
  margin: 0;
  font-size: 2rem;
  color: #2d3f54;
  font-weight: 700;
}

.product-id {
  font-size: 1rem;
  color: #718096;
  font-weight: 500;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f5f8fc;
  border: 1px solid #dde7f2;
  padding: 8px 12px;
  border-radius: 10px;
  font-size: 14px;
  color: #506a84;
}

.product-description h4,
.product-pricing h4,
.variants-section h4 {
  margin: 0 0 12px;
  color: #33475b;
  font-size: 1.1rem;
  font-weight: 700;
}

.product-description p {
  color: #5a6f85;
  margin: 0;
  line-height: 1.6;
}

.product-pricing p {
  font-weight: 700;
  color: #0876b6;
  font-size: 1.95rem;
  margin: 0;
}

.variants-section {
  margin-top: 0.5rem;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.variant-group {
  margin-bottom: 1rem;
}

.variant-group label {
  display: block;
  font-weight: 700;
  color: #4f657b;
  margin-bottom: 8px;
  font-size: 14px;
}

.color-tag,
.size-tag {
  display: inline-block;
  padding: 6px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  font-size: 13px;
  color: #4f657b;
  background: #f7fafc;
}

.variant-table {
  margin-top: 0.75rem;
  overflow-x: auto;
}

.variant-table table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 119, 182, 0.08);
}

.variant-table th {
  background: #f0f8ff;
  color: #1e40af;
  font-weight: 700;
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.variant-table td {
  padding: 12px;
  border-bottom: 1px solid #edf2f7;
  color: #2d3748;
}

.variant-status {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.variant-status.active {
  background: #e6f7eb;
  color: #1d7a3f;
}

.variant-status.inactive {
  background: #ffe9e9;
  color: #b52e2e;
}

.zoom-body {
  text-align: center;
  min-height: 25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.zoom-body img {
  width: 100%;
  max-height: 76vh;
  object-fit: contain;
  border-radius: 0.9rem;
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.2);
}

.card-fade-enter-active,
.card-fade-leave-active {
  transition: all 0.24s ease;
}

.card-fade-enter-from,
.card-fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 900px) {
  .quick-view-content {
    grid-template-columns: 1fr;
  }

  .search-bar {
    grid-template-columns: 1fr;
  }

  .filters-row {
    grid-template-columns: repeat(2, 1fr);
  }

  .results-summary,
  .filters-actions-bar,
  .product-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .main-image img {
    height: 420px;
  }
}

@media (max-width: 600px) {
  .filters-row {
    grid-template-columns: 1fr;
  }

  .products-admin-grid,
  .products-skeleton {
    grid-template-columns: 1fr;
  }

  .main-image img {
    height: 280px;
  }
}
</style>
