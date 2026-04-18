<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-folder-open"
      title="Categorías"
      subtitle="Gestiona estructura, estado y contenido descriptivo de las categorías del catálogo."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Categorías' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva categoría
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y estado"
      placeholder="Nombre, slug o descripción"
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="category-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="category-status" v-model="statusFilter">
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} categorías`" />

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCategories.length === 0"
        icon="fas fa-folder-open"
        title="Sin categorías visibles"
        description="Ajusta los filtros o crea una nueva categoría para empezar."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in pagination.paginatedItems" :key="category.id">
              <td>
                <AdminTableImage :src="resolveCategoryImage(category)" :alt="category.name" :original-path="category.image" fallback-type="category" />
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ category.name }}</strong>
                  <span>{{ category.slug || 'Sin slug' }}</span>
                </div>
              </td>
              <td>
                <p class="admin-entity-name__description">{{ excerpt(category.description, 120) }}</p>
              </td>
              <td>
                <span class="admin-entity-filters__pill">{{ category.product_count }} producto(s)</span>
              </td>
              <td>
                <span class="status-badge" :class="category.is_active ? 'active' : 'inactive'">
                  {{ category.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" title="Editar" @click="openModal(category)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn view" type="button" :title="category.is_active ? 'Desactivar' : 'Activar'" @click="toggleStatus(category)">
                    <i class="fas fa-power-off"></i>
                  </button>
                  <button v-if="category.product_count === 0" class="action-btn delete" type="button" title="Eliminar" @click="confirmDelete(category)">
                    <i class="fas fa-trash"></i>
                  </button>
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
      :title="editing ? 'Editar categoría' : 'Nueva categoría'"
      :subtitle="editing ? 'Actualiza los datos de esta categoría.' : 'Completa los datos para crear una nueva categoría.'"
      max-width="860px"
      @close="closeModal"
    >
      <div class="admin-editor-grid">
        <!-- Columna izquierda: datos principales -->
        <div>
          <div class="form-group">
            <label for="category-name">
              Nombre *
              <AdminInfoTooltip text="Nombre visible de la categoría en la tienda y en el panel de administración. Debe ser claro y reconocible para el equipo." />
            </label>
            <input
              id="category-name"
              v-model="form.name"
              class="form-control"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Ej. Vestidos, Pijamas, Ropa Deportiva"
              @input="onNameInput"
            >
            <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
          </div>

          <div class="form-group">
            <label for="category-slug">
              Identificador (slug)
              <AdminInfoTooltip text="Clave única que identifica la categoría en las URLs. Se genera automáticamente al escribir el nombre. Solo letras minúsculas, números y guiones." />
            </label>
            <input
              id="category-slug"
              v-model="form.slug"
              class="form-control"
              placeholder="se-genera-automaticamente"
              @input="onSlugInput"
            >
            <p v-if="form.slug" class="admin-field-hint">URL: <code>/tienda/categoria/{{ form.slug }}</code></p>
          </div>

          <div class="form-group">
            <label for="category-description">
              Descripción
              <AdminInfoTooltip text="Descripción interna del alcance y contenido de la categoría. No es visible al cliente en la tienda." />
            </label>
            <textarea
              id="category-description"
              v-model="form.description"
              class="form-control"
              rows="4"
              placeholder="Describe el tipo de prendas que incluye esta categoría."
            ></textarea>
          </div>

          <AdminToggleSwitch
            id="category-active"
            v-model="form.is_active"
            title="Categoría activa"
            description="Si está activa, puede asociarse a productos y aparece disponible en los filtros del catálogo."
          />
        </div>

        <!-- Columna derecha: imagen -->
        <div>
          <div class="form-group">
            <label>
              Imagen de la categoría
              <AdminInfoTooltip text="Imagen representativa que se muestra en la tienda y el catálogo. Formatos admitidos: JPG, PNG o WEBP. Máximo 4 MB." />
            </label>
            <div class="admin-upload-box" @click="openImagePicker">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>{{ imagePreviewUrl ? 'Cambiar imagen' : 'Selecciona la imagen de la categoría' }}</p>
              <small>JPG, PNG o WEBP · Máximo 4 MB</small>
            </div>
            <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
            <p v-if="errors.image" class="form-error">{{ errors.image }}</p>
            <div v-if="imagePreviewUrl" class="admin-image-preview">
              <img :src="imagePreviewUrl" alt="Vista previa de la imagen de categoría">
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
        <button class="btn btn-primary" type="button" @click="saveCategory">
          <i :class="editing ? 'fas fa-save' : 'fas fa-plus'"></i>
          {{ editing ? 'Guardar cambios' : 'Crear categoría' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
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

const categories = ref([])
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
  is_active: true,
})

const errors = reactive({
  name: '',
  image: '',
})

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

const filteredCategories = computed(() => {
  const term = search.value.trim().toLowerCase()

  return categories.value.filter((category) => {
    const matchesSearch = !term || [category.name, category.slug, category.description]
      .some((value) => String(value || '').toLowerCase().includes(term))

    const matchesStatus = !statusFilter.value
      || (statusFilter.value === 'active' && category.is_active)
      || (statusFilter.value === 'inactive' && !category.is_active)

    return matchesSearch && matchesStatus
  })
})

const pagination = useAdminPagination(filteredCategories, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value].filter(Boolean).length)

const stats = computed(() => {
  const total = categories.value.length
  const active = categories.value.filter((category) => category.is_active).length
  const inactive = total - active
  const linkedProducts = categories.value.reduce((sum, category) => sum + Number(category.product_count || 0), 0)

  return [
    { key: 'total', label: 'Categorías totales', value: String(total), icon: 'fas fa-tags', color: 'primary' },
    { key: 'active', label: 'Categorías activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
    { key: 'inactive', label: 'Categorías inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
    { key: 'products', label: 'Productos asociados', value: String(linkedProducts), icon: 'fas fa-box-open', color: 'info' },
  ]
})

function normalizeCategory(item) {
  return {
    ...item,
    id: Number(item.id),
    name: item.name || item.nombre || 'Sin nombre',
    slug: item.slug || '',
    description: item.description || item.descripcion || '',
    image: item.image || item.imagen || null,
    product_count: Number(item.product_count || 0),
    is_active: typeof item.is_active === 'boolean' ? item.is_active : Boolean(Number(item.activo ?? 1)),
  }
}

function resolveCategoryImage(category) {
  return resolveMediaUrl(category.image, 'category')
}

function onCategoryImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'category')
}

function excerpt(value, max = 100) {
  const text = String(value || '').trim()
  if (!text) return 'Sin descripción'
  return text.length > max ? `${text.slice(0, max).trim()}...` : text
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
  form.is_active = true
  errors.name = ''
  errors.image = ''
  slugManuallyEdited.value = false
  clearSelectedImage(false)
}

function openModal(category = null) {
  editing.value = category
  resetForm()

  if (category) {
    form.name = category.name
    form.description = category.description
    form.is_active = category.is_active

    // Slug: si existe uno ya guardado, tratar como editado manualmente
    if (category.slug) {
      form.slug = category.slug
      slugManuallyEdited.value = true
    }

    // Pre-cargar vista previa si la categoría ya tiene imagen
    if (category.image) {
      imagePreviewUrl.value = resolveCategoryImage(category)
    }
  }

  showModal.value = true
}

function closeModal() {
  clearSelectedImage()
  showModal.value = false
  editing.value = null
}

async function loadCategories() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/categories')
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    categories.value = rows.map(normalizeCategory)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando categorías' })
  } finally {
    loading.value = false
  }
}

async function saveCategory() {
  validateField('name')
  if (errors.name) return

  const payload = new FormData()
  payload.append('nombre', form.name.trim())
  payload.append('slug', form.slug?.trim() || '')
  payload.append('descripcion', form.description?.trim() || '')
  payload.append('activo', form.is_active ? '1' : '0')

  if (selectedImageFile.value) {
    payload.append('image_file', selectedImageFile.value)
  }

  const headers = { 'Content-Type': 'multipart/form-data' }

  try {
    if (editing.value?.id) {
      await catalogHttp.put(`/admin/categories/${editing.value.id}`, payload, { headers })
      showSnackbar({ type: 'success', message: 'Categoría actualizada' })
    } else {
      await catalogHttp.post('/admin/categories', payload, { headers })
      showSnackbar({ type: 'success', message: 'Categoría creada' })
    }

    closeModal()
    await loadCategories()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error guardando categoría' })
  }
}

function confirmDelete(category) {
  showAlert({
    type: 'warning',
    title: 'Eliminar categoría',
    message: category.product_count > 0
      ? `La categoría ${category.name} tiene productos asociados y no se puede eliminar.`
      : `¿Deseas eliminar la categoría ${category.name}?`,
    actions: category.product_count > 0
      ? [{ text: 'Entendido', style: 'primary' }]
      : [
          { text: 'Cancelar', style: 'secondary' },
          {
            text: 'Eliminar',
            style: 'danger',
            callback: async () => {
              try {
                await catalogHttp.delete(`/admin/categories/${category.id}`)
                showSnackbar({ type: 'success', message: 'Categoría eliminada' })
                await loadCategories()
              } catch (error) {
                showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error eliminando categoría' })
              }
            },
          },
        ],
  })
}

async function toggleStatus(category) {
  try {
    await catalogHttp.put(`/admin/categories/${category.id}`, {
      nombre: category.name,
      slug: category.slug || null,
      descripcion: category.description || null,
      activo: !category.is_active,
    })
    showSnackbar({
      type: 'success',
      message: !category.is_active ? 'Categoría activada' : 'Categoría desactivada',
    })
    await loadCategories()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error actualizando estado' })
  }
}

onMounted(loadCategories)
</script>

<style scoped>
/* Estilos responsivos propios de categorías */
@media (max-width: 768px) {
  .admin-editor-grid {
    grid-template-columns: 1fr;
  }

  .admin-entity-actions {
    flex-wrap: wrap;
  }
}
</style>
