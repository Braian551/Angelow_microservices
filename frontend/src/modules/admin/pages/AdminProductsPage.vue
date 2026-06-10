<template>
  <div class="admin-products-page">
    <AdminPageHeader
      icon="fas fa-box-open"
      title="Gestión de Productos"
      :breadcrumbs="[{ label: 'Productos' }]"
    />

    <!-- Filtros de búsqueda -->
    <AdminFilterCard
      v-model="search"
      icon="fas fa-sliders-h"
      title="Filtros de búsqueda"
      placeholder="Buscar por nombre, SKU, etc..."
      @update:model-value="debouncedLoad"
      @search="applyFilters"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="category-filter"><i class="fas fa-tag"></i> Categoría</label>
            <select id="category-filter" v-model="categoryFilter" @change="applyFilters">
              <option value="">Todas las categorías</option>
              <option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name || category.nombre }}</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="status-filter"><i class="fas fa-toggle-on"></i> Estado</label>
            <select id="status-filter" v-model="statusFilter" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="gender-filter"><i class="fas fa-venus-mars"></i> Género</label>
            <select id="gender-filter" v-model="genderFilter" @change="applyFilters">
              <option value="">Todos los géneros</option>
              <option value="nino">Niño</option>
              <option value="nina">Niña</option>
              <option value="bebe">Bebé</option>
              <option value="unisex">Unisex</option>
            </select>
          </div>

          <div class="admin-filters__group">
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

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-filter"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>

          <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <button type="button" class="admin-filters__clear" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar todo
            </button>
            <button type="button" class="admin-filters__apply" @click="applyFilters">
              <i class="fas fa-check-circle"></i>
              Aplicar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} productos`">
      <template #actions>
        <div v-if="selectedProducts.length > 0" class="results-action-btn results-action-btn--neutral" style="cursor:default;">
          <span class="results-action-btn__icon"><i class="fas fa-check-double"></i></span>
          <span>{{ selectedProducts.length }} seleccionado<span v-if="selectedProducts.length !== 1">s</span></span>
        </div>
        <RouterLink :to="{ name: 'admin-product-create' }" class="results-action-btn results-action-btn--primary">
          <span class="results-action-btn__icon"><i class="fas fa-plus"></i></span>
          <span>Nuevo Producto</span>
        </RouterLink>
        <AdminExportActions
          tone="results"
          :disabled="filteredProducts.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportProducts('excel')"
          @pdf="exportProducts('pdf')"
        />
      </template>
    </AdminResultsBar>

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
          v-for="product in pagination.paginatedItems"
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

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

    <AdminModal :show="showQuickView" title="Detalles del Producto" max-width="1110px" @close="closeQuickView">
      <div class="admin-products-page admin-products-page--modal">
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
      <div class="admin-products-page admin-products-page--modal">
        <div class="zoom-body">
          <img :src="zoomImage" :alt="zoomTitle" @error="onZoomImageError($event, zoomImage)">
        </div>
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
import { getFallbackMediaUrl, handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAdminDataExport } from '../composables/useAdminDataExport'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminProductCard from '../components/AdminProductCard.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import '../views/AdminProductsPage.css'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()
const { exportData, exportingFormat } = useAdminDataExport()

const products = ref([])
const categories = ref([])
const loading = ref(true)
const search = ref('')
const categoryFilter = ref('')
const statusFilter = ref('')
const genderFilter = ref('')
const sortOrder = ref('newest')
const selectedProducts = ref([])

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

const productFallbackImage = getFallbackMediaUrl('product')

const quickColors = computed(() => {
  const fromVariants = quickSizeVariants.value.map((variant) => variant.color_name).filter(Boolean)
  const fromFilters = colorFilters.value
    .map((filter) => filter.color)
    .filter((color) => color && color !== 'General')

  return [...new Set([...fromVariants, ...fromFilters])]
})
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

const pagination = useAdminPagination(filteredProducts, {
  initialPageSize: 12,
  pageSizeOptions: [12, 24, 48],
})

function normalizeGender(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

// Traduce el género a la misma etiqueta visible del panel para reutilizarla en Excel y PDF.
function genderLabel(value) {
  return {
    nino: 'Niño',
    nina: 'Niña',
    bebe: 'Bebé',
    unisex: 'Unisex',
  }[normalizeGender(value)] || 'Sin género'
}

function formatCurrency(value) {
  return `$${Number(value || 0).toLocaleString('es-CO')}`
}

function productImage(product) {
  return resolveMediaUrl(product.primary_image || product.image || product.product_image || product.imagen || product.image_url, 'product')
}

function extractColorName(raw) {
  if (!raw) return ''
  if (typeof raw === 'string') return raw.trim()
  if (typeof raw === 'object') {
    return String(raw.name || raw.nombre || raw.color_name || raw.label || '').trim()
  }
  return ''
}

function normalizeVariant(rawVariant) {
  const colorName = extractColorName(rawVariant.color_name || rawVariant.color || rawVariant.color_label || rawVariant.variant_color)

  return {
    ...rawVariant,
    id: rawVariant.id || `${rawVariant.color_name || rawVariant.color || 'general'}-${rawVariant.size_name || rawVariant.size || 'unica'}-${rawVariant.price || rawVariant.precio || 0}`,
    color_name: colorName,
    color_variant_id: Number(rawVariant.color_variant_id || rawVariant.variant_id || rawVariant.product_color_variant_id || 0) || null,
    hex_code: rawVariant.hex_code || rawVariant.color_hex || rawVariant.hex || null,
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
      color_name: extractColorName(image.color_name || image.color || image.color_label || image.variant_color) || 'General',
      hex_code: image.hex_code || image.color_hex || image.hex || null,
      resolvedUrl: resolveMediaUrl(image.url || image.image_path || image.image, 'product'),
      is_primary: Boolean(Number(image.is_primary ?? 0)),
    }))
    .filter((image) => image.resolvedUrl && image.resolvedUrl !== productFallbackImage)

  // Evita miniaturas duplicadas cuando backend devuelve la misma imagen desde varias fuentes.
  const dedupedByUrl = []
  const byUrl = new Map()
  normalizedImages.forEach((image) => {
    const key = image.resolvedUrl
    const existing = byUrl.get(key)

    if (!existing) {
      byUrl.set(key, image)
      dedupedByUrl.push(image)
      return
    }

    // Priorizar primaria, color especifico y hex real sobre copia general.
    if (!existing.is_primary && image.is_primary) existing.is_primary = true
    if ((existing.color_name === 'General' || !existing.color_name) && image.color_name && image.color_name !== 'General') {
      existing.color_name = image.color_name
    }
    if (!existing.hex_code && image.hex_code) existing.hex_code = image.hex_code
    if (!existing.alt_text && image.alt_text) existing.alt_text = image.alt_text
  })

  const productImageUrl = product.rawImage ? resolveMediaUrl(product.rawImage, 'product') : ''
  if (productImageUrl && !dedupedByUrl.some((image) => image.resolvedUrl === productImageUrl)) {
    dedupedByUrl.unshift({
      id: 'primary-image',
      color_name: 'General',
      resolvedUrl: productImageUrl,
      is_primary: true,
      alt_text: product.name,
    })
  }

  return dedupedByUrl
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

function applyFilters() {
  pagination.currentPage = 1
  loadProducts()
}

function clearAllFilters() {
  search.value = ''
  categoryFilter.value = ''
  statusFilter.value = ''
  genderFilter.value = ''
  sortOrder.value = 'newest'
  selectedProducts.value = []
  pagination.currentPage = 1
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
    pagination.currentPage = 1
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
    const variantsPayload = Array.isArray(payload.variants) ? payload.variants : []
    const variantColorMap = new Map()
    const variantHexMap = new Map()

    variantsPayload.forEach((variant) => {
      const variantId = Number(variant?.id || variant?.color_variant_id || 0)
      const colorName = extractColorName(variant?.color_name || variant?.color || variant?.name)
      const hexCode = variant?.hex_code || variant?.color_hex || null

      if (variantId > 0) {
        if (colorName) variantColorMap.set(variantId, colorName)
        if (hexCode) variantHexMap.set(variantId, hexCode)
      }
    })

    const sizeVariantsRaw = []
    if (Array.isArray(payload.size_variants) && payload.size_variants.length > 0) {
      sizeVariantsRaw.push(...payload.size_variants)
    } else {
      variantsPayload.forEach((variant) => {
        if (Array.isArray(variant?.size_variants)) {
          variant.size_variants.forEach((sizeVariant) => {
            sizeVariantsRaw.push({
              ...sizeVariant,
              color_variant_id: sizeVariant.color_variant_id || variant.id || variant.color_variant_id,
              color_name: sizeVariant.color_name || variant.color_name,
              hex_code: sizeVariant.hex_code || variant.hex_code || variant.color_hex,
            })
          })
        }
      })
    }

    const sizeVariants = sizeVariantsRaw
      .map((variant) => {
        const normalized = normalizeVariant(variant)
        const variantId = Number(normalized.color_variant_id || 0)

        if (!normalized.color_name && variantId > 0 && variantColorMap.has(variantId)) {
          normalized.color_name = variantColorMap.get(variantId)
        }
        if (!normalized.hex_code && variantId > 0 && variantHexMap.has(variantId)) {
          normalized.hex_code = variantHexMap.get(variantId)
        }

        return normalized
      })

    const imageRows = []
    const hasVariantImages = Array.isArray(payload.variant_images) && payload.variant_images.length > 0
    const hasProductImages = Array.isArray(payload.images) && payload.images.length > 0

    if (hasVariantImages) {
      imageRows.push(...payload.variant_images)
    } else if (hasProductImages) {
      imageRows.push(...payload.images)
    } else {
      variantsPayload.forEach((variant) => {
        if (Array.isArray(variant?.images)) {
          variant.images.forEach((image) => {
            imageRows.push({
              ...image,
              color_variant_id: image.color_variant_id || variant.id || variant.color_variant_id,
              color_name: image.color_name || variant.color_name,
              hex_code: image.hex_code || variant.hex_code || variant.color_hex,
            })
          })
        }
      })
    }

    const enrichedImageRows = imageRows.map((image) => {
      const variantId = Number(image.color_variant_id || 0)
      const colorName = extractColorName(image.color_name || image.color || image.color_label)

      return {
        ...image,
        color_name: colorName || (variantId > 0 ? (variantColorMap.get(variantId) || '') : ''),
        hex_code: image.hex_code || image.color_hex || (variantId > 0 ? (variantHexMap.get(variantId) || null) : null),
      }
    })

    quickProduct.value = normalizeProduct(productData)
    quickSizeVariants.value = sizeVariants
    quickTotalStock.value = Number(payload.total_stock ?? quickProduct.value.stock ?? sizeVariants.reduce((total, variant) => total + variant.quantity, 0))
    quickMinPrice.value = Number(payload.min_price ?? quickProduct.value.min_price ?? quickProduct.value.price ?? 0)
    quickMaxPrice.value = Number(payload.max_price ?? quickProduct.value.max_price ?? quickProduct.value.price ?? 0)

    quickImages.value = buildQuickImages(quickProduct.value, enrichedImageRows)
    if (quickImages.value.length === 0) {
      const fallbackSafeImage = quickProduct.value.rawImage
        ? resolveMediaUrl(quickProduct.value.rawImage, 'product')
        : quickProduct.value.image

      quickImages.value = [{ id: 0, resolvedUrl: fallbackSafeImage, color_name: 'General', is_primary: true }]
    }
    const preferredImage = quickImages.value.find((image) => image.is_primary && image.resolvedUrl !== productFallbackImage)
      || quickImages.value.find((image) => image.resolvedUrl !== productFallbackImage)
      || quickImages.value[0]

    mainQuickImage.value = preferredImage?.resolvedUrl || quickProduct.value.image
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

// Define un único contrato exportable para no mantener plantillas separadas por formato.
function buildProductsExportColumns() {
  return [
    {
      header: 'Vista',
      includeInExcel: false,
      pdfImage: (product) => product.rawImage || product.image,
      fallbackType: 'product',
      pdfWidth: 18,
      pdfImageSize: 12,
    },
    { header: 'Producto', value: (product) => product.name },
    { header: 'Categoría', value: (product) => product.category_name || 'Sin categoría' },
    { header: 'Estado', value: (product) => (product.is_active ? 'Activo' : 'Inactivo') },
    { header: 'Variantes', value: (product) => Number(product.variant_count || 0), excelType: 'number', align: 'center' },
    { header: 'Stock total', value: (product) => Number(product.stock || 0), excelType: 'number', align: 'center' },
    {
      header: 'Precio desde',
      value: (product) => formatCurrency(product.min_price || product.price || 0),
      excelValue: (product) => Number(product.min_price || product.price || 0),
      excelType: 'currency',
      align: 'right',
      width: 16,
    },
    {
      header: 'Precio hasta',
      value: (product) => formatCurrency(product.max_price || product.price || 0),
      excelValue: (product) => Number(product.max_price || product.price || 0),
      excelType: 'currency',
      align: 'right',
      width: 16,
    },
    { header: 'Género', value: (product) => genderLabel(product.gender), width: 14 },
  ]
}

// Reutiliza el exportador admin para que Excel y PDF compartan branding, logo y estructura.
function exportProducts(format) {
  return exportData({
    format,
    fileBaseName: 'productos-admin',
    sheetName: 'Productos',
    title: 'Catálogo de productos',
    subtitle: 'Resumen exportado desde la gestión administrativa de productos.',
    columns: buildProductsExportColumns(),
    rows: filteredProducts.value,
    landscape: true,
    emptyMessage: 'No hay productos para exportar.',
  })
}

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>

