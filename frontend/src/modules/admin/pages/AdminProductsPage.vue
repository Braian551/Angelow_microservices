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
import { RouterLink } from 'vue-router'
import { useAdminProducts } from '../composables/useAdminProducts'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminProductCard from '../components/AdminProductCard.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import '../views/AdminProductsPage.css'

// =====================================================
// Lógica administrativa reutilizable
// =====================================================
const {
  activeColorFilter,
  activeFilterCount,
  applyFilters,
  categories,
  categoryFilter,
  clearAllFilters,
  closeQuickView,
  closeZoom,
  colorFilters,
  confirmToggleStatus,
  debouncedLoad,
  exportProducts,
  exportingFormat,
  filteredProducts,
  formatCurrency,
  genderFilter,
  loading,
  mainQuickImage,
  onProductCardImageError,
  onZoomImageError,
  openQuickView,
  openZoom,
  pagination,
  quickColors,
  quickMaxPrice,
  quickMinPrice,
  quickProduct,
  quickSizes,
  quickSizeVariants,
  quickTotalStock,
  quickVariantCount,
  quickViewLoading,
  scrollThumbs,
  search,
  selectedProducts,
  setColorFilter,
  showQuickView,
  showThumbArrows,
  showVariantTable,
  showZoom,
  sortOrder,
  statusFilter,
  thumbGalleryRef,
  toggleSelection,
  visibleThumbs,
  zoomImage,
  zoomTitle,
} = useAdminProducts()
</script>

