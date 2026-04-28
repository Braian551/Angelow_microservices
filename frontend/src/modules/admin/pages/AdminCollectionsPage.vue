<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-layer-group"
      title="Colecciones"
      subtitle="Organiza temporadas y lanzamientos manteniendo la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Colecciones' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva colección
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y estado"
      placeholder="Nombre, descripción o fecha"
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="collection-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="collection-status" v-model="statusFilter">
              <option value="">Todas</option>
              <option value="active">Activas</option>
              <option value="inactive">Inactivas</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} colecciones`" />

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCollections.length === 0"
        icon="fas fa-layer-group"
        title="Sin colecciones visibles"
        description="Crea una nueva colección o ajusta los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Fecha</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="collection in pagination.paginatedItems" :key="collection.id">
              <td>
                <AdminTableImage :src="resolveCollectionImage(collection)" :alt="collection.name" :original-path="collection.image" fallback-type="collection" />
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ collection.name }}</strong>
                  <span>{{ collection.slug || 'Sin slug' }}</span>
                </div>
              </td>
              <td><p class="admin-entity-name__description">{{ excerpt(collection.description, 120) }}</p></td>
              <td>{{ formatDate(collection.launch_date) }}</td>
              <td><span class="admin-entity-filters__pill">{{ collection.product_count }} producto(s)</span></td>
              <td>
                <span class="status-badge" :class="collection.is_active ? 'active' : 'inactive'">
                  {{ collection.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" @click="openModal(collection)"><i class="fas fa-edit"></i></button>
                  <button class="action-btn view" type="button" @click="toggleStatus(collection)"><i class="fas fa-power-off"></i></button>
                  <button v-if="collection.product_count === 0" class="action-btn delete" type="button" title="Eliminar" @click="confirmDelete(collection)"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

    <AdminModal
      :show="showModal"
      :icon="editing ? 'fas fa-edit' : 'fas fa-plus'"
      :title="editing ? 'Editar colección' : 'Nueva colección'"
      :subtitle="editing ? 'Actualiza los datos de esta colección.' : 'Completa los datos para crear una nueva colección.'"
      max-width="860px"
      @close="closeModal"
    >
      <div class="admin-editor-grid">
        <!-- Columna izquierda: datos principales -->
        <div>
          <div class="form-group">
            <label for="collection-name">
              Nombre *
              <AdminInfoTooltip text="Nombre de la colección o temporada. Por ejemplo: «Verano 2025», «Colección Básicos» o «Edición especial»." />
            </label>
            <input
              id="collection-name"
              v-model="form.name"
              class="form-control"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Ej. Verano 2025, Línea Básicos"
              @input="onNameInput"
            >
            <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
          </div>

          <div class="form-group">
            <label for="collection-slug">
              Identificador (slug)
              <AdminInfoTooltip text="Clave única que identifica la colección en las URLs. Se genera automáticamente al escribir el nombre. Solo letras minúsculas, números y guiones." />
            </label>
            <input
              id="collection-slug"
              v-model="form.slug"
              class="form-control"
              placeholder="se-genera-automaticamente"
              @input="onSlugInput"
            >
            <p v-if="form.slug" class="admin-field-hint">URL: <code>/tienda/coleccion/{{ form.slug }}</code></p>
          </div>

          <div class="form-group">
            <label for="collection-date">
              Fecha de lanzamiento
              <AdminInfoTooltip text="Fecha en que la colección fue o será presentada. Es informativa y no activa ni desactiva la colección de forma automática." />
            </label>
            <input id="collection-date" v-model="form.launch_date" type="date" class="form-control">
          </div>

          <div class="form-group">
            <label for="collection-description">
              Descripción
              <AdminInfoTooltip text="Descripción interna con el concepto y alcance de la colección. No es visible al cliente en la tienda." />
            </label>
            <textarea
              id="collection-description"
              v-model="form.description"
              class="form-control"
              rows="4"
              placeholder="Describe el concepto, temporada o inspiración de esta colección."
            ></textarea>
          </div>

          <AdminToggleSwitch
            id="collection-active"
            v-model="form.is_active"
            title="Colección activa"
            description="Si está activa, puede asociarse a productos y participa en los flujos promocionales del catálogo."
          />
        </div>

        <!-- Columna derecha: imagen -->
        <div>
          <div class="form-group">
            <label>
              Imagen de portada
              <AdminInfoTooltip text="Imagen principal que representa visualmente la colección en la tienda y el catálogo. Formatos: JPG, PNG o WEBP. Máximo 4 MB." />
            </label>
            <div class="admin-upload-box" @click="openImagePicker">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>{{ imagePreviewUrl ? 'Cambiar imagen' : 'Selecciona la imagen de portada' }}</p>
              <small>JPG, PNG o WEBP · Máximo 4 MB</small>
            </div>
            <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
            <p v-if="errors.image" class="form-error">{{ errors.image }}</p>
            <div v-if="imagePreviewUrl" class="admin-image-preview">
              <img :src="imagePreviewUrl" alt="Vista previa de la imagen de colección">
              <div class="admin-image-preview__actions">
                <button class="btn btn-secondary btn-sm" type="button" title="Quitar imagen" @click="clearSelectedImage">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveCollection">
          <i :class="editing ? 'fas fa-save' : 'fas fa-plus'"></i>
          {{ editing ? 'Guardar cambios' : 'Crear colección' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminTableImage from '../components/AdminTableImage.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const collections = ref([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const showModal = ref(false)
const editing = ref(null)

// Referencias para carga de imagen
const imageInputRef = ref(null)
const selectedImageFile = ref(null)
const imagePreviewUrl = ref('')
const slugManuallyEdited = ref(false)

const form = reactive({
  name: '',
  slug: '',
  description: '',
  launch_date: '',
  is_active: true,
})

const errors = reactive({ name: '', image: '' })

// Genera slug a partir de texto (normaliza, quita acentos, convierte a kebab-case)
function slugifyText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/[\s_]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
}

function onNameInput() {
  validateField('name')
  if (!slugManuallyEdited.value) {
    form.slug = slugifyText(form.name)
  }
}

function onSlugInput() {
  slugManuallyEdited.value = form.slug.trim() !== ''
  form.slug = slugifyText(form.slug)
}

const filteredCollections = computed(() => {
  const term = search.value.trim().toLowerCase()
  return collections.value.filter((collection) => {
    const matchesSearch = !term || [collection.name, collection.slug, collection.description, formatDate(collection.launch_date)]
      .some((value) => String(value || '').toLowerCase().includes(term))

    const matchesStatus = !statusFilter.value
      || (statusFilter.value === 'active' && collection.is_active)
      || (statusFilter.value === 'inactive' && !collection.is_active)

    return matchesSearch && matchesStatus
  })
})

const pagination = useAdminPagination(filteredCollections, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value].filter(Boolean).length)

const stats = computed(() => {
  const total = collections.value.length
  const active = collections.value.filter((collection) => collection.is_active).length
  const inactive = total - active
  const linkedProducts = collections.value.reduce((sum, collection) => sum + Number(collection.product_count || 0), 0)

  return [
    { key: 'total', label: 'Colecciones totales', value: String(total), icon: 'fas fa-layer-group', color: 'primary' },
    { key: 'active', label: 'Colecciones activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
    { key: 'inactive', label: 'Colecciones inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
    { key: 'products', label: 'Productos asociados', value: String(linkedProducts), icon: 'fas fa-box-open', color: 'info' },
  ]
})

function normalizeCollection(row) {
  return {
    ...row,
    id: Number(row.id),
    name: row.name || row.nombre || 'Sin nombre',
    slug: row.slug || '',
    description: row.description || row.descripcion || '',
    image: row.image || row.imagen || null,
    launch_date: row.launch_date || '',
    product_count: Number(row.product_count || 0),
    is_active: typeof row.is_active === 'boolean' ? row.is_active : Boolean(Number(row.activo ?? 1)),
  }
}

function resolveCollectionImage(collection) {
  return resolveMediaUrl(collection.image, 'collection')
}

function onCollectionImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'collection')
}

function excerpt(value, max = 100) {
  const text = String(value || '').trim()
  if (!text) return 'Sin descripción'
  return text.length > max ? `${text.slice(0, max).trim()}...` : text
}

function formatDate(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleDateString('es-CO')
}

function validateField(field) {
  if (field === 'name') {
    errors.name = form.name.trim().length >= 2 ? '' : 'El nombre es obligatorio y debe tener al menos 2 caracteres.'
  }
}

function clearFilters() {
  search.value = ''
  statusFilter.value = ''
}

function openImagePicker() {
  imageInputRef.value?.click()
}

function onImageSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return
  selectedImageFile.value = file
  imagePreviewUrl.value = URL.createObjectURL(file)
  errors.image = ''
}

function clearSelectedImage(resetInput = true) {
  if (imagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviewUrl.value)
  }
  imagePreviewUrl.value = ''
  selectedImageFile.value = null
  if (resetInput && imageInputRef.value) {
    imageInputRef.value.value = ''
  }
}

function resetForm() {
  form.name = ''
  form.slug = ''
  form.description = ''
  form.launch_date = ''
  form.is_active = true
  errors.name = ''
  errors.image = ''
  slugManuallyEdited.value = false
  clearSelectedImage(false)
}

function openModal(collection = null) {
  editing.value = collection
  resetForm()

  if (collection) {
    form.name = collection.name
    form.description = collection.description
    form.launch_date = collection.launch_date || ''
    form.is_active = collection.is_active

    // Slug: si existe uno ya guardado, tratar como editado manualmente
    if (collection.slug) {
      form.slug = collection.slug
      slugManuallyEdited.value = true
    }

    // Pre-cargar vista previa si la colección ya tiene imagen
    if (collection.image) {
      imagePreviewUrl.value = resolveCollectionImage(collection)
    }
  }

  showModal.value = true
}

function closeModal() {
  clearSelectedImage()
  showModal.value = false
  editing.value = null
}

async function loadCollections() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/collections')
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    collections.value = rows.map(normalizeCollection)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando colecciones' })
  } finally {
    loading.value = false
  }
}

async function saveCollection() {
  validateField('name')
  if (errors.name) return

  const payload = new FormData()
  payload.append('nombre', form.name.trim())
  payload.append('slug', form.slug?.trim() || '')
  payload.append('descripcion', form.description?.trim() || '')
  payload.append('activo', form.is_active ? '1' : '0')
  if (form.launch_date) {
    payload.append('launch_date', form.launch_date)
  }
  if (selectedImageFile.value) {
    payload.append('image_file', selectedImageFile.value)
  }

  const headers = { 'Content-Type': 'multipart/form-data' }

  try {
    if (editing.value?.id) {
      await catalogHttp.put(`/admin/collections/${editing.value.id}`, payload, { headers })
      showSnackbar({ type: 'success', message: 'Colección actualizada' })
    } else {
      await catalogHttp.post('/admin/collections', payload, { headers })
      showSnackbar({ type: 'success', message: 'Colección creada' })
    }

    closeModal()
    await loadCollections()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error guardando colección' })
  }
}

function confirmDelete(collection) {
  showAlert({
    type: 'warning',
    title: 'Eliminar colección',
    message: collection.product_count > 0
      ? `La colección ${collection.name} tiene productos asociados y no se puede eliminar.`
      : `¿Deseas eliminar la colección ${collection.name}?`,
    actions: collection.product_count > 0
      ? [{ text: 'Entendido', style: 'primary' }]
      : [
          { text: 'Cancelar', style: 'secondary' },
          {
            text: 'Eliminar',
            style: 'danger',
            callback: async () => {
              try {
                await catalogHttp.delete(`/admin/collections/${collection.id}`)
                showSnackbar({ type: 'success', message: 'Colección eliminada' })
                await loadCollections()
              } catch (error) {
                showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error eliminando colección' })
              }
            },
          },
        ],
  })
}

async function toggleStatus(collection) {
  try {
    await catalogHttp.put(`/admin/collections/${collection.id}`, {
      nombre: collection.name,
      slug: collection.slug || null,
      descripcion: collection.description || null,
      launch_date: collection.launch_date || null,
      activo: !collection.is_active,
    })
    showSnackbar({ type: 'success', message: !collection.is_active ? 'Colección activada' : 'Colección desactivada' })
    await loadCollections()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error actualizando estado' })
  }
}

onMounted(loadCollections)
</script>

<style scoped>
/* Estilos específicos de Colecciones — los comunes están en admin.css */

@media (max-width: 768px) {
  .admin-editor-grid {
    grid-template-columns: 1fr;
  }

  .admin-entity-filters__form {
    grid-template-columns: 1fr;
  }
}
</style>
