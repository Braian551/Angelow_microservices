<template>
  <div>
    <AdminPageHeader
      :icon="isEditing ? 'fas fa-edit' : 'fas fa-plus-circle'"
      :title="isEditing ? 'Editar producto' : 'Nuevo producto'"
      :subtitle="isEditing ? 'Actualiza la ficha, variantes y precios del producto.' : 'Crea un producto completo con informacion, variantes e inventario.'"
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Productos', to: '/admin/productos' }, { label: isEditing ? 'Editar' : 'Nuevo' }]"
    >
      <template #actions>
        <RouterLink to="/admin/productos" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver
        </RouterLink>
      </template>
    </AdminPageHeader>

    <AdminCard>
      <div v-if="initialLoading" class="product-form-loading">
        <AdminShimmer :rows="1" variant="banner" />
        <AdminShimmer :rows="5" />
      </div>

      <form v-else class="product-form" @submit.prevent="saveProduct">
        <div class="admin-tabs product-form-tabs">
          <button type="button" class="admin-tab" :class="{ active: activeTab === 'general' }" @click="activeTab = 'general'">
            Informacion general
          </button>
          <button type="button" class="admin-tab" :class="{ active: activeTab === 'variants' }" @click="activeTab = 'variants'">
            Variantes y precios
          </button>
        </div>

        <section v-show="activeTab === 'general'" class="product-form-panel">
          <div class="product-form-grid">
            <div class="product-form-main">
              <div class="product-form-section">
                <div class="form-row">
                  <div class="form-group">
                    <label for="product-name">Nombre del producto *</label>
                    <input id="product-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="validateField('name')">
                    <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
                  </div>
                  <div class="form-group">
                    <label for="product-slug">Slug</label>
                    <input id="product-slug" v-model="form.slug" class="form-control" placeholder="se-genera-automaticamente">
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="product-brand">Marca</label>
                    <input id="product-brand" v-model="form.brand" class="form-control" placeholder="Angelow">
                  </div>
                  <div class="form-group">
                    <label for="product-gender">Genero</label>
                    <select id="product-gender" v-model="form.gender" class="form-control">
                      <option value="unisex">Unisex</option>
                      <option value="mujer">Mujer</option>
                      <option value="hombre">Hombre</option>
                      <option value="nina">Nina</option>
                      <option value="nino">Nino</option>
                    </select>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="product-category">Categoria *</label>
                    <select id="product-category" v-model="form.category_id" class="form-control" :class="{ 'is-invalid': errors.category_id }" @change="validateField('category_id')">
                      <option value="">Seleccionar...</option>
                      <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <p v-if="errors.category_id" class="form-error">{{ errors.category_id }}</p>
                  </div>
                  <div class="form-group">
                    <label for="product-collection-id">Coleccion</label>
                    <select id="product-collection-id" v-model="form.collection_id" class="form-control">
                      <option value="">Sin coleccion</option>
                      <option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option>
                    </select>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="product-price">Precio base *</label>
                    <input id="product-price" v-model="form.price" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': errors.price }" @input="validateField('price')">
                    <p v-if="errors.price" class="form-error">{{ errors.price }}</p>
                  </div>
                  <div class="form-group">
                    <label for="product-compare-price">Precio comparativo</label>
                    <input id="product-compare-price" v-model="form.compare_price" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': errors.compare_price }" @input="validateField('compare_price')">
                    <p v-if="errors.compare_price" class="form-error">{{ errors.compare_price }}</p>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="product-material">Material</label>
                    <input id="product-material" v-model="form.material" class="form-control" placeholder="Algodon, denim, cuero...">
                  </div>
                  <div class="form-group">
                    <label for="product-collection-name">Etiqueta de coleccion</label>
                    <input id="product-collection-name" v-model="form.collection" class="form-control" placeholder="Drop verano 2026">
                  </div>
                </div>

                <div class="form-group">
                  <label for="product-description">Descripcion</label>
                  <textarea id="product-description" v-model="form.description" class="form-control" rows="5" placeholder="Cuenta materiales, silueta, fit y atributos clave."></textarea>
                </div>

                <div class="form-group">
                  <label for="product-care">Instrucciones de cuidado</label>
                  <textarea id="product-care" v-model="form.care_instructions" class="form-control" rows="4" placeholder="Lavado, secado y recomendaciones de mantenimiento."></textarea>
                </div>
              </div>
            </div>

            <aside class="product-form-side">
              <div class="product-form-section image-panel">
                <div class="image-panel__header">
                  <div>
                    <h3>Imagen principal</h3>
                    <p>Sube una imagen o conserva una ruta existente.</p>
                  </div>
                  <button type="button" class="btn btn-secondary btn-sm" title="Seleccionar imagen" @click="triggerMainImagePicker">
                    <i class="fas fa-upload"></i>
                  </button>
                </div>

                <input ref="mainImageInput" type="file" accept="image/*" class="visually-hidden" @change="handleMainImageUpload">

                <div class="image-panel__preview" :class="{ empty: !mainImagePreview }">
                  <img v-if="mainImagePreview" :src="mainImagePreview" alt="Imagen principal" @error="onProductImageError($event, form.main_image_path)">
                  <div v-else>
                    <i class="fas fa-image"></i>
                    <p>Sin imagen principal</p>
                  </div>
                </div>


                <div class="image-panel__actions">

                  <button type="button" class="btn btn-secondary btn-sm text-danger" title="Limpiar" @click="removeMainImage">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>

              <div class="product-form-section status-panel">
                <h3>Visibilidad</h3>
                <div class="status-option">
                  <div>
                    <strong>Producto activo</strong>
                    <p>Visible para catalogo y procesos internos.</p>
                  </div>
                  <label class="toggle-switch">
                    <input v-model="form.is_active" type="checkbox">
                    <span class="toggle-slider"></span>
                  </label>
                </div>

                <div class="status-option">
                  <div>
                    <strong>Producto destacado</strong>
                    <p>Permite resaltarlo en vitrinas o listados especiales.</p>
                  </div>
                  <label class="toggle-switch">
                    <input v-model="form.is_featured" type="checkbox">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>
            </aside>
          </div>
        </section>

        <section v-show="activeTab === 'variants'" class="product-form-panel">
          <div class="variants-toolbar">
            <div>
              <h3>Variantes configuradas</h3>
              <p>Replica el flujo de Angelow: color, imagen y tallas con precio, stock, SKU y codigo de barras.</p>
            </div>
            <button type="button" class="btn btn-primary" @click="addVariant">
              <i class="fas fa-plus"></i> Agregar variante
            </button>
          </div>

          <p v-if="errors.variants" class="form-error product-form-panel-error">{{ errors.variants }}</p>

          <div class="variant-stats-grid">
            <article class="variant-stat-card">
              <span>Variantes</span>
              <strong>{{ form.variants.length }}</strong>
            </article>
            <article class="variant-stat-card">
              <span>Tallas activas</span>
              <strong>{{ totalSizeConfigurations }}</strong>
            </article>
            <article class="variant-stat-card">
              <span>Stock total</span>
              <strong>{{ totalStock }}</strong>
            </article>
          </div>

          <div class="variant-list">
            <article v-for="(variant, index) in form.variants" :key="variant.key" class="variant-card">
              <div class="variant-card__header">
                <div>
                  <p class="variant-card__eyebrow">Variante {{ index + 1 }}</p>
                  <h3>{{ colorName(variant.color_id) || 'Color pendiente' }}</h3>
                </div>

                <div class="variant-card__header-actions">
                  <label class="variant-default-pill">
                    <input :checked="variant.is_default" type="radio" name="default-variant" @change="setDefaultVariant(variant.key)">
                    <span>Principal</span>
                  </label>

                  <button type="button" class="btn btn-secondary btn-sm" title="Tallas y precios" @click="openVariantModal(variant.key)">
                    <i class="fas fa-tags"></i>
                  </button>

                  <button type="button" class="btn btn-secondary btn-sm text-danger" title="Eliminar" :disabled="form.variants.length === 1" @click="removeVariant(variant.key)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>

              <div class="variant-card__body">
                <div v-if="variant.color_id" class="color-preview-banner">
                  <span class="color-circle" :style="{ backgroundColor: colorHex(variant.color_id) }"></span>
                  {{ colorName(variant.color_id) }}
                </div>

                <div class="form-group">
                  <label :for="`variant-color-${variant.key}`">Color *</label>
                  <select :id="`variant-color-${variant.key}`" v-model="variant.color_id" class="form-control">
                    <option value="">Seleccionar color...</option>
                    <option v-for="color in colors" :key="color.id" :value="color.id">{{ color.name }}</option>
                  </select>
                </div>

                <div class="variant-card__media">
                  <div class="variant-card__preview" :class="{ empty: !variant.image_preview }">
                    <img v-if="variant.image_preview" :src="variant.image_preview" :alt="`Variante ${index + 1}`" @error="onProductImageError($event, variant.image_path)">
                    <div v-else>
                      <i class="fas fa-swatchbook"></i>
                      <p>Sin imagen</p>
                    </div>
                  </div>

                  <div class="variant-card__media-actions">
                    <button type="button" class="btn btn-secondary btn-sm" title="Subir imagen" @click="triggerVariantImagePicker(variant.key)">
                      <i class="fas fa-upload"></i>
                    </button>

                    <button type="button" class="btn btn-secondary btn-sm text-danger" title="Quitar" @click="removeVariantImage(variant.key)">
                      <i class="fas fa-times"></i>
                    </button>
                    <input :ref="(element) => setVariantImageInputRef(variant.key, element)" type="file" accept="image/*" class="visually-hidden" @change="(event) => handleVariantImageUpload(variant.key, event)">
                  </div>
                </div>

                <div class="variant-size-summary">
                  <div class="variant-size-summary__header">
                    <strong>Tallas configuradas</strong>
                    <span>{{ variant.sizes.length }} registro(s)</span>
                  </div>

                  <div v-if="variant.sizes.length" class="variant-size-pills">
                    <span v-for="size in variant.sizes" :key="size.key" class="variant-size-pill">
                      {{ sizeName(size.size_id) || 'Sin talla' }}
                      <small>{{ currencyLabel(size.price) }} / {{ size.quantity }} und</small>
                    </span>
                  </div>

                  <p v-else class="variant-size-summary__empty">Abre el modal para cargar precios, stock y codigos.</p>
                </div>
              </div>
            </article>
          </div>
        </section>

        <div class="product-form-footer">
          <RouterLink to="/admin/productos" class="btn btn-secondary">Cancelar</RouterLink>
          <button type="button" class="btn btn-secondary" @click="activeTab = activeTab === 'general' ? 'variants' : 'general'">
            <i class="fas fa-exchange-alt"></i> Cambiar vista
          </button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <i class="fas fa-save"></i> {{ saving ? 'Guardando...' : (isEditing ? 'Actualizar producto' : 'Guardar producto') }}
          </button>
        </div>
      </form>
    </AdminCard>

    <AdminModal :show="variantModalOpen" title="Configuracion de tallas y precios" max-width="980px" @close="closeVariantModal">
      <div v-if="activeVariant" class="variant-modal">
        <div class="variant-modal__intro">
          <div>
            <h3>{{ colorName(activeVariant.color_id) || 'Variante sin color' }}</h3>
            <p>Define tallas, precios comparativos, stock, SKU y codigo de barras.</p>
            
            <div v-if="activeVariant.color_id" class="color-preview-banner mt-3">
              <span class="color-circle" :style="{ backgroundColor: colorHex(activeVariant.color_id) }"></span>
              {{ colorName(activeVariant.color_id) }}
            </div>
          </div>

          <div class="variant-modal__add-size">
            <select v-model="selectedSizeId" class="form-control">
              <option value="">Agregar talla...</option>
              <option v-for="size in availableSizesForActiveVariant" :key="size.id" :value="size.id">{{ size.name }}</option>
            </select>
            <button v-show="selectedSizeId !== ''" type="button" class="btn btn-primary btn-sm" title="Agregar" @click="addSizeRowToActiveVariant">
              <i class="fas fa-plus"></i>
            </button>
          </div>
        </div>

        <div v-if="activeVariant.sizes.length" class="variant-modal__rows">
          <article v-for="sizeRow in activeVariant.sizes" :key="sizeRow.key" class="variant-modal__row">
            <div class="variant-modal__row-top">
              <strong>{{ sizeName(sizeRow.size_id) || 'Talla pendiente' }}</strong>
              <button type="button" class="btn btn-secondary btn-sm text-danger" title="Quitar" @click="removeSizeRow(activeVariant.key, sizeRow.key)">
                <i class="fas fa-trash"></i>
              </button>
            </div>

            <div class="variant-modal__grid">
              <div class="form-group">
                <label>Talla *</label>
                <select v-model="sizeRow.size_id" class="form-control">
                  <option value="">Seleccionar...</option>
                  <option v-for="size in sizes" :key="size.id" :value="size.id">{{ size.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Precio *</label>
                <input v-model="sizeRow.price" type="number" step="0.01" min="0" class="form-control">
              </div>
              <div class="form-group">
                <label>Precio comparativo</label>
                <input v-model="sizeRow.compare_price" type="number" step="0.01" min="0" class="form-control">
              </div>
              <div class="form-group">
                <label>Stock</label>
                <input v-model="sizeRow.quantity" type="number" step="1" min="0" class="form-control">
              </div>
              <div class="form-group">
                <label>SKU</label>
                <input v-model="sizeRow.sku" class="form-control" placeholder="SKU-001">
              </div>
              <div class="form-group">
                <label>Codigo de barras</label>
                <input v-model="sizeRow.barcode" class="form-control" placeholder="7700000000000">
              </div>
            </div>

            <div class="variant-modal__row-footer">
              <label class="toggle-switch">
                <input v-model="sizeRow.is_active" type="checkbox">
                <span class="toggle-slider"></span>
              </label>
              <span>{{ sizeRow.is_active ? 'Talla activa' : 'Talla inactiva' }}</span>
            </div>
          </article>
        </div>

        <div v-else class="variant-modal__empty">
          <i class="fas fa-tags"></i>
          <p>Selecciona una talla para empezar a construir la matriz de precio e inventario.</p>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-secondary" @click="closeVariantModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminShimmer from '../components/AdminShimmer.vue'

const route = useRoute()
const router = useRouter()
const { showSnackbar } = useSnackbarSystem()

const isEditing = computed(() => Boolean(route.params.id))
const saving = ref(false)
const initialLoading = ref(false)
const activeTab = ref('general')

const categories = ref([])
const collections = ref([])
const colors = ref([])
const sizes = ref([])

const mainImageInput = ref(null)
const mainImageFile = ref(null)
const mainImagePreview = ref('')
const variantImageInputs = ref({})

const variantModalOpen = ref(false)
const activeVariantKey = ref('')
const selectedSizeId = ref('')

let variantSeed = 0
let sizeSeed = 0

const form = reactive({
  name: '',
  slug: '',
  brand: '',
  gender: 'unisex',
  category_id: '',
  collection_id: '',
  collection: '',
  price: '',
  compare_price: '',
  material: '',
  description: '',
  care_instructions: '',
  main_image_path: '',
  is_featured: false,
  is_active: true,
  variants: [],
})

const errors = reactive({
  name: '',
  price: '',
  compare_price: '',
  category_id: '',
  variants: '',
})

const activeVariant = computed(() => form.variants.find((variant) => variant.key === activeVariantKey.value) || null)

const availableSizesForActiveVariant = computed(() => {
  if (!activeVariant.value) return sizes.value
  const selectedIds = new Set(activeVariant.value.sizes.map((size) => Number(size.size_id)).filter(Boolean))
  return sizes.value.filter((size) => !selectedIds.has(Number(size.id)))
})

const totalSizeConfigurations = computed(() => form.variants.reduce((total, variant) => total + variant.sizes.length, 0))
const totalStock = computed(() => form.variants.reduce(
  (total, variant) => total + variant.sizes.reduce((variantTotal, size) => variantTotal + Number(size.quantity || 0), 0),
  0,
))

function nextVariantKey() {
  variantSeed += 1
  return `variant-${variantSeed}`
}

function nextSizeKey() {
  sizeSeed += 1
  return `size-${sizeSeed}`
}

function normalizeBoolean(value, fallback = false) {
  if (typeof value === 'boolean') return value
  if (value === null || value === undefined || value === '') return fallback
  return Boolean(Number(value)) || value === 'true'
}

function createSizeRow(partial = {}) {
  return {
    id: partial.id || null,
    key: partial.key || nextSizeKey(),
    size_id: partial.size_id ? Number(partial.size_id) : '',
    price: partial.price ?? form.price ?? '',
    compare_price: partial.compare_price ?? form.compare_price ?? '',
    quantity: Number(partial.quantity ?? 0),
    sku: partial.sku || '',
    barcode: partial.barcode || '',
    is_active: normalizeBoolean(partial.is_active, true),
  }
}

function createVariant(partial = {}) {
  const imagePath = partial.image_path || ''
  return {
    id: partial.id || null,
    key: partial.key || nextVariantKey(),
    color_id: partial.color_id ? Number(partial.color_id) : '',
    is_default: normalizeBoolean(partial.is_default, form.variants.length === 0),
    image_path: imagePath,
    image_preview: imagePath ? resolveMediaUrl(imagePath, 'product') : '',
    image_file: null,
    sizes: (partial.size_variants || partial.sizes || []).map((size) => createSizeRow(size)),
  }
}

function setVariantImageInputRef(key, element) {
  if (element) {
    variantImageInputs.value[key] = element
    return
  }

  delete variantImageInputs.value[key]
}

function triggerMainImagePicker() {
  mainImageInput.value?.click()
}

function triggerVariantImagePicker(key) {
  variantImageInputs.value[key]?.click()
}

function revokePreview(url) {
  if (typeof url === 'string' && url.startsWith('blob:')) {
    URL.revokeObjectURL(url)
  }
}

function handleMainImageUpload(event) {
  const file = event.target.files?.[0]
  if (!file) return

  revokePreview(mainImagePreview.value)
  mainImageFile.value = file
  mainImagePreview.value = URL.createObjectURL(file)
}

function refreshMainImagePreviewFromPath() {
  if (!form.main_image_path) {
    revokePreview(mainImagePreview.value)
    mainImagePreview.value = ''
    return
  }

  if (!mainImageFile.value) {
    revokePreview(mainImagePreview.value)
    mainImagePreview.value = resolveMediaUrl(form.main_image_path, 'product')
  }
}

function removeMainImage() {
  revokePreview(mainImagePreview.value)
  form.main_image_path = ''
  mainImageFile.value = null
  mainImagePreview.value = ''
  if (mainImageInput.value) {
    mainImageInput.value.value = ''
  }
}

function handleVariantImageUpload(key, event) {
  const file = event.target.files?.[0]
  if (!file) return

  const variant = form.variants.find((item) => item.key === key)
  if (!variant) return

  revokePreview(variant.image_preview)
  variant.image_file = file
  variant.image_preview = URL.createObjectURL(file)
}

function refreshVariantPreview(key) {
  const variant = form.variants.find((item) => item.key === key)
  if (!variant) return

  if (!variant.image_file) {
    revokePreview(variant.image_preview)
    variant.image_preview = variant.image_path ? resolveMediaUrl(variant.image_path, 'product') : ''
  }
}

function removeVariantImage(key) {
  const variant = form.variants.find((item) => item.key === key)
  if (!variant) return

  revokePreview(variant.image_preview)
  variant.image_path = ''
  variant.image_file = null
  variant.image_preview = ''

  if (variantImageInputs.value[key]) {
    variantImageInputs.value[key].value = ''
  }
}

function colorName(colorId) {
  const color = colors.value.find((item) => Number(item.id) === Number(colorId))
  return color?.name || ''
}

function colorHex(colorId) {
  const color = colors.value.find((item) => Number(item.id) === Number(colorId))
  return color?.hex_code || ''
}

function sizeName(sizeId) {
  const size = sizes.value.find((item) => Number(item.id) === Number(sizeId))
  return size?.name || ''
}

function currencyLabel(value) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(amount)
}

function setDefaultVariant(key) {
  form.variants.forEach((variant) => {
    variant.is_default = variant.key === key
  })
}

function ensureDefaultVariant() {
  if (!form.variants.length) return
  if (form.variants.some((variant) => variant.is_default)) return
  form.variants[0].is_default = true
}

function addVariant() {
  errors.variants = ''
  form.variants.push(createVariant({
    sizes: [],
    is_default: form.variants.length === 0,
  }))
  ensureDefaultVariant()
  activeTab.value = 'variants'
}

function removeVariant(key) {
  const index = form.variants.findIndex((variant) => variant.key === key)
  if (index === -1) return

  revokePreview(form.variants[index].image_preview)
  form.variants.splice(index, 1)
  ensureDefaultVariant()

  if (activeVariantKey.value === key) {
    closeVariantModal()
  }
}

function openVariantModal(key) {
  activeVariantKey.value = key
  selectedSizeId.value = ''
  variantModalOpen.value = true
}

function closeVariantModal() {
  variantModalOpen.value = false
  activeVariantKey.value = ''
  selectedSizeId.value = ''
}

function addSizeRowToActiveVariant() {
  if (!activeVariant.value) return

  const sizeId = Number(selectedSizeId.value)
  if (!sizeId) {
    showSnackbar({ type: 'error', message: 'Selecciona una talla para agregarla a la variante.' })
    return
  }

  activeVariant.value.sizes.push(createSizeRow({ size_id: sizeId }))
  selectedSizeId.value = ''
}

function removeSizeRow(variantKey, sizeKey) {
  const variant = form.variants.find((item) => item.key === variantKey)
  if (!variant) return

  const index = variant.sizes.findIndex((size) => size.key === sizeKey)
  if (index !== -1) {
    variant.sizes.splice(index, 1)
  }
}

function validateField(field) {
  if (field === 'name') {
    errors.name = form.name.trim().length >= 2 ? '' : 'El nombre es obligatorio y debe tener al menos 2 caracteres.'
  }

  if (field === 'price') {
    errors.price = Number(form.price || 0) > 0 ? '' : 'El precio base debe ser mayor a 0.'
  }

  if (field === 'category_id') {
    errors.category_id = form.category_id ? '' : 'Selecciona una categoria.'
  }

  if (field === 'compare_price') {
    const compare = Number(form.compare_price || 0)
    const price = Number(form.price || 0)
    errors.compare_price = compare && compare <= price
      ? 'El precio comparativo debe ser mayor al precio base.'
      : ''
  }
}

function validateVariants() {
  if (!form.variants.length) {
    errors.variants = 'Debes agregar al menos una variante.'
    return false
  }

  for (const variant of form.variants) {
    if (!variant.color_id) {
      errors.variants = 'Todas las variantes deben tener un color.'
      return false
    }

    if (!variant.sizes.length) {
      errors.variants = 'Cada variante debe tener al menos una talla configurada.'
      return false
    }

    const seenSizes = new Set()
    for (const size of variant.sizes) {
      const sizeId = Number(size.size_id)
      if (!sizeId) {
        errors.variants = 'Cada fila de tallas debe apuntar a una talla valida.'
        return false
      }

      if (seenSizes.has(sizeId)) {
        errors.variants = 'No repitas la misma talla dentro de una variante.'
        return false
      }

      seenSizes.add(sizeId)

      const price = Number(size.price || form.price || 0)
      const compare = Number(size.compare_price || 0)
      if (price <= 0) {
        errors.variants = 'Cada talla debe tener un precio mayor a cero.'
        return false
      }

      if (compare && compare <= price) {
        errors.variants = 'El precio comparativo por talla debe ser mayor al precio de venta.'
        return false
      }
    }
  }

  errors.variants = ''
  return true
}

function validateForm() {
  validateField('name')
  validateField('price')
  validateField('compare_price')
  validateField('category_id')

  const generalValid = !errors.name && !errors.price && !errors.compare_price && !errors.category_id
  const variantsValid = validateVariants()

  if (!generalValid) {
    activeTab.value = 'general'
  } else if (!variantsValid) {
    activeTab.value = 'variants'
  }

  return generalValid && variantsValid
}

function buildVariantPayload() {
  return form.variants.map((variant) => ({
    id: variant.id,
    key: variant.key,
    color_id: Number(variant.color_id),
    is_default: Boolean(variant.is_default),
    image_path: variant.image_path?.trim() || null,
    sizes: variant.sizes.map((size) => ({
      id: size.id,
      key: size.key,
      size_id: Number(size.size_id),
      price: Number(size.price || form.price),
      compare_price: size.compare_price !== '' && size.compare_price !== null
        ? Number(size.compare_price)
        : (form.compare_price !== '' ? Number(form.compare_price) : null),
      quantity: Number(size.quantity || 0),
      sku: size.sku?.trim() || null,
      barcode: size.barcode?.trim() || null,
      is_active: Boolean(size.is_active),
    })),
  }))
}

function buildPayload() {
  return {
    nombre: form.name.trim(),
    slug: form.slug?.trim() || null,
    brand: form.brand?.trim() || null,
    gender: form.gender || 'unisex',
    category_id: Number(form.category_id),
    collection_id: form.collection_id ? Number(form.collection_id) : null,
    collection: form.collection?.trim() || null,
    precio: Number(form.price),
    compare_price: form.compare_price !== '' ? Number(form.compare_price) : null,
    material: form.material?.trim() || null,
    descripcion: form.description?.trim() || null,
    care_instructions: form.care_instructions?.trim() || null,
    main_image_path: form.main_image_path?.trim() || null,
    activo: Boolean(form.is_active),
    is_featured: Boolean(form.is_featured),
    variants: buildVariantPayload(),
  }
}

function buildRequestBody(payload) {
  const hasFiles = Boolean(mainImageFile.value) || form.variants.some((variant) => variant.image_file)

  if (!hasFiles) {
    return { body: payload, config: undefined }
  }

  const formData = new FormData()
  Object.entries(payload).forEach(([key, value]) => {
    if (key === 'variants') {
      formData.append('variants', JSON.stringify(value))
      return
    }

    formData.append(key, value ?? '')
  })

  if (mainImageFile.value) {
    formData.append('main_image_file', mainImageFile.value)
  }

  form.variants.forEach((variant) => {
    if (variant.image_file) {
      formData.append(`variant_image_files[${variant.key}]`, variant.image_file)
    }
  })

  return {
    body: formData,
    config: {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    },
  }
}

function extractErrorMessage(error) {
  return error?.response?.data?.message || error?.response?.data?.error || 'No se pudo guardar el producto.'
}

async function saveProduct() {
  if (!validateForm()) {
    showSnackbar({ type: 'error', message: 'Revisa la informacion del producto antes de guardar.' })
    return
  }

  saving.value = true
  try {
    const payload = buildPayload()
    const { body, config } = buildRequestBody(payload)
    const endpoint = isEditing.value ? `/admin/products/${route.params.id}` : '/admin/products'

    if (isEditing.value) {
      await catalogHttp.put(endpoint, body, config)
    } else {
      await catalogHttp.post(endpoint, body, config)
    }

    showSnackbar({
      type: 'success',
      message: isEditing.value ? 'Producto actualizado correctamente.' : 'Producto creado correctamente.',
    })
    router.push('/admin/productos')
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error) })
  } finally {
    saving.value = false
  }
}

function normalizeRows(response) {
  const data = response.data?.data || response.data || []
  return Array.isArray(data) ? data : (data.data || [])
}

async function loadCatalogOptions() {
  const [categoriesResponse, collectionsResponse, colorsResponse, sizesResponse] = await Promise.all([
    catalogHttp.get('/admin/categories'),
    catalogHttp.get('/admin/collections'),
    catalogHttp.get('/admin/colors'),
    catalogHttp.get('/admin/sizes'),
  ])

  categories.value = normalizeRows(categoriesResponse).map((row) => ({
    ...row,
    id: Number(row.id),
    name: row.name || row.nombre || 'Sin nombre',
  }))

  collections.value = normalizeRows(collectionsResponse).map((row) => ({
    ...row,
    id: Number(row.id),
    name: row.name || row.nombre || 'Sin nombre',
  }))

  colors.value = normalizeRows(colorsResponse).map((row) => ({
    ...row,
    id: Number(row.id),
    name: row.name || row.nombre || 'Sin color',
  }))

  sizes.value = normalizeRows(sizesResponse).map((row) => ({
    ...row,
    id: Number(row.id),
    name: row.name || row.nombre || row.size_label || 'Sin talla',
  }))
}

async function loadProduct() {
  if (!isEditing.value) {
    if (!form.variants.length) {
      addVariant()
    }
    return
  }

  const response = await catalogHttp.get(`/admin/products/${route.params.id}`)
  const data = response.data?.data || {}
  const product = data.product || {}
  const variants = Array.isArray(data.variants) ? data.variants : []
  const productImages = Array.isArray(data.images) ? data.images : []

  form.name = product.name || product.nombre || ''
  form.slug = product.slug || ''
  form.brand = product.brand || product.marca || ''
  form.gender = product.gender || product.genero || 'unisex'
  form.category_id = product.category_id ? Number(product.category_id) : ''
  form.collection_id = product.collection_id ? Number(product.collection_id) : ''
  form.collection = product.collection || product.coleccion || ''
  form.price = Number(product.price ?? product.precio ?? 0) || ''
  form.compare_price = product.compare_price !== null && product.compare_price !== undefined
    ? Number(product.compare_price)
    : ''
  form.material = product.material || ''
  form.description = product.description || product.descripcion || ''
  form.care_instructions = product.care_instructions || product.instrucciones_cuidado || ''
  form.is_featured = normalizeBoolean(product.is_featured ?? product.destacado, false)
  form.is_active = normalizeBoolean(product.is_active ?? product.activo, true)

  const mainImage = productImages.find((image) => !image.color_variant_id && normalizeBoolean(image.is_primary, true))
    || productImages.find((image) => !image.color_variant_id)

  form.main_image_path = mainImage?.image_path || mainImage?.url || product.image || product.imagen || product.image_url || ''
  if (form.main_image_path) {
    mainImagePreview.value = resolveMediaUrl(form.main_image_path, 'product')
  }

  form.variants = variants.map((variant) => {
    const variantImage = (variant.images || []).find((image) => normalizeBoolean(image.is_primary, true))
      || (variant.images || [])[0]
      || productImages.find((image) => Number(image.color_variant_id) === Number(variant.id))

    return createVariant({
      id: variant.id,
      key: `variant-${variant.id || nextVariantKey()}`,
      color_id: variant.color_id,
      is_default: normalizeBoolean(variant.is_default, false),
      image_path: variantImage?.image_path || variantImage?.url || '',
      size_variants: (variant.size_variants || []).map((size) => ({
        id: size.id,
        size_id: size.size_id,
        price: Number(size.price ?? 0),
        compare_price: size.compare_price !== null && size.compare_price !== undefined ? Number(size.compare_price) : '',
        quantity: Number(size.quantity ?? 0),
        sku: size.sku || '',
        barcode: size.barcode || '',
        is_active: normalizeBoolean(size.is_active, true),
      })),
    })
  })

  if (!form.variants.length) {
    addVariant()
  }

  ensureDefaultVariant()
}

function onProductImageError(event, path) {
  handleMediaError(event, path, 'product')
}

onMounted(async () => {
  initialLoading.value = true
  try {
    await loadCatalogOptions()
    await loadProduct()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error) || 'No se pudo cargar el formulario.' })
  } finally {
    initialLoading.value = false
  }
})
</script>

<style scoped>
.product-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.product-form-loading {
  display: flex;
  flex-direction: column;
  gap: 1.6rem;
}

.product-form-tabs {
  margin-bottom: 0;
}

.product-form-panel {
  display: flex;
  flex-direction: column;
  gap: 1.8rem;
}

.product-form-panel-error {
  margin-top: -0.8rem;
}

.product-form-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.9fr);
  gap: 2rem;
  align-items: start;
}

.product-form-main,
.product-form-side {
  min-width: 0;
}

.product-form-side {
  display: flex;
  flex-direction: column;
  gap: 1.6rem;
}

.product-form-section {
  background: #ffffff;
  border: 1px solid rgba(0, 119, 182, 0.12);
  border-radius: 18px;
  padding: 1.8rem;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.product-form-section h3 {
  margin: 0 0 0.6rem;
  font-size: 1.7rem;
  color: var(--admin-text-dark);
}

.image-panel,
.status-panel {
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

.image-panel__header,
.variants-toolbar,
.variant-modal__intro,
.variant-card__header,
.variant-size-summary__header,
.variant-modal__row-top,
.product-form-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.image-panel__header p,
.variants-toolbar p,
.variant-modal__intro p,
.status-option p,
.variant-size-summary__empty,
.variant-card__eyebrow {
  margin: 0;
  color: var(--admin-text-light);
  font-size: 1.25rem;
}

.image-panel__preview,
.variant-card__preview {
  min-height: 220px;
  border-radius: 16px;
  border: 1px dashed rgba(0, 119, 182, 0.28);
  background: #f5f8fc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.variant-card__preview {
  min-height: 180px;
}

.image-panel__preview img,
.variant-card__preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-panel__preview.empty,
.variant-card__preview.empty {
  color: var(--admin-text-light);
  text-align: center;
}

.image-panel__preview i,
.variant-card__preview i,
.variant-modal__empty i {
  font-size: 2.8rem;
  margin-bottom: 0.8rem;
}

.image-panel__actions,
.variant-card__media-actions,
.variant-card__header-actions,
.variant-modal__add-size {
  display: flex;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.status-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.2rem 1.4rem;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(0, 119, 182, 0.1);
}

.variants-toolbar h3,
.variant-modal__intro h3 {
  margin: 0 0 0.4rem;
  font-size: 1.8rem;
}

.variant-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.variant-stat-card,
.variant-card,
.variant-modal__row {
  background: #ffffff;
  border: 1px solid rgba(0, 119, 182, 0.12);
  border-radius: 18px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.variant-stat-card {
  padding: 1.4rem 1.5rem;
}

.variant-stat-card span {
  display: block;
  font-size: 1.2rem;
  color: var(--admin-text-light);
  margin-bottom: 0.4rem;
}

.variant-stat-card strong {
  font-size: 2rem;
  color: var(--admin-text-dark);
}

.variant-list {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.variant-card {
  padding: 1.6rem;
}

.variant-card__header {
  padding-bottom: 1.2rem;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.variant-card__header h3 {
  margin: 0.2rem 0 0;
  font-size: 1.8rem;
}

.variant-default-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 1rem;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.08);
  color: var(--admin-primary);
  font-size: 1.25rem;
  font-weight: 600;
  cursor: pointer;
}

.variant-card__body,
.variant-card__media,
.variant-size-summary,
.variant-modal {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

.variant-size-pills {
  display: flex;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.variant-size-pill {
  display: inline-flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.04);
  color: var(--admin-text-dark);
  font-size: 1.2rem;
}

.variant-size-pill small {
  color: var(--admin-text-light);
}

.variant-modal__rows {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 60vh;
  overflow-y: auto;
  padding-right: 0.2rem;
}

.variant-modal__row {
  padding: 1.4rem;
}

.variant-modal__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.variant-modal__row-footer,
.variant-modal__empty {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.variant-modal__empty {
  min-height: 180px;
  justify-content: center;
  flex-direction: column;
  border-radius: 16px;
  border: 1px dashed rgba(0, 119, 182, 0.24);
  color: var(--admin-text-light);
  text-align: center;
}

.product-form-footer {
  padding-top: 0.6rem;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

@media (max-width: 1100px) {
  .product-form-grid,
  .variant-modal__grid,
  .variant-stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .product-form-section,
  .variant-card,
  .variant-modal__row {
    padding: 1.4rem;
  }

  .image-panel__preview,
  .variant-card__preview {
    min-height: 180px;
  }
}
</style>
