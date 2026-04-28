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
        <button class="results-action-btn results-action-btn--neutral" type="button" @click="exportProductsCsv">
          <span class="results-action-btn__icon"><i class="fas fa-file-export"></i></span>
          <span>Exportar CSV</span>
        </button>
        <button class="results-action-btn results-action-btn--neutral" type="button" @click="exportProductsPdf">
          <span class="results-action-btn__icon"><i class="fas fa-file-pdf"></i></span>
          <span>Exportar PDF</span>
        </button>
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
import { getFallbackMediaUrl, handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminProductCard from '../components/AdminProductCard.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'

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

function buildExportParams() {
  const params = {}

  if (search.value) params.search = search.value
  if (categoryFilter.value) params.category = categoryFilter.value
  if (statusFilter.value) params.status = statusFilter.value

  const ids = filteredProducts.value
    .map((product) => Number(product.id))
    .filter((id) => Number.isFinite(id) && id > 0)

  if (ids.length > 0) {
    params.ids = ids.join(',')
  }

  return params
}

function extractFileName(contentDisposition, fallbackName) {
  if (!contentDisposition) return fallbackName

  const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1])
  }

  const simpleMatch = contentDisposition.match(/filename="?([^";]+)"?/i)
  if (simpleMatch?.[1]) {
    return simpleMatch[1]
  }

  return fallbackName
}

function triggerBlobDownload(blob, fileName) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = fileName
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

async function exportProductsByFormat(format) {
  if (filteredProducts.value.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay productos para exportar' })
    return
  }

  const isPdf = format === 'pdf'
  const endpoint = isPdf ? '/admin/products/export/pdf' : '/admin/products/export/csv'
  const fallbackName = isPdf ? 'productos.pdf' : 'productos.csv'

  try {
    const response = await catalogHttp.get(endpoint, {
      params: buildExportParams(),
      responseType: 'blob',
      headers: {
        Accept: isPdf ? 'application/pdf' : 'text/csv',
      },
    })

    const fileName = extractFileName(response.headers?.['content-disposition'], fallbackName)
    triggerBlobDownload(response.data, fileName)
    showSnackbar({ type: 'success', message: `Exportación ${isPdf ? 'PDF' : 'CSV'} generada correctamente` })
  } catch {
    showSnackbar({ type: 'error', message: `No fue posible exportar productos en ${isPdf ? 'PDF' : 'CSV'}` })
  }
}

function exportProductsCsv() {
  return exportProductsByFormat('csv')
}

function exportProductsPdf() {
  return exportProductsByFormat('pdf')
}

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>

<style scoped>
/* Estilos específicos de Productos — los comunes están en admin.css */

/* Grid de productos (tarjetas) */
.products-admin-grid,
.products-skeleton {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
  padding: 20px 0;
}

/* Skeleton de carga para tarjetas de producto */
.product-skeleton-card {
  border: 1px solid var(--admin-border-light);
  border-radius: var(--admin-card-radius);
  overflow: hidden;
  background: var(--admin-bg);
  padding: 16px;
  box-shadow: var(--admin-shadow-card);
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

.skeleton-thumb { height: 160px; }
.skeleton-body { display: flex; flex-direction: column; gap: 10px; }
.skeleton-line { height: 12px; }
.skeleton-tags { display: flex; gap: 8px; }
.skeleton-pill { height: 20px; width: 90px; border-radius: var(--admin-radius-pill); }
.skeleton-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.skeleton-btn { height: 36px; border-radius: var(--admin-radius-md); }
.w-80 { width: 80%; }
.w-70 { width: 70%; }
.w-60 { width: 60%; }
.w-40 { width: 40%; }

/* Paginación local (usa variables globales) */
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
  border: 1px solid var(--admin-border-light);
  background: var(--admin-bg);
  color: var(--admin-text-soft);
  border-radius: var(--admin-radius-lg);
  cursor: pointer;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.7rem;
  transition: var(--admin-transition-fast);
}

.pagination-item:hover:not(:disabled):not(.active):not(.dots) {
  border-color: var(--admin-primary);
  color: var(--admin-primary);
}

.pagination-item.active {
  background: var(--admin-primary);
  color: #fff;
  border-color: var(--admin-primary);
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

/* Vista rápida de producto (modal) */
.quick-view-loading {
  min-height: 18rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--admin-text-soft);
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
  border: 1px solid var(--admin-border-soft);
  background: var(--admin-bg);
  padding: 8px 12px;
  border-radius: var(--admin-radius-pill);
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: var(--admin-primary);
  transition: var(--admin-transition-fast);
}

.color-filter-btn.active,
.color-filter-btn:hover {
  border-color: var(--admin-primary);
  background: var(--admin-bg-highlight);
}

.main-image {
  position: relative;
  border-radius: var(--admin-radius-xl);
  overflow: hidden;
  border: 1px solid var(--admin-border-light);
  background: var(--admin-bg-dark);
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
  border: 1px solid var(--admin-border-soft);
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
  border-radius: var(--admin-radius-sm);
  cursor: pointer;
  flex-shrink: 0;
}

.thumbnail.active,
.thumbnail:hover {
  border-color: var(--admin-primary);
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
  border-bottom: 1px solid var(--admin-border);
}

.product-header h2 {
  margin: 0;
  font-size: 2rem;
  color: var(--admin-text-heading);
  font-weight: 700;
}

.product-id {
  font-size: 1rem;
  color: var(--admin-text-light);
  font-weight: 500;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--admin-bg-dark);
  border: 1px solid var(--admin-border-light);
  padding: 8px 12px;
  border-radius: var(--admin-radius-md);
  font-size: 14px;
  color: var(--admin-text-soft);
}

.product-description h4,
.product-pricing h4,
.variants-section h4 {
  margin: 0 0 12px;
  color: var(--admin-text-heading);
  font-size: 1.1rem;
  font-weight: 700;
}

.product-description p {
  color: var(--admin-text-soft);
  margin: 0;
  line-height: 1.6;
}

.product-pricing p {
  font-weight: 700;
  color: var(--admin-primary);
  font-size: 1.95rem;
  margin: 0;
}

.variants-section {
  margin-top: 0.5rem;
  padding-top: 20px;
  border-top: 1px solid var(--admin-border);
}

.variant-group {
  margin-bottom: 1rem;
}

.variant-group label {
  display: block;
  font-weight: 700;
  color: var(--admin-text-soft);
  margin-bottom: 8px;
  font-size: 14px;
}

.color-tag,
.size-tag {
  display: inline-block;
  padding: 6px 12px;
  border: 1px solid var(--admin-border);
  border-radius: var(--admin-radius-pill);
  font-size: 13px;
  color: var(--admin-text-soft);
  background: var(--admin-bg-dark);
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
  box-shadow: var(--admin-shadow-card);
}

.variant-table th {
  background: var(--admin-bg-highlight);
  color: var(--admin-primary);
  font-weight: 700;
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid var(--admin-border);
}

.variant-table td {
  padding: 12px;
  border-bottom: 1px solid var(--admin-border-muted);
  color: var(--admin-text-heading);
}

.variant-status {
  display: inline-block;
  padding: 4px 8px;
  border-radius: var(--admin-radius-pill);
  font-size: 12px;
  font-weight: 700;
}

.variant-status.active {
  background: rgba(75, 181, 67, 0.12);
  color: var(--admin-success);
}

.variant-status.inactive {
  background: rgba(255, 51, 51, 0.1);
  color: var(--admin-error);
}

/* Modal de zoom de imagen */
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

/* Transición de tarjetas */
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

  .product-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .main-image img {
    height: 420px;
  }
}

@media (max-width: 600px) {
  .products-admin-grid,
  .products-skeleton {
    grid-template-columns: 1fr;
  }

  .main-image img {
    height: 280px;
  }
}
</style>
