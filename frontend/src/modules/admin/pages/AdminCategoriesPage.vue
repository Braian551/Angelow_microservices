<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-folder-open"
      title="Categorias"
      subtitle="Gestiona estructura, estado y contenido descriptivo de las categorias del catalogo."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Categorias' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva categoria
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Busqueda y estado"
      placeholder="Nombre, slug o descripcion"
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} categorias`" />

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCategories.length === 0"
        icon="fas fa-folder-open"
        title="Sin categorias visibles"
        description="Ajusta los filtros o crea una nueva categoria para empezar."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Descripcion</th>
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
                  <button class="action-btn delete" type="button" title="Eliminar" :disabled="category.product_count > 0" @click="confirmDelete(category)">
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

    <AdminModal :show="showModal" :title="editing ? 'Editar categoria' : 'Nueva categoria'" max-width="720px" @close="closeModal">
      <div class="admin-entity-filters__form">
        <div class="form-group admin-entity-filters__form--full">
          <label for="category-name">Nombre *</label>
          <input id="category-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="validateField('name')">
          <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
        </div>

        <div class="form-group">
          <label for="category-slug">Slug</label>
          <input id="category-slug" v-model="form.slug" class="form-control" placeholder="se-genera-automaticamente">
        </div>

        <div class="form-group">
          <label for="category-image">Ruta de imagen</label>
          <input id="category-image" v-model="form.image" class="form-control" placeholder="/uploads/categories/mi-categoria.jpg">
        </div>

        <div class="form-group admin-entity-filters__form--full">
          <label for="category-description">Descripcion</label>
          <textarea id="category-description" v-model="form.description" class="form-control" rows="4" placeholder="Describe la categoria y su alcance comercial."></textarea>
        </div>

        <div class="form-group admin-entity-filters__form--full admin-entity-filters__toggle">
          <div>
            <strong>Categoria activa</strong>
            <p>Permite que siga disponible para asociarla a productos.</p>
          </div>
          <label class="toggle-switch">
            <input v-model="form.is_active" type="checkbox">
            <span class="toggle-slider"></span>
          </label>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveCategory">
          {{ editing ? 'Actualizar categoria' : 'Crear categoria' }}
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
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminTableImage from '../components/AdminTableImage.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const categories = ref([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const showModal = ref(false)
const editing = ref(null)

const form = reactive({
  name: '',
  slug: '',
  description: '',
  image: '',
  is_active: true,
})

const errors = reactive({
  name: '',
})

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
    { key: 'total', label: 'Categorias totales', value: String(total), icon: 'fas fa-tags', color: 'primary' },
    { key: 'active', label: 'Categorias activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
    { key: 'inactive', label: 'Categorias inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
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
  if (!text) return 'Sin descripcion'
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

function resetForm() {
  form.name = ''
  form.slug = ''
  form.description = ''
  form.image = ''
  form.is_active = true
  errors.name = ''
}

function openModal(category = null) {
  editing.value = category
  resetForm()

  if (category) {
    form.name = category.name
    form.slug = category.slug
    form.description = category.description
    form.image = category.image || ''
    form.is_active = category.is_active
  }

  showModal.value = true
}

function closeModal() {
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
    showSnackbar({ type: 'error', message: 'Error cargando categorias' })
  } finally {
    loading.value = false
  }
}

async function saveCategory() {
  validateField('name')
  if (errors.name) return

  const payload = {
    nombre: form.name.trim(),
    slug: form.slug?.trim() || null,
    descripcion: form.description?.trim() || null,
    imagen: form.image?.trim() || null,
    activo: form.is_active,
  }

  try {
    if (editing.value?.id) {
      await catalogHttp.put(`/admin/categories/${editing.value.id}`, payload)
      showSnackbar({ type: 'success', message: 'Categoria actualizada' })
    } else {
      await catalogHttp.post('/admin/categories', payload)
      showSnackbar({ type: 'success', message: 'Categoria creada' })
    }

    closeModal()
    await loadCategories()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error guardando categoria' })
  }
}

function confirmDelete(category) {
  showAlert({
    type: 'warning',
    title: 'Eliminar categoria',
    message: category.product_count > 0
      ? `La categoria ${category.name} tiene productos asociados y no se puede eliminar.`
      : `¿Deseas eliminar la categoria ${category.name}?`,
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
                showSnackbar({ type: 'success', message: 'Categoria eliminada' })
                await loadCategories()
              } catch (error) {
                showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error eliminando categoria' })
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
      imagen: category.image || null,
      activo: !category.is_active,
    })
    showSnackbar({
      type: 'success',
      message: !category.is_active ? 'Categoria activada' : 'Categoria desactivada',
    })
    await loadCategories()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error actualizando estado' })
  }
}

onMounted(loadCategories)
</script>

<style scoped>
/* Estilos responsivos propios de categorias */
@media (max-width: 768px) {
  .admin-entity-actions {
    flex-wrap: wrap;
  }
}
</style>
