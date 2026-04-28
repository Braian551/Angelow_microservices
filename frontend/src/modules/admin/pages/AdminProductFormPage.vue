<template>
  <div>
    <AdminPageHeader
      :icon="isEditing ? 'fas fa-edit' : 'fas fa-plus-circle'"
      :title="isEditing ? 'Editar producto' : 'Nuevo producto'"
      :subtitle="isEditing ? 'Actualiza la ficha, variantes y precios del producto.' : 'Crea un producto completo con información, variantes e inventario.'"
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
            <i class="fas fa-file-alt"></i>
            Información general
          </button>
          <button type="button" class="admin-tab" :class="{ active: activeTab === 'variants' }" @click="activeTab = 'variants'">
            <i class="fas fa-swatchbook"></i>
            Variantes y precios
            <span v-if="form.variants.length" class="tab-badge">{{ form.variants.length }}</span>
          </button>
        </div>

        <section v-show="activeTab === 'general'" class="product-form-panel">
          <div class="product-form-grid">
            <div class="product-form-main">
              <div class="product-form-section">
                <div class="section-header">
                  <div class="section-header__icon"><i class="fas fa-tag"></i></div>
                  <div>
                    <h3>Identificación</h3>
                    <p>Nombre público y URL del producto.</p>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="product-name">
                      Nombre del producto *
                      <AdminInfoTooltip text="Nombre público del producto visible en la tienda y el catálogo. Sé descriptivo y claro." />
                    </label>
                    <input id="product-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="validateField('name')">
                    <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
                  </div>
                  <div class="form-group">
                    <label for="product-slug">
                      Slug
                      <AdminInfoTooltip text="Identificador único del producto en la URL. Se genera automáticamente desde el nombre o puedes editarlo manualmente." />
                    </label>
                    <input id="product-slug" v-model="form.slug" class="form-control" placeholder="se-genera-automaticamente" @input="handleSlugInput">
                  </div>
                </div>
              </div>

              <div class="product-form-section">
                <div class="section-header">
                  <div class="section-header__icon"><i class="fas fa-layer-group"></i></div>
                  <div>
                    <h3>Clasificación</h3>
                    <p>Marca, género, categoría y colección.</p>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="product-brand">
                      Marca
                      <AdminInfoTooltip text="Marca o fabricante del producto. Aparece en la ficha del catálogo." />
                    </label>
                    <input id="product-brand" v-model="form.brand" class="form-control" placeholder="Angelow">
                  </div>
                  <div class="form-group">
                    <label for="product-gender">
                      Género
                      <AdminInfoTooltip text="Público objetivo del producto. Permite filtrar y clasificar en el catálogo." />
                    </label>
                    <select id="product-gender" v-model="form.gender" class="form-control">
                      <option value="unisex">Unisex</option>
                      <option value="mujer">Mujer</option>
                      <option value="hombre">Hombre</option>
                      <option value="nina">Niña</option>
                      <option value="nino">Niño</option>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="product-category">
                      Categoría *
                      <AdminInfoTooltip text="Categoría principal a la que pertenece el producto. Es obligatoria para guardarlo." />
                    </label>
                    <select id="product-category" v-model="form.category_id" class="form-control" :class="{ 'is-invalid': errors.category_id }" @change="validateField('category_id')">
                      <option value="">Seleccionar...</option>
                      <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <p v-if="errors.category_id" class="form-error">{{ errors.category_id }}</p>
                  </div>
                  <div class="form-group">
                    <label for="product-collection-id">
                      Colección
                      <AdminInfoTooltip text="Colección o temporada a la que pertenece el producto. Opcional." />
                    </label>
                    <select id="product-collection-id" v-model="form.collection_id" class="form-control">
                      <option value="">Sin colección</option>
                      <option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="product-form-section">
                <div class="section-header">
                  <div class="section-header__icon"><i class="fas fa-dollar-sign"></i></div>
                  <div>
                    <h3>Precios y detalles</h3>
                    <p>Precio base, comparativo, material y etiqueta de colección.</p>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="product-price">
                      Precio base *
                      <AdminInfoTooltip text="Precio de venta principal del producto en pesos. Las variantes pueden tener su propio precio." />
                    </label>
                    <input id="product-price" v-model="form.price" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': errors.price }" @input="validateField('price')">
                    <p v-if="errors.price" class="form-error">{{ errors.price }}</p>
                  </div>
                  <div class="form-group">
                    <label for="product-compare-price">
                      Precio comparativo
                      <AdminInfoTooltip text="Precio original o tachado que muestra el descuento al cliente. Deja vacío si no aplica." />
                    </label>
                    <input id="product-compare-price" v-model="form.compare_price" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': errors.compare_price }" @input="validateField('compare_price')">
                    <p v-if="errors.compare_price" class="form-error">{{ errors.compare_price }}</p>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="product-material">
                      Material
                      <AdminInfoTooltip text="Composición textil o material principal del producto. Visible en la ficha del catálogo." />
                    </label>
                    <input id="product-material" v-model="form.material" class="form-control" placeholder="Algodón, denim, cuero...">
                  </div>
                  <div class="form-group">
                    <label for="product-collection-name">
                      Etiqueta de colección
                      <AdminInfoTooltip text="Texto libre de etiqueta para el producto. Ejemplo: «Drop verano 2026». Complementa el nombre de la colección." />
                    </label>
                    <input id="product-collection-name" v-model="form.collection" class="form-control" placeholder="Drop verano 2026">
                  </div>
                </div>
              </div>

              <div class="product-form-section">
                <div class="section-header">
                  <div class="section-header__icon"><i class="fas fa-align-left"></i></div>
                  <div>
                    <h3>Descripción y cuidado</h3>
                    <p>Texto de catálogo e instrucciones para el cliente.</p>
                  </div>
                </div>
                <div class="form-group">
                  <label for="product-description">
                    Descripción
                    <AdminInfoTooltip text="Texto de venta visible al cliente en la ficha del producto. Describe materiales, silueta, fit y atributos clave." />
                  </label>
                  <textarea id="product-description" v-model="form.description" class="form-control" rows="5" placeholder="Cuenta materiales, silueta, fit y atributos clave."></textarea>
                </div>
                <div class="form-group">
                  <label for="product-care">
                    Instrucciones de cuidado
                    <AdminInfoTooltip text="Indicaciones de lavado, secado y mantenimiento para el cliente. Visibles en la ficha del producto." />
                  </label>
                  <textarea id="product-care" v-model="form.care_instructions" class="form-control" rows="4" placeholder="Lavado, secado y recomendaciones de mantenimiento."></textarea>
                </div>
              </div>
            </div>

            <aside class="product-form-side">
              <div class="product-form-section image-panel">
                <div class="section-header">
                  <div class="section-header__icon"><i class="fas fa-camera"></i></div>
                  <div>
                    <h3>Imagen principal</h3>
                    <p>Sube o conserva una ruta existente.</p>
                  </div>
                  <button type="button" class="btn btn-secondary btn-sm section-header__action" title="Seleccionar imagen" @click="triggerMainImagePicker">
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
                <div class="section-header">
                  <div class="section-header__icon"><i class="fas fa-eye"></i></div>
                  <div>
                    <h3>Visibilidad</h3>
                    <p>Estado y destaque del producto.</p>
                  </div>
                </div>
                <AdminToggleSwitch
                  id="product-active"
                  class="status-option"
                  v-model="form.is_active"
                  title="Producto activo"
                  description="Visible para catálogo y procesos internos."
                />

                <AdminToggleSwitch
                  id="product-featured"
                  class="status-option"
                  v-model="form.is_featured"
                  title="Producto destacado"
                  description="Permite resaltarlo en vitrinas o listados especiales."
                />
              </div>
            </aside>
          </div>
        </section>

        <section v-show="activeTab === 'variants'" class="product-form-panel">
          <div class="variants-toolbar">
            <div>
              <h3>Variantes configuradas</h3>
              <p>Replica el flujo de Angelow: color, imagen y tallas con precio, stock, SKU y código de barras.</p>
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

                  <button type="button" class="btn btn-primary btn-sm" title="Tallas y precios" @click="openVariantModal(variant.key)">
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
                  <label :for="`variant-color-${variant.key}`">
                    Color *
                    <AdminInfoTooltip text="Color de esta variante. Cada variante corresponde a un color distinto del producto." />
                  </label>
                  <select :id="`variant-color-${variant.key}`" v-model="variant.color_id" class="form-control">
                    <option value="">Seleccionar color...</option>
                    <option v-for="color in colors" :key="color.id" :value="color.id">{{ color.name }}</option>
                  </select>
                </div>

                <div class="variant-card__media">
                  <div class="variant-image-grid">
                    <div
                      v-for="(img, imgIndex) in variant.images"
                      :key="imgIndex"
                      class="variant-image-thumb"
                      :class="{ 'is-primary': img.is_primary }"
                    >
                      <img :src="img.preview" :alt="`Imagen ${imgIndex + 1}`" @error="onProductImageError($event, img.path)">
                      <div class="variant-image-thumb__overlay">
                        <button type="button" class="vit-action vit-action--star" :title="img.is_primary ? 'Principal' : 'Marcar como principal'" @click="setVariantImagePrimary(variant.key, imgIndex)">
                          <i class="fas fa-star"></i>
                        </button>
                        <button type="button" class="vit-action vit-action--remove" title="Quitar imagen" @click="removeVariantImageItem(variant.key, imgIndex)">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                      <span v-if="img.is_primary" class="vit-primary-badge">Principal</span>
                    </div>

                    <button type="button" class="variant-image-add-btn" title="Agregar imágenes" @click="triggerVariantImagePicker(variant.key)">
                      <i class="fas fa-plus"></i>
                      <span v-if="!variant.images.length">Agregar imágenes</span>
                    </button>
                  </div>
                  <input :ref="(element) => setVariantImageInputRef(variant.key, element)" type="file" accept="image/*" multiple class="visually-hidden" @change="(event) => handleVariantImageUpload(variant.key, event)">
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

                  <p v-else class="variant-size-summary__empty">Abre el modal para cargar precios, stock y códigos.</p>
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

    <AdminModal :show="variantModalOpen" title="Configuración de tallas y precios" icon="fas fa-tags" subtitle="Color, tallas, precios e inventario de la variante." max-width="980px" @close="closeVariantModal">
      <div v-if="activeVariant" class="variant-modal">
        <div class="variant-modal__intro">
          <div>
            <h3>{{ colorName(activeVariant.color_id) || 'Variante sin color' }}</h3>
            <p>Define tallas, precios comparativos, stock, SKU y código de barras.</p>
            
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
                <label>
                  Talla *
                  <AdminInfoTooltip text="Talla a la que corresponde este precio e inventario." />
                </label>
                <select v-model="sizeRow.size_id" class="form-control">
                  <option value="">Seleccionar...</option>
                  <option v-for="size in sizes" :key="size.id" :value="size.id">{{ size.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>
                  Precio *
                  <AdminInfoTooltip text="Precio de venta de esta combinación color + talla. Valor en pesos." />
                </label>
                <input v-model="sizeRow.price" type="number" step="0.01" min="0" class="form-control">
              </div>
              <div class="form-group">
                <label>
                  Precio comparativo
                  <AdminInfoTooltip text="Precio original o tachado que muestra el descuento al cliente. Deja vacío si no hay precio anterior." />
                </label>
                <input v-model="sizeRow.compare_price" type="number" step="0.01" min="0" class="form-control">
              </div>
              <div class="form-group">
                <label>
                  Stock
                  <AdminInfoTooltip text="Unidades disponibles en inventario para esta variante." />
                </label>
                <input v-model="sizeRow.quantity" type="number" step="1" min="0" class="form-control">
              </div>
              <div class="form-group">
                <label>
                  SKU
                  <AdminInfoTooltip text="Código único interno de la variante. Se genera automáticamente al completar los datos o puedes editarlo." />
                </label>
                <input v-model="sizeRow.sku" class="form-control" placeholder="ANG-PIJA-GRL-DULC-ROS-XS" @input="handleSizeSkuInput(activeVariant, sizeRow)">
              </div>
              <div class="form-group">
                <label>
                  Código de barras
                  <AdminInfoTooltip text="Código de barras del producto para uso en escaneos físicos o etiquetado." />
                </label>
                <input v-model="sizeRow.barcode" class="form-control" placeholder="7700000000000">
              </div>
            </div>

            <div class="variant-modal__row-footer">
              <AdminToggleSwitch
                :id="`variant-size-active-${sizeRow.key}`"
                v-model="sizeRow.is_active"
                layout="inline"
                :label="sizeRow.is_active ? 'Talla activa' : 'Talla inactiva'"
              />
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminShimmer from '../components/AdminShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

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
const slugManuallyEdited = ref(false)

let variantSeed = 0
let sizeSeed = 0

const SPANISH_STOPWORDS = new Set(['a', 'al', 'con', 'de', 'del', 'el', 'en', 'la', 'las', 'los', 'para', 'por', 'sin', 'un', 'una', 'y'])
const GENDER_SLUG_LABELS = {
  unisex: 'unisex',
  mujer: 'mujer',
  hombre: 'hombre',
  nina: 'nina',
  nino: 'nino',
}
const GENDER_SKU_CODES = {
  unisex: 'UNI',
  mujer: 'WMN',
  hombre: 'MEN',
  nina: 'GRL',
  nino: 'BOY',
}

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

function normalizePlainText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

function slugifyText(value) {
  return normalizePlainText(value)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-+|-+$/g, '')
}

function buildWordSignature(value) {
  const normalized = slugifyText(value).replace(/-/g, '')

  if (normalized.length > 4 && normalized.endsWith('es')) {
    return normalized.slice(0, -2)
  }

  if (normalized.length > 3 && normalized.endsWith('s')) {
    return normalized.slice(0, -1)
  }

  return normalized
}

function extractMeaningfulWords(value) {
  return slugifyText(value)
    .split('-')
    .filter(Boolean)
    .filter((word) => !SPANISH_STOPWORDS.has(word))
}

function uniqueMeaningfulWords(sources) {
  const seen = new Set()
  const words = []

  for (const source of sources) {
    for (const word of extractMeaningfulWords(source)) {
      const signature = buildWordSignature(word)
      if (!signature || seen.has(signature)) {
        continue
      }

      seen.add(signature)
      words.push(word)
    }
  }

  return words
}

function normalizeGenderKey(value) {
  const normalized = slugifyText(value).replace(/-/g, '')
  return GENDER_SLUG_LABELS[normalized] ? normalized : 'unisex'
}

function genderSlugLabel(value) {
  return GENDER_SLUG_LABELS[normalizeGenderKey(value)] || 'unisex'
}

function genderSkuCode(value) {
  return GENDER_SKU_CODES[normalizeGenderKey(value)] || 'UNI'
}

function categoryOption(categoryId) {
  return categories.value.find((item) => Number(item.id) === Number(categoryId)) || null
}

function categorySource(categoryId) {
  const category = categoryOption(categoryId)
  return category?.slug || category?.name || ''
}

function buildCodeSegment(value, length, fallback) {
  const words = extractMeaningfulWords(value)

  if (!words.length) {
    return fallback
  }

  if (words.length === 1) {
    const singleWord = words[0].replace(/[^a-z0-9]/g, '')
    if (!singleWord) {
      return fallback
    }

    return singleWord.substring(0, length).toUpperCase().padEnd(length, singleWord.charAt(0).toUpperCase())
  }

  const compact = words.map((word) => word.slice(0, 2)).join('').replace(/[^a-z0-9]/g, '')
  if (!compact) {
    return fallback
  }

  return compact.substring(0, length).toUpperCase().padEnd(length, compact.charAt(0).toUpperCase())
}

function buildBrandSkuToken() {
  return buildCodeSegment(form.brand || 'Angelow', 3, 'ANG')
}

function buildCategorySkuToken() {
  return buildCodeSegment(categorySource(form.category_id), 4, 'CATG')
}

function buildStyleSource() {
  const excludedSignatures = new Set([
    ...extractMeaningfulWords(form.brand),
    ...extractMeaningfulWords(categorySource(form.category_id)),
    ...extractMeaningfulWords(genderSlugLabel(form.gender)),
  ].map((word) => buildWordSignature(word)))

  const styleWords = extractMeaningfulWords(form.name)
    .filter((word) => !excludedSignatures.has(buildWordSignature(word)))

  return styleWords.join(' ') || form.name
}

function buildStyleSkuToken() {
  return buildCodeSegment(buildStyleSource(), 4, 'MODE')
}

function buildColorSkuToken(colorId) {
  return buildCodeSegment(colorName(colorId), 3, 'COL')
}

function buildSizeSkuToken(sizeId) {
  const normalizedSize = normalizePlainText(sizeName(sizeId))
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, '')

  if (!normalizedSize) {
    return 'TAL'
  }

  return normalizedSize.substring(0, 4)
}

function buildGeneratedSku(variant, sizeRow) {
  if (!String(form.name || '').trim() || !form.category_id || !variant.color_id || !sizeRow.size_id) {
    return ''
  }

  return [
    buildBrandSkuToken(),
    buildCategorySkuToken(),
    genderSkuCode(form.gender),
    buildStyleSkuToken(),
    buildColorSkuToken(variant.color_id),
    buildSizeSkuToken(sizeRow.size_id),
  ].join('-')
}

function normalizeSkuValue(value) {
  return normalizePlainText(value)
    .toUpperCase()
    .replace(/[^A-Z0-9-]+/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-+|-+$/g, '')
}

function shouldTreatExistingSkuAsManual(value) {
  const normalizedSku = normalizeSkuValue(value)
  return Boolean(normalizedSku) && normalizedSku !== '-' && normalizedSku.length > 2
}

function shouldTreatExistingSlugAsManual(value) {
  const normalizedSlug = slugifyText(value)
  if (!normalizedSlug) {
    return false
  }

  const basicNameSlug = slugifyText(form.name)
  return normalizedSlug !== generatedSlug.value && normalizedSlug !== basicNameSlug
}

const generatedSlug = computed(() => {
  if (!String(form.name || '').trim()) {
    return ''
  }

  const words = uniqueMeaningfulWords([
    categorySource(form.category_id),
    form.name,
    genderSlugLabel(form.gender),
  ])

  return words.length ? words.join('-') : slugifyText(form.name)
})

const skuGenerationSignature = computed(() => JSON.stringify({
  name: form.name,
  brand: form.brand,
  category_id: form.category_id,
  category_source: categorySource(form.category_id),
  gender: form.gender,
  colors: colors.value.map((color) => `${color.id}:${color.name}`),
  sizes: sizes.value.map((size) => `${size.id}:${size.name}`),
  variants: form.variants.map((variant) => ({
    key: variant.key,
    color_id: variant.color_id,
    sizes: variant.sizes.map((size) => ({
      key: size.key,
      size_id: size.size_id,
      manual: Boolean(size.sku_manually_edited),
    })),
  })),
}))

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
    sku_manually_edited: partial.sku_manually_edited ?? shouldTreatExistingSkuAsManual(partial.sku),
    barcode: partial.barcode || '',
    is_active: normalizeBoolean(partial.is_active, true),
  }
}

function createVariant(partial = {}) {
  // Construir array de imágenes desde partial.images[] o partial.image_path (retrocompat)
  const images = []

  if (Array.isArray(partial.images) && partial.images.length > 0) {
    for (const img of partial.images) {
      const path = img.image_path || img.url || img.path || ''
      images.push({
        id: img.id || null,
        path,
        preview: path ? resolveMediaUrl(path, 'product') : '',
        file: null,
        is_primary: normalizeBoolean(img.is_primary, images.length === 0),
      })
    }
  } else if (partial.image_path) {
    const path = partial.image_path
    images.push({ id: null, path, preview: resolveMediaUrl(path, 'product'), file: null, is_primary: true })
  }

  return {
    id: partial.id || null,
    key: partial.key || nextVariantKey(),
    color_id: partial.color_id ? Number(partial.color_id) : '',
    is_default: normalizeBoolean(partial.is_default, form.variants.length === 0),
    images,
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
  const files = Array.from(event.target.files || [])
  if (!files.length) return

  const variant = form.variants.find((item) => item.key === key)
  if (!variant) return

  // Agregar todas las imágenes seleccionadas al array de la variante
  for (const file of files) {
    variant.images.push({
      id: null,
      path: '',
      preview: URL.createObjectURL(file),
      file,
      is_primary: variant.images.length === 0,
    })
  }

  // Resetear input para permitir re-selección de los mismos archivos
  if (variantImageInputs.value[key]) {
    variantImageInputs.value[key].value = ''
  }
}

function removeVariantImageItem(variantKey, imgIndex) {
  const variant = form.variants.find((item) => item.key === variantKey)
  if (!variant) return

  const [removed] = variant.images.splice(imgIndex, 1)
  revokePreview(removed?.preview)

  // Reasignar principal si la imagen eliminada era la principal
  if (removed?.is_primary && variant.images.length > 0) {
    variant.images[0].is_primary = true
  }
}

function setVariantImagePrimary(variantKey, imgIndex) {
  const variant = form.variants.find((item) => item.key === variantKey)
  if (!variant) return

  variant.images.forEach((img, idx) => {
    img.is_primary = idx === imgIndex
  })
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
  syncVariantSkus()
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
    errors.category_id = form.category_id ? '' : 'Selecciona una categoría.'
  }

  if (field === 'compare_price') {
    const compare = Number(form.compare_price || 0)
    const price = Number(form.price || 0)
    errors.compare_price = compare && compare <= price
      ? 'El precio comparativo debe ser mayor al precio base.'
      : ''
  }
}

function syncSlugValue() {
  if (!slugManuallyEdited.value || !String(form.slug || '').trim()) {
    form.slug = generatedSlug.value
  }
}

function syncVariantSkus() {
  form.variants.forEach((variant) => {
    variant.sizes.forEach((sizeRow) => {
      if (sizeRow.sku_manually_edited && String(sizeRow.sku || '').trim()) {
        return
      }

      sizeRow.sku = buildGeneratedSku(variant, sizeRow)
    })
  })
}

function handleSlugInput() {
  const normalizedSlug = slugifyText(form.slug)

  if (!normalizedSlug) {
    slugManuallyEdited.value = false
    form.slug = generatedSlug.value
    return
  }

  slugManuallyEdited.value = normalizedSlug !== generatedSlug.value
  form.slug = normalizedSlug
}

function handleSizeSkuInput(variant, sizeRow) {
  const normalizedSku = normalizeSkuValue(sizeRow.sku)

  if (!normalizedSku) {
    sizeRow.sku_manually_edited = false
    sizeRow.sku = buildGeneratedSku(variant, sizeRow)
    return
  }

  const generatedSku = buildGeneratedSku(variant, sizeRow)
  sizeRow.sku_manually_edited = normalizedSku !== generatedSku
  sizeRow.sku = normalizedSku
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
    // Imágenes ya persistidas (sin archivo nuevo) — el backend las re-inserta
    images: variant.images
      .filter((img) => img.path && !img.file)
      .map((img, order) => ({
        id: img.id,
        path: img.path,
        is_primary: img.is_primary,
        order,
      })),
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
  const hasFiles = Boolean(mainImageFile.value) || form.variants.some((variant) => variant.images.some((img) => img.file))

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
    // Adjuntar nuevas imágenes de la variante con índice para soporte de múltiples archivos
    variant.images.forEach((img, imgIndex) => {
      if (img.file) {
        formData.append(`variant_image_files[${variant.key}][${imgIndex}]`, img.file)
      }
    })
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
    showSnackbar({ type: 'error', message: 'Revisa la información del producto antes de guardar.' })
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

  slugManuallyEdited.value = true
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
    // Usar todas las imágenes de la variante; fallback a product_images si no hay
    const variantImgList = (variant.images || []).length > 0
      ? variant.images
      : productImages.filter((img) => Number(img.color_variant_id) === Number(variant.id))

    return createVariant({
      id: variant.id,
      key: `variant-${variant.id || nextVariantKey()}`,
      color_id: variant.color_id,
      is_default: normalizeBoolean(variant.is_default, false),
      images: variantImgList.map((img) => ({
        id: img.id || null,
        image_path: img.image_path || img.url || '',
        is_primary: normalizeBoolean(img.is_primary, false),
      })),
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

  slugManuallyEdited.value = shouldTreatExistingSlugAsManual(form.slug)
  syncSlugValue()
  ensureDefaultVariant()
  syncVariantSkus()
}

function onProductImageError(event, path) {
  handleMediaError(event, path, 'product')
}

watch(generatedSlug, () => {
  syncSlugValue()
}, { immediate: true })

watch(skuGenerationSignature, () => {
  syncVariantSkus()
}, { immediate: true })

onMounted(async () => {
  initialLoading.value = true
  try {
    await loadCatalogOptions()
    await loadProduct()
    // Siempre abrir en pestaña general al entrar al formulario
    activeTab.value = 'general'
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

.product-form-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

.product-form-side {
  min-width: 0;
}

.product-form-side {
  display: flex;
  flex-direction: column;
  gap: 1.6rem;
}

.product-form-section {
  background: var(--admin-bg, #ffffff);
  border: 1px solid var(--admin-border-light, rgba(0, 119, 182, 0.12));
  border-radius: var(--admin-card-radius, 18px);
  padding: 1.8rem;
  box-shadow: var(--admin-shadow-card, 0 2px 12px rgba(15, 23, 42, 0.06));
}

.product-form-section h3 {
  margin: 0 0 0.6rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--admin-text-dark);
}

.section-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.6rem;
  padding-bottom: 1.2rem;
  border-bottom: 1px solid var(--admin-border-light, rgba(0, 119, 182, 0.1));
}

.section-header__icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(0, 119, 182, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--admin-primary);
  font-size: 1.3rem;
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.section-header h3 {
  margin: 0 0 0.2rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--admin-text-dark);
}

.section-header p {
  margin: 0;
  font-size: 1.2rem;
  color: var(--admin-text-light);
}

.section-header__action {
  margin-left: auto;
  flex-shrink: 0;
}

.tab-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.15);
  color: var(--admin-primary);
  font-size: 1.1rem;
  font-weight: 700;
  line-height: 1;
  margin-left: 0.4rem;
}

.admin-tab.active .tab-badge {
  background: rgba(255, 255, 255, 0.25);
  color: inherit;
}

.image-panel,
.status-panel {
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

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

.variants-toolbar p,
.variant-modal__intro p,
.status-option p,
.variant-size-summary__empty,
.variant-card__eyebrow {
  margin: 0;
  color: var(--admin-text-light);
  font-size: 1.25rem;
}

.image-panel__preview {
  min-height: 220px;
  border-radius: 16px;
  border: 1px dashed rgba(0, 119, 182, 0.28);
  background: #f5f8fc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.image-panel__preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-panel__preview.empty {
  color: var(--admin-text-light);
  text-align: center;
}

.image-panel__preview i,
.variant-modal__empty i {
  font-size: 2.8rem;
  margin-bottom: 0.8rem;
}

.image-panel__actions,
.variant-card__header-actions {
  display: flex;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.variant-modal__add-size {
  display: flex;
  gap: 0.8rem;
  align-items: center;
  flex-wrap: nowrap;
}

.variant-modal__add-size .form-control {
  flex: 1;
  min-width: 0;
}

.variant-modal__add-size .btn {
  flex-shrink: 0;
}

/* ── Galería compacta de imágenes por variante ── */
.variant-image-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  align-items: flex-start;
}

.variant-image-thumb {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid transparent;
  flex-shrink: 0;
  transition: border-color 0.18s ease;
}

.variant-image-thumb.is-primary {
  border-color: var(--admin-primary);
}

.variant-image-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.variant-image-thumb__overlay {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.52);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  opacity: 0;
  transition: opacity 0.18s ease;
}

.variant-image-thumb:hover .variant-image-thumb__overlay {
  opacity: 1;
}

.vit-action {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  padding: 0;
  line-height: 1;
}

.vit-action--star {
  background: rgba(255, 193, 7, 0.92);
  color: #4a3a00;
}

.vit-action--remove {
  background: rgba(220, 53, 69, 0.92);
  color: white;
}

.vit-primary-badge {
  position: absolute;
  bottom: 3px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--admin-primary);
  color: white;
  font-size: 0.9rem;
  padding: 1px 5px;
  border-radius: 4px;
  white-space: nowrap;
  pointer-events: none;
  line-height: 1.4;
}

.variant-image-add-btn {
  width: 80px;
  height: 80px;
  border-radius: 10px;
  border: 2px dashed rgba(0, 119, 182, 0.35);
  background: rgba(0, 119, 182, 0.04);
  color: var(--admin-primary);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  cursor: pointer;
  font-size: 1.6rem;
  flex-shrink: 0;
  transition: background 0.18s ease, border-color 0.18s ease;
}

.variant-image-add-btn span {
  font-size: 1.1rem;
  font-weight: 500;
}

.variant-image-add-btn:hover {
  background: rgba(0, 119, 182, 0.1);
  border-color: rgba(0, 119, 182, 0.55);
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
