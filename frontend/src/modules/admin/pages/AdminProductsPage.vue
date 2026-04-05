<template>
  <div>
    <AdminPageHeader icon="fas fa-box-open" title="Gestion de Productos" :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Productos' }]" />

    <!-- Filtros y búsqueda -->
    <div class="dashboard-card filters-card">
      <div class="filters-header">
        <div class="filters-title">
          <i class="fas fa-sliders-h"></i>
          <h3>Filtros de búsqueda</h3>
        </div>
        <button type="button" class="filters-toggle" :class="{ collapsed: !showAdvanced }" @click="showAdvanced = !showAdvanced">
          <i class="fas fa-chevron-down"></i>
        </button>
      </div>

      <div class="search-bar">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model="search" type="text" class="search-input" placeholder="Buscar por nombre, SKU, etc..." @input="debouncedLoad">
          <button v-if="search" type="button" class="search-clear" @click="search = ''; loadProducts()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <button type="button" class="search-submit-btn" @click="loadProducts">
          <i class="fas fa-search"></i>
          <span>Buscar</span>
        </button>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row">
          <div class="filter-group">
            <label><i class="fas fa-tag"></i> Categoría</label>
            <select v-model="categoryFilter" @change="loadProducts">
              <option value="">Todas las categorías</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name || c.nombre }}</option>
            </select>
          </div>
          <div class="filter-group">
            <label><i class="fas fa-toggle-on"></i> Estado</label>
            <select v-model="statusFilter" @change="loadProducts">
              <option value="">Todos los estados</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </select>
          </div>
          <div class="filter-group">
            <label><i class="fas fa-venus-mars"></i> Género</label>
            <select v-model="genderFilter" @change="loadProducts">
              <option value="">Todos los géneros</option>
              <option value="nino">Niño</option>
              <option value="nina">Niña</option>
              <option value="bebe">Bebé</option>
              <option value="unisex">Unisex</option>
            </select>
          </div>
          <div class="filter-group">
            <label><i class="fas fa-sort-amount-down"></i> Ordenar por</label>
            <select v-model="sortOrder" @change="loadProducts">
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
            <span>{{ activeFilterCount }} filtros activos</span>
          </div>
          <div class="filters-buttons">
            <button type="button" class="btn-clear-filters" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i> Limpiar todo
            </button>
            <button type="button" class="btn-apply-filters" @click="applyFilters">
              <i class="fas fa-check-circle"></i> Aplicar filtros
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Resumen de resultados -->
    <div class="results-summary">
      <p>Mostrando {{ showingCount }} de {{ filteredProducts.length }} productos</p>
      <div class="quick-actions">
        <RouterLink to="/admin/productos/nuevo" class="btn btn-primary">
          <i class="fas fa-plus"></i> Nuevo Producto
        </RouterLink>
        <button class="btn btn-icon" @click="exportProducts">
          <i class="fas fa-file-export"></i> Exportar
        </button>
      </div>
    </div>

    <!-- Listado de productos -->
    <div class="products-container">
      <div v-if="loading" class="products-skeleton">
        <div v-for="i in 6" :key="i" class="product-skeleton-card">
          <div class="admin-shimmer shimmer-thumb"></div>
          <div class="skeleton-body">
            <div class="admin-shimmer shimmer-line" style="width:80%"></div>
            <div class="admin-shimmer shimmer-line" style="width:60%"></div>
            <div class="skeleton-tags">
              <span class="admin-shimmer shimmer-pill"></span>
              <span class="admin-shimmer shimmer-pill"></span>
            </div>
            <div class="admin-shimmer shimmer-line" style="width:70%"></div>
            <div class="admin-shimmer shimmer-line" style="width:40%"></div>
          </div>
          <div class="skeleton-actions">
            <span class="admin-shimmer shimmer-btn"></span>
            <span class="admin-shimmer shimmer-btn"></span>
          </div>
        </div>
      </div>

      <AdminEmptyState v-else-if="filteredProducts.length === 0" icon="fas fa-box-open" title="No se encontraron productos" description="Intenta ajustar tus filtros o agrega un nuevo producto.">
        <RouterLink to="/admin/productos/nuevo" class="btn btn-primary">
          <i class="fas fa-plus"></i> Agregar Producto
        </RouterLink>
      </AdminEmptyState>

      <TransitionGroup v-else name="card-fade" tag="div" class="products-admin-grid">
        <div v-for="product in paginatedProducts" :key="product.id" class="product-admin-card">
          <div class="product-admin-select">
            <input type="checkbox" class="select-row" :value="product.id" v-model="selectedProducts">
          </div>

          <div class="product-admin-status">
            <span class="status-badge" :class="product.is_active ? 'status-active' : 'status-inactive'">
              {{ product.is_active ? 'Activo' : 'Inactivo' }}
            </span>
          </div>

          <div class="product-admin-image">
            <img :src="product.image" :alt="product.name" @error="onProductImageError($event, product.rawImage)">
            <div class="product-admin-overlay">
              <button class="btn-overlay" title="Vista rápida" @click="openQuickView(product)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="product-admin-body">
            <div class="product-admin-header">
              <h3 class="product-admin-title">
                <RouterLink :to="{ name: 'admin-product-edit', params: { id: product.id } }">{{ product.name }}</RouterLink>
              </h3>
              <span class="product-admin-id">ID: {{ product.id }}</span>
            </div>

            <div class="product-admin-meta">
              <div class="meta-item">
                <i class="fas fa-tag"></i>
                <span>{{ product.category_name || 'Sin categoría' }}</span>
              </div>
              <div class="meta-item">
                <i class="fas fa-palette"></i>
                <span>{{ product.variant_count || 0 }} variante{{ (product.variant_count || 0) !== 1 ? 's' : '' }}</span>
              </div>
            </div>

            <div class="product-admin-info">
              <div class="info-item">
                <label>Stock Total:</label>
                <span class="stock-value" :class="{ 'low-stock': product.stock < 10 }">{{ product.stock }} unidades</span>
              </div>
              <div class="info-item">
                <label>Precio:</label>
                <span class="price-value">{{ formatPriceRange(product) }}</span>
              </div>
            </div>
          </div>

          <div class="product-admin-actions">
            <RouterLink :to="{ name: 'admin-product-edit', params: { id: product.id } }" class="btn-action btn-edit" title="Editar">
              <i class="fas fa-edit"></i>
              <span>Editar</span>
            </RouterLink>
            <button
              class="btn-action"
              :class="product.is_active ? 'btn-toggle-off' : 'btn-toggle-on'"
              :title="product.is_active ? 'Desactivar producto' : 'Activar producto'"
              @click="confirmToggleStatus(product)"
            >
              <i :class="product.is_active ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              <span>{{ product.is_active ? 'Desactivar' : 'Activar' }}</span>
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Paginación -->
    <div v-if="totalPages > 1" class="pagination-container">
      <button class="pagination-item" :disabled="currentPage === 1" @click="goToPage(1)">
        <i class="fas fa-angle-double-left"></i>
      </button>
      <button class="pagination-item" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
        <i class="fas fa-angle-left"></i>
      </button>

      <template v-for="item in paginationItems" :key="String(item)">
        <span v-if="item === '...'" class="pagination-item dots">...</span>
        <button
          v-else
          class="pagination-item"
          :class="{ active: Number(item) === currentPage }"
          @click="goToPage(Number(item))"
        >
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

    <!-- Modal vista rápida -->
    <Transition name="fade-scale">
      <div v-if="showQuickView" class="modal-overlay quick-view-modal" @click.self="closeQuickView">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title-with-icon">
              <span class="modal-title-icon"><i class="fas fa-filter"></i></span>
              Detalles del Producto
            </h3>
            <button class="modal-close" @click="closeQuickView">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="quickViewLoading" class="empty-state" style="padding: 2rem;"><p>Cargando...</p></div>
            <div v-else-if="quickProduct" class="quick-view-content">
              <!-- Galería con filtros de color -->
              <div class="quick-view-gallery">
                <div class="gallery-filters">
                  <button
                    v-for="cf in colorFilters"
                    :key="cf.color"
                    class="color-filter-btn"
                    :class="{ active: activeColorFilter === cf.color }"
                    :title="cf.color"
                    @click="setColorFilter(cf.color)"
                  >
                    <span v-if="cf.hex" class="color-circle" :style="{ backgroundColor: cf.hex }"></span>
                    <span class="color-text">{{ cf.label }}</span>
                  </button>
                </div>
                <div class="main-image">
                  <img :src="mainQuickImage" :alt="quickProduct.name" @error="onZoomImageError($event, mainQuickImage)">
                  <button class="image-zoom-btn" @click="openZoom(mainQuickImage, quickProduct.name)"><i class="fas fa-expand"></i></button>
                </div>
                <div v-if="visibleThumbs.length > 0" class="thumbnail-gallery-container">
                  <button v-if="showThumbArrows" class="gallery-arrow left" @click="scrollThumbs(-1)"><i class="fas fa-chevron-left"></i></button>
                  <div ref="thumbGalleryRef" class="thumbnail-gallery">
                    <img
                      v-for="img in visibleThumbs"
                      :key="img.id || img.url"
                      :src="img.resolvedUrl"
                      :alt="img.alt_text || 'Miniatura'"
                      class="thumbnail"
                      :class="{ active: img.resolvedUrl === mainQuickImage }"
                      @click="mainQuickImage = img.resolvedUrl"
                    >
                  </div>
                  <button v-if="showThumbArrows" class="gallery-arrow right" @click="scrollThumbs(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
              </div>

              <!-- Info del producto -->
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

                <!-- Variantes -->
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
                        <tr><th>Color</th><th>Talla</th><th>Precio</th><th>Stock</th><th>Estado</th></tr>
                      </thead>
                      <tbody>
                        <tr v-for="v in quickSizeVariants" :key="v.id">
                          <td data-label="Color">{{ v.color_name || 'General' }}</td>
                          <td data-label="Talla">{{ v.size_name || 'Única' }}</td>
                          <td data-label="Precio">{{ formatCurrency(v.price) }}</td>
                          <td data-label="Stock">{{ v.quantity }}</td>
                          <td data-label="Estado">
                            <span class="variant-status" :class="v.is_active ? 'active' : 'inactive'">
                              {{ v.is_active ? 'Activo' : 'Inactivo' }}
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
          <div class="modal-footer">
            <RouterLink
              v-if="quickProduct"
              :to="{ name: 'admin-product-edit', params: { id: quickProduct.id } }"
              class="btn btn-primary"
              @click="closeQuickView"
            >
              <i class="fas fa-edit"></i> Editar Producto
            </RouterLink>
            <button class="btn btn-secondary" @click="closeQuickView">Cerrar</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal zoom de imagen -->
    <Transition name="fade-scale">
      <div v-if="showZoom" class="modal-overlay image-zoom-modal" @click.self="closeZoom">
        <div class="modal-content zoom-content">
          <div class="modal-header">
            <h3>{{ zoomTitle || 'Imagen del producto' }}</h3>
            <button class="modal-close" @click="closeZoom">&times;</button>
          </div>
          <div class="modal-body zoom-body">
            <img :src="zoomImage" :alt="zoomTitle" @error="onZoomImageError($event, zoomImage)">
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

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

// Quick-view
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

// Zoom
const showZoom = ref(false)
const zoomImage = ref('')
const zoomTitle = ref('')

// Colores y tallas únicos del quick-view
const quickColors = computed(() => [...new Set(quickSizeVariants.value.map(v => v.color_name).filter(Boolean))])
const quickSizes = computed(() => [...new Set(quickSizeVariants.value.map(v => v.size_name).filter(Boolean))])
const quickVariantCount = computed(() => quickSizeVariants.value.length || Number(quickProduct.value?.variant_count || 0))
const showVariantTable = computed(() => quickSizeVariants.value.length > 1 || quickColors.value.length > 1 || quickSizes.value.length > 1)

// Filtros de color para galería
const colorFilters = computed(() => {
  const filters = [{ color: 'General', label: 'Principal', hex: null }]
  const seen = new Set(['General'])

  // Priorizar colores presentes en imágenes para mantener comportamiento legacy.
  quickImages.value.forEach(img => {
    if (img.color_name && img.color_name !== 'General' && !seen.has(img.color_name)) {
      seen.add(img.color_name)
      filters.push({ color: img.color_name, label: img.color_name, hex: img.hex_code || '#CCC' })
    }
  })

  // Fallback: si faltan colores en imágenes, usar colores de variantes.
  quickSizeVariants.value.forEach(v => {
    const color = v.color_name || 'General'
    if (color !== 'General' && !seen.has(color)) {
      seen.add(color)
      filters.push({ color, label: color, hex: '#D0D7E2' })
    }
  })

  return filters
})

// Thumbnails visibles según filtro de color
const visibleThumbs = computed(() => {
  if (activeColorFilter.value === 'General') {
    return quickImages.value.filter(img => !img.color_name || img.color_name === 'General' || img.is_primary)
      .length > 0
      ? quickImages.value.filter(img => !img.color_name || img.color_name === 'General' || img.is_primary)
      : quickImages.value
  }
  const byColor = quickImages.value.filter(img => img.color_name === activeColorFilter.value)
  return byColor.length > 0 ? byColor : quickImages.value
})
const showThumbArrows = computed(() => visibleThumbs.value.length > 4)

// Cuenta de filtros activos
const activeFilterCount = computed(() => {
  let count = 0
  if (categoryFilter.value) count++
  if (statusFilter.value) count++
  if (genderFilter.value) count++
  if (sortOrder.value !== 'newest') count++
  return count
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / perPage.value)))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredProducts.value.slice(start, end)
})

const showingCount = computed(() => paginatedProducts.value.length)

const paginationItems = computed(() => {
  const pages = totalPages.value
  const current = currentPage.value
  if (pages <= 7) return Array.from({ length: pages }, (_, i) => i + 1)

  const items = [1]
  const start = Math.max(2, current - 1)
  const end = Math.min(pages - 1, current + 1)

  if (start > 2) items.push('...')
  for (let i = start; i <= end; i++) items.push(i)
  if (end < pages - 1) items.push('...')

  items.push(pages)
  return items
})

const filteredProducts = computed(() => {
  let result = products.value
  if (statusFilter.value !== '') {
    result = result.filter(p => p.is_active === (statusFilter.value === '1'))
  }
  if (genderFilter.value) {
    result = result.filter(p => normalizeGender(p.gender) === genderFilter.value)
  }
  // Ordenar
  const sorted = [...result]
  switch (sortOrder.value) {
    case 'name_asc': sorted.sort((a, b) => (a.name || '').localeCompare(b.name || '')); break
    case 'name_desc': sorted.sort((a, b) => (b.name || '').localeCompare(a.name || '')); break
    case 'price_asc': sorted.sort((a, b) => (a.min_price || a.price || 0) - (b.min_price || b.price || 0)); break
    case 'price_desc': sorted.sort((a, b) => (b.max_price || b.price || 0) - (a.max_price || a.price || 0)); break
    case 'stock_asc': sorted.sort((a, b) => a.stock - b.stock); break
    case 'stock_desc': sorted.sort((a, b) => b.stock - a.stock); break
    default: break
  }
  return sorted
})

function formatPriceRange(product) {
  const min = Number(product.min_price || product.price || 0)
  const max = Number(product.max_price || product.price || 0)
  if (min === max || max === 0) return `$${min.toLocaleString('es-CO')}`
  return `$${min.toLocaleString('es-CO')} - $${max.toLocaleString('es-CO')}`
}

function formatCurrency(value) {
  return `$${Number(value || 0).toLocaleString('es-CO')}`
}

function normalizeGender(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

function productImage(product) {
  return resolveMediaUrl(product.primary_image || product.image || product.product_image || product.imagen || product.image_url, 'product')
}

function onProductImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'product')
}

function onZoomImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'product')
}

function normalizeVariant(rawVariant) {
  return {
    ...rawVariant,
    id: rawVariant.id || `${rawVariant.color_name || rawVariant.color || 'general'}-${rawVariant.size_name || rawVariant.size || 'unica'}-${rawVariant.price || rawVariant.precio || 0}`,
    color_name: rawVariant.color_name || rawVariant.color || '',
    size_name: rawVariant.size_name || rawVariant.size || '',
    price: Number(rawVariant.price ?? rawVariant.precio ?? 0),
    quantity: Number(rawVariant.quantity ?? rawVariant.stock ?? rawVariant.cantidad ?? 0),
    is_active: typeof rawVariant.is_active === 'boolean'
      ? rawVariant.is_active
      : Boolean(Number(rawVariant.is_active ?? rawVariant.activo ?? 1)),
  }
}

function buildQuickImages(product, imageRows) {
  const normalizedImages = imageRows
    .map(img => ({
      ...img,
      color_name: img.color_name || 'General',
      resolvedUrl: resolveMediaUrl(img.url || img.image_path || img.image, 'product'),
      is_primary: Boolean(Number(img.is_primary ?? 0)),
    }))
    .filter(img => img.resolvedUrl)

  const productImageUrl = product.image || resolveMediaUrl(product.rawImage, 'product')
  if (productImageUrl && !normalizedImages.some(img => img.resolvedUrl === productImageUrl)) {
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
    is_active: typeof rawProduct.is_active === 'boolean'
      ? rawProduct.is_active
      : Boolean(Number(rawProduct.activo ?? 1)),
  }
}

function setColorFilter(color) {
  activeColorFilter.value = color
  const filtered = visibleThumbs.value
  if (filtered.length > 0) {
    mainQuickImage.value = filtered[0].resolvedUrl
  }
}

function goToPage(page) {
  const next = Math.min(Math.max(1, page), totalPages.value)
  currentPage.value = next
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function applyFilters() {
  currentPage.value = 1
  loadProducts()
}

function scrollThumbs(direction) {
  if (thumbGalleryRef.value) {
    thumbGalleryRef.value.scrollBy({ left: direction * 150, behavior: 'smooth' })
  }
}

async function openQuickView(product) {
  showQuickView.value = true
  quickViewLoading.value = true
  quickProduct.value = product
  activeColorFilter.value = 'General'

  try {
    const res = await catalogHttp.get(`/admin/products/${product.id}`)
    const data = res.data?.data || res.data || {}
    const productData = data.product || product
    const imageRows = Array.isArray(data.images) ? data.images : []
    const sizeVars = (Array.isArray(data.size_variants) ? data.size_variants : (Array.isArray(data.variants) ? data.variants : []))
      .map(normalizeVariant)

    quickProduct.value = normalizeProduct(productData)
    quickSizeVariants.value = sizeVars
    quickTotalStock.value = Number(data.total_stock ?? quickProduct.value.stock ?? sizeVars.reduce((total, variant) => total + variant.quantity, 0))
    quickMinPrice.value = Number(data.min_price ?? quickProduct.value.min_price ?? quickProduct.value.price ?? 0)
    quickMaxPrice.value = Number(data.max_price ?? quickProduct.value.max_price ?? quickProduct.value.price ?? 0)

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
function openZoom(image, title) { zoomImage.value = image; zoomTitle.value = title; showZoom.value = true }
function closeZoom() { showZoom.value = false }

function clearAllFilters() {
  search.value = ''
  categoryFilter.value = ''
  statusFilter.value = ''
  genderFilter.value = ''
  sortOrder.value = 'newest'
  currentPage.value = 1
  loadProducts()
}

let _debounceTimer = null
function debouncedLoad() {
  clearTimeout(_debounceTimer)
  _debounceTimer = setTimeout(() => {
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
    if (statusFilter.value !== '') adminParams.status = statusFilter.value === '1' ? 'active' : 'inactive'

    try {
      const adminRes = await catalogHttp.get('/admin/products', { params: adminParams })
      const adminData = adminRes.data?.data || adminRes.data || []
      const adminRows = Array.isArray(adminData) ? adminData : (adminData.data || [])
      products.value = adminRows.map(normalizeProduct)
      currentPage.value = 1
      return
    } catch (adminError) {
      if (adminError?.response?.status !== 401 && adminError?.response?.status !== 403) {
        throw adminError
      }
    }

    const publicParams = { limit: 200 }
    if (search.value) publicParams.search = search.value
    if (categoryFilter.value) publicParams.category_id = categoryFilter.value

    const res = await catalogHttp.get('/products', { params: publicParams })
    const data = res.data?.data || res.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    products.value = rows.map(normalizeProduct)
    currentPage.value = 1
  } catch (err) {
    showSnackbar({ type: 'error', message: 'Error cargando productos' })
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const res = await catalogHttp.get('/categories')
    categories.value = res.data?.data || res.data || []
  } catch { /* ignora */ }
}

function confirmToggleStatus(product) {
  const action = product.is_active ? 'desactivar' : 'activar'
  const actionCap = product.is_active ? 'Desactivar' : 'Activar'
  showAlert({
    type: product.is_active ? 'warning' : 'success',
    title: `${actionCap} producto`,
    message: `¿Estás seguro de ${action} "${product.name}"?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      { text: actionCap, style: product.is_active ? 'warning' : 'success', callback: () => toggleProductStatus(product) },
    ],
  })
}

async function toggleProductStatus(product) {
  try {
    const newStatus = !product.is_active
    await catalogHttp.patch(`/admin/products/${product.id}/status`, { is_active: newStatus })
    showSnackbar({ type: 'success', message: `Producto ${newStatus ? 'activado' : 'desactivado'}` })
    loadProducts()
  } catch {
    showSnackbar({ type: 'error', message: 'Error al cambiar el estado del producto' })
  }
}

function exportProducts() {
  // Exportar lista de productos como CSV
  const rows = filteredProducts.value
  if (rows.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay productos para exportar' })
    return
  }
  const header = ['ID', 'Nombre', 'Categoría', 'Stock', 'Precio Min', 'Precio Max', 'Estado']
  const csvRows = [header.join(',')]
  rows.forEach(p => {
    csvRows.push([
      p.id,
      `"${(p.name || '').replace(/"/g, '""')}"`,
      `"${(p.category_name || '').replace(/"/g, '""')}"`,
      p.stock,
      p.min_price || p.price || 0,
      p.max_price || p.price || 0,
      p.is_active ? 'Activo' : 'Inactivo',
    ].join(','))
  })
  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'productos.csv'
  a.click()
  URL.revokeObjectURL(url)
}

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.8rem; border-bottom: 1px solid #e6eef6; }
.page-header h1 { display: inline-flex; align-items: center; gap: 1rem; margin: 0; font-size: clamp(2rem, 2.6vw, 2.8rem); font-weight: 700; color: #18263d; }
.page-header h1 i { width: 3.5rem; height: 3.5rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #eaf6ff; color: #0077b6; box-shadow: 0 14px 30px rgba(0, 119, 182, .18); }
.breadcrumb { padding-top: .8rem; color: #70859c; font-size: 1.05rem; }
.breadcrumb a { color: #0077b6; text-decoration: none; font-weight: 600; }
.filters-card { margin-bottom: 2rem; padding: 0; overflow: hidden; background: #fff; border: 1px solid #d9e8f4; border-radius: 26px; box-shadow: 0 14px 32px rgba(15, 55, 96, .08); }
.filters-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1.75rem 2rem; border-bottom: 1px solid #edf3f8; }
.filters-title { display: flex; align-items: center; gap: .95rem; }
.filters-title i { width: 3rem; height: 3rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 1rem; background: #eef7ff; color: #0077b6; box-shadow: 0 12px 24px rgba(0, 119, 182, .12); }
.filters-title h3 { margin: 0; font-size: 1.45rem; font-weight: 700; color: #24364b; }
.filters-toggle { width: 3.25rem; height: 3.25rem; border: 1px solid #cfe2f2; background: #fff; color: #45617d; border-radius: 1.1rem; transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease; cursor: pointer; }
.filters-toggle:hover { border-color: #0077b6; box-shadow: 0 10px 20px rgba(0, 119, 182, .12); }
.filters-toggle.collapsed { transform: rotate(180deg); }
.search-bar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1rem; padding: 1.75rem 2rem; }
.search-input-wrapper { position: relative; }
.search-input { width: 100%; height: 4.25rem; padding: 0 3.25rem 0 3.25rem; border: 1px solid #cfe2f2; border-radius: 1.4rem; font-size: 1.08rem; color: #24364b; transition: border-color .25s ease, box-shadow .25s ease; }
.search-input:focus { outline: none; border-color: #0077b6; box-shadow: 0 0 0 4px rgba(0, 119, 182, .1); }
.search-icon { position: absolute; left: 1.15rem; top: 50%; transform: translateY(-50%); color: #6a96cf; font-size: 1.1rem; }
.search-clear { position: absolute; right: .85rem; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #90a4b7; cursor: pointer; font-size: 1rem; }
.search-submit-btn { min-width: 9.5rem; height: 4.25rem; display: inline-flex; align-items: center; justify-content: center; gap: .6rem; border: none; background: #0077b6; color: #fff; border-radius: 1.4rem; padding: 0 1.65rem; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 18px 30px rgba(0, 119, 182, .18); transition: transform .25s ease, box-shadow .25s ease, background-color .25s ease; }
.search-submit-btn:hover { background: #0068a1; transform: translateY(-2px); box-shadow: 0 22px 34px rgba(0, 119, 182, .22); }
.filters-advanced { padding: 0 2rem 2rem; }
.filters-row { display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap: 1rem; }
.filter-group label { display: flex; align-items: center; gap: .45rem; margin-bottom: .55rem; font-size: .95rem; color: #4f657b; font-weight: 600; }
.filter-group select { width: 100%; height: 3.3rem; border-radius: 1rem; border: 1px solid #cde0ef; padding: 0 .95rem; color: #24364b; transition: border-color .25s ease, box-shadow .25s ease; }
.filter-group select:focus { outline: none; border-color: #0077b6; box-shadow: 0 0 0 4px rgba(0, 119, 182, .08); }
.filters-actions-bar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-top: 1.5rem; padding-top: 1.3rem; border-top: 1px solid #edf2f7; }
.active-filters { display: flex; align-items: center; gap: .55rem; font-size: .95rem; color: #4f657b; font-weight: 600; }
.filters-buttons, .quick-actions { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.btn-clear-filters { border: none; background: transparent; color: #0077b6; cursor: pointer; display: inline-flex; align-items: center; gap: .45rem; font-size: .95rem; font-weight: 600; }
.btn-apply-filters, .quick-actions .btn, .quick-actions button { min-height: 3.15rem; display: inline-flex; align-items: center; justify-content: center; gap: .55rem; padding: 0 1.2rem; border-radius: 1rem; text-decoration: none; font-weight: 700; cursor: pointer; transition: transform .25s ease, box-shadow .25s ease, background-color .25s ease, border-color .25s ease; }
.btn-apply-filters { border: 1px solid #0077b6; background: #0077b6; color: #fff; box-shadow: 0 14px 24px rgba(0, 119, 182, .16); }
.btn-apply-filters:hover, .quick-actions .btn-primary:hover { background: #0068a1; border-color: #0068a1; transform: translateY(-2px); box-shadow: 0 18px 30px rgba(0, 119, 182, .2); }
.results-summary { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.25rem; padding: 1.25rem 1.6rem; background: #fff; border: 1px solid #d9e8f4; border-radius: 1.65rem; box-shadow: 0 14px 28px rgba(15, 55, 96, .08); }
.results-summary p { margin: 0; color: #0077b6; font-size: 1.12rem; font-weight: 700; }
.quick-actions .btn-primary { background: #0077b6; color: #fff; border: 1px solid #0077b6; box-shadow: 0 16px 28px rgba(0, 119, 182, .16); }
.btn.btn-icon { display: inline-flex; align-items: center; gap: .5rem; border: 1px solid #cde0ef; background: #fff; padding: .5rem 1rem; border-radius: 10px; cursor: pointer; color: #0e5f99; font-weight: 600; }
.btn.btn-icon:hover { border-color: #0077b6; color: #0077b6; transform: translateY(-2px); box-shadow: 0 16px 28px rgba(0, 119, 182, .12); }
.pagination-container { margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: .45rem; flex-wrap: wrap; }
.pagination-item { min-width: 40px; height: 40px; border: 1px solid #d5e3f0; background: #fff; color: #325372; border-radius: .8rem; cursor: pointer; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; padding: 0 .7rem; transition: all .2s ease; }
.pagination-item:hover:not(:disabled):not(.active):not(.dots) { border-color: #0077b6; color: #0077b6; }
.pagination-item.active { background: #0077b6; color: #fff; border-color: #0077b6; box-shadow: 0 12px 22px rgba(0, 119, 182, .18); }
.pagination-item:disabled { opacity: .45; cursor: not-allowed; }
.pagination-item.dots { border-style: dashed; cursor: default; }
.products-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; padding: 20px 0; }
.product-admin-card { position: relative; border: 1px solid #b3d9ff; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 2px 8px rgba(0, 119, 182, .08); transition: all .3s ease; animation: fadeInUp .45s ease both; }
.product-admin-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0, 119, 182, .15); border-color: #0077b6; }
.product-admin-select { position: absolute; top: 12px; left: 12px; z-index: 3; }
.product-admin-select input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: #0077b6; }
.product-admin-status { position: absolute; top: 12px; right: 12px; z-index: 3; }
.status-badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
.status-badge.status-active { background: #e6f7eb; color: #1d7a3f; }
.status-badge.status-inactive { background: #ffe9e9; color: #b52e2e; }
.product-admin-image { position: relative; height: 220px; background: #f8f9fa; }
.product-admin-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease; }
.product-admin-card:hover .product-admin-image img { transform: scale(1.05); }
.product-admin-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, .55); opacity: 0; transition: opacity .3s ease; }
.product-admin-card:hover .product-admin-overlay { opacity: 1; }
.btn-overlay { border: none; width: 48px; height: 48px; border-radius: 50%; background: #fff; color: #0077b6; font-size: 1.15rem; cursor: pointer; transition: all .25s ease; }
.btn-overlay:hover { background: #0077b6; color: #fff; transform: scale(1.08); }
.product-admin-body { padding: 16px; display: flex; flex-direction: column; gap: 12px; }
.product-admin-header { display: flex; flex-direction: column; gap: 4px; margin-bottom: 0; }
.product-admin-title { margin: 0; font-size: 1.08rem; font-weight: 700; line-height: 1.35; }
.product-admin-title a { color: #2d3748; text-decoration: none; }
.product-admin-title a:hover { color: #0077b6; }
.product-admin-id { font-size: 11px; color: #718096; white-space: nowrap; flex-shrink: 0; font-weight: 500; }
.product-admin-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 0; }
.meta-item { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #4d6880; background: #f7fafc; border-radius: 999px; padding: 4px 10px; border: 1px solid #e2e8f0; }
.product-admin-info { display: flex; flex-direction: column; gap: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
.info-item { display: flex; align-items: center; justify-content: space-between; gap: .75rem; font-size: 13px; }
.info-item label { color: #718096; font-weight: 500; margin: 0; }
.stock-value { font-weight: 600; color: #2d3748; }
.stock-value.low-stock { color: #e53e3e; }
.price-value { font-weight: 700; color: #0077b6; font-size: 14px; }
.product-admin-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 12px 16px; background: #f7fafc; border-top: 1px solid #e8eff5; }
.btn-action { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 40px; border-radius: 10px; text-decoration: none; font-weight: 700; border: 1px solid transparent; cursor: pointer; font-size: 13px; transition: all .2s ease; }
.btn-edit { background: #0077b6; color: #fff; }
.btn-edit:hover, .btn-toggle-off:hover, .btn-toggle-on:hover { transform: translateY(-2px); }
.btn-toggle-off { background: #fff5f5; color: #e53e3e; border-color: #feb2b2; }
.btn-toggle-on { background: #ecfdf5; color: #15803d; border-color: #bbf7d0; }

/* Modal overlay */
.modal-overlay { position: fixed; inset: 0; padding: 1.5rem; background: rgba(15, 23, 42, .52); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.modal-content { width: min(1500px, 94vw); background: #fff; border-radius: 24px; overflow: hidden; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 32px 80px rgba(15, 23, 42, .24); }
.quick-view-modal .modal-content { width: min(1110px, 92vw); max-height: 90vh; }
.modal-header, .modal-footer { padding: 1.75rem 2rem; border-bottom: 1px solid #edf2f7; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-shrink: 0; }
.modal-footer { border-bottom: 0; border-top: 1px solid #edf2f7; justify-content: flex-end; background: #fff; }
.modal-footer .btn { min-height: 3.5rem; display: inline-flex; align-items: center; justify-content: center; gap: .6rem; padding: 0 1.5rem; border-radius: 1.05rem; text-decoration: none; font-size: 1rem; font-weight: 700; border: 1px solid transparent; cursor: pointer; transition: transform .25s ease, box-shadow .25s ease, background-color .25s ease; }
.modal-footer .btn-primary { background: #0077b6; border-color: #0077b6; color: #fff; box-shadow: 0 18px 28px rgba(0, 119, 182, .2); }
.modal-footer .btn-primary:hover { background: #0068a1; transform: translateY(-2px); }
.modal-footer .btn-secondary { background: #fff; border-color: #d9e1ea; color: #111827; }
.modal-footer .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 14px 24px rgba(15, 23, 42, .08); }
.modal-body { padding: 1.6rem 2rem 2rem; overflow: auto; flex: 1; }
.modal-close { border: none; background: transparent; font-size: 2rem; line-height: 1; cursor: pointer; color: #7c8fa3; transition: color .2s ease, transform .2s ease; }
.modal-close:hover { color: #e53e3e; transform: rotate(90deg); }
.modal-title-with-icon { display: inline-flex; align-items: center; gap: 1rem; margin: 0; font-size: clamp(1.7rem, 2.2vw, 2.3rem); font-weight: 700; color: #202733; }
.modal-title-icon { width: 44px; height: 44px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: #e9f4fb; color: #0077b6; font-size: 1rem; box-shadow: 0 12px 24px rgba(0, 119, 182, .14); }

/* Quick-view layout */
.quick-view-content { display: grid; grid-template-columns: 350px minmax(0, 1fr); gap: 24px; align-items: start; }
.quick-view-gallery { display: flex; flex-direction: column; gap: 12px; }
.gallery-filters { display: flex; flex-wrap: wrap; gap: 4px; }
.color-filter-btn { display: inline-flex; align-items: center; gap: 6px; border: 2px solid #0077b6; background: #fff; padding: 8px 12px; border-radius: 999px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all .25s ease; color: #0077b6; }
.color-filter-btn:hover { background: #f0f8ff; }
.color-filter-btn.active { border-color: #0077b6; background: #0077b6; color: #fff; }
.color-circle { width: 16px; height: 16px; border-radius: 50%; display: inline-block; border: 1px solid #c3c9d2; }
.main-image { position: relative; border-radius: 16px; overflow: hidden; border: 1px solid #d8e4f0; background: #f8fafc; box-shadow: 0 4px 12px rgba(0, 119, 182, .15); }
.main-image img { width: 100%; height: 350px; object-fit: cover; background: #f8fafc; display: block; transition: transform .3s ease; }
.main-image:hover img { transform: scale(1.02); }
.image-zoom-btn { position: absolute; top: 12px; right: 12px; border: none; background: rgba(0, 0, 0, .7); color: #fff; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: all .25s ease; }
.image-zoom-btn:hover { background: #0077b6; transform: scale(1.08); }
.thumbnail-gallery-container { position: relative; display: flex; align-items: center; gap: 8px; padding: 0 4px; }
.gallery-arrow { border: 1px solid #c9d8e8; background: rgba(255, 255, 255, .95); width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all .25s ease; }
.gallery-arrow:hover { background: #0077b6; color: #fff; border-color: #0077b6; }
.thumbnail-gallery { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: thin; flex: 1; padding: 4px; }
.thumbnail-gallery::-webkit-scrollbar { height: 4px; }
.thumbnail-gallery::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 999px; }
.thumbnail-gallery::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
.thumbnail { width: 60px; height: 60px; object-fit: cover; border: 2px solid transparent; border-radius: 6px; cursor: pointer; flex-shrink: 0; transition: all .2s ease; }
.thumbnail.active, .thumbnail:hover { border-color: #0a74b8; transform: scale(1.05); box-shadow: 0 2px 8px rgba(0, 119, 182, .2); }

/* Quick-view info */
.quick-view-info { display: flex; flex-direction: column; gap: 20px; }
.product-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
.product-header h2 { margin: 0; font-size: 2rem; color: #2d3f54; font-weight: 700; }
.product-id { font-size: 1rem; color: #718096; background: transparent; padding: .2rem 0; border-radius: 0; white-space: nowrap; font-weight: 500; }
.product-meta { display: flex; flex-wrap: wrap; gap: 12px; }
.product-meta .meta-item { background: #f5f8fc; border: 1px solid #dde7f2; padding: 8px 12px; border-radius: 10px; font-size: 14px; color: #506a84; }
.product-description h4, .product-pricing h4, .variants-section h4 { margin: 0 0 12px; color: #33475b; font-size: 1.1rem; font-weight: 700; }
.product-description p { color: #5a6f85; margin: 0; font-size: 1rem; line-height: 1.6; }
.product-pricing p { font-weight: 700; color: #0876b6; font-size: 1.95rem; margin: 0; }

/* Variantes */
.variants-section { margin-top: .5rem; padding-top: 20px; border-top: 1px solid #e2e8f0; }
.variant-group { margin-bottom: 1rem; }
.variant-group label { display: block; font-weight: 700; color: #4f657b; margin-bottom: 8px; font-size: 14px; }
.color-options, .size-options { display: inline-flex; flex-wrap: wrap; gap: 8px; }
.color-tag, .size-tag { display: inline-block; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 13px; font-weight: 500; transition: all .2s ease; }
.color-tag { background: #f7fafc; color: #4f657b; }
.size-tag { background: #f7fafc; color: #4f657b; }
.color-tag:hover, .size-tag:hover { background: #f0f8ff; border-color: #0077b6; color: #1e40af; }
.variant-table { margin-top: .75rem; overflow-x: auto; }
.variant-table table { width: 100%; border-collapse: collapse; font-size: 14px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 119, 182, .08); }
.variant-table th { background: #f0f8ff; color: #1e40af; font-weight: 700; padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.variant-table td { padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748; }
.variant-table tr:hover td { background: #f7fafc; }
.variant-status { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.variant-status.active { background: #e6f7eb; color: #1d7a3f; }
.variant-status.inactive { background: #ffe9e9; color: #b52e2e; }

/* Zoom modal */
.zoom-content { width: min(980px, 96vw); }
.zoom-body { text-align: center; min-height: 25rem; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.zoom-body img { width: 100%; max-height: 76vh; object-fit: contain; border-radius: .9rem; box-shadow: 0 16px 36px rgba(15, 23, 42, .2); }

/* Skeleton */
.products-skeleton { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; padding: 20px 0; }
.product-skeleton-card { border: 1px solid #b3d9ff; border-radius: 12px; overflow: hidden; background: #fff; padding: 16px; box-shadow: 0 2px 8px rgba(0, 119, 182, .08); display: flex; flex-direction: column; gap: 16px; }
.skeleton { background: linear-gradient(90deg, #edf2f7 25%, #f7fafc 50%, #edf2f7 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 8px; }
.skeleton-thumb { height: 160px; }
.skeleton-body { padding: 0; display: flex; flex-direction: column; gap: 10px; }
.skeleton-line { height: 12px; margin-bottom: 0; }
.skeleton-tags { display: flex; gap: 8px; margin-bottom: 0; }
.skeleton-pill { height: 20px; width: 90px; border-radius: 999px; }
.w-80 { width: 80%; }
.w-70 { width: 70%; }
.w-60 { width: 60%; }
.w-40 { width: 40%; }
.skeleton-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.skeleton-btn { height: 36px; border-radius: 10px; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Transitions */
.card-fade-enter-active, .card-fade-leave-active { transition: all .24s ease; }
.card-fade-enter-from, .card-fade-leave-to { opacity: 0; transform: translateY(8px); }
.fade-scale-enter-active, .fade-scale-leave-active { transition: all .2s ease; }
.fade-scale-enter-from, .fade-scale-leave-to { opacity: 0; transform: scale(.97); }

@media (max-width: 900px) {
  .quick-view-content { grid-template-columns: 1fr; }
  .search-bar { grid-template-columns: 1fr; }
  .filters-row { grid-template-columns: repeat(2, 1fr); }
  .results-summary { flex-direction: column; align-items: flex-start; gap: .75rem; }
  .filters-actions-bar { flex-direction: column; align-items: flex-start; gap: .6rem; }
  .main-image img { height: 420px; }
}
@media (max-width: 600px) {
  .filters-row { grid-template-columns: 1fr; }
  .modal-title-with-icon { font-size: 1.35rem; }
  .product-header h2 { font-size: 1.5rem; }
  .page-header,
  .results-summary,
  .filters-actions-bar { flex-direction: column; align-items: flex-start; }
  .products-admin-grid,
  .products-skeleton { grid-template-columns: 1fr; }
  .product-admin-actions { grid-template-columns: 1fr; }
  .modal-overlay { padding: .75rem; }
  .main-image img { height: 280px; }
  .modal-footer { flex-direction: column-reverse; }
  .modal-footer .btn { width: 100%; }
}
</style>
