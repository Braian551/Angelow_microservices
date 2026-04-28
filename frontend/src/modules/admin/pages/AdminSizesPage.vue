<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-ruler"
      title="Tallas"
      subtitle="Controla las tallas activas del catálogo, su prioridad y uso real en variantes."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Tallas' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva talla
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y estado"
      placeholder="Nombre o descripción"
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="size-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="size-status" v-model="statusFilter">
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} tallas`" />

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredSizes.length === 0"
        icon="fas fa-ruler"
        title="Sin tallas visibles"
        description="Crea una talla nueva o ajusta los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table dashboard-table--sizes">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Orden</th>
              <th>Uso en variantes</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="size in pagination.paginatedItems" :key="size.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ size.name }}</strong>
                  <span>ID #{{ size.id }}</span>
                </div>
              </td>
              <td><p class="admin-entity-name__description">{{ excerpt(size.description, 120) }}</p></td>
              <td>{{ size.sort_order ?? '-' }}</td>
              <td><span class="admin-entity-filters__pill">{{ size.product_count }} variante(s)</span></td>
              <td>
                <span class="status-badge" :class="size.is_active ? 'active' : 'inactive'">
                  {{ size.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" @click="openModal(size)"><i class="fas fa-edit"></i></button>
                  <button class="action-btn view" type="button" @click="toggleStatus(size)"><i class="fas fa-power-off"></i></button>
                  <button v-if="size.product_count === 0" class="action-btn delete" type="button" title="Eliminar" @click="confirmDelete(size)"><i class="fas fa-trash"></i></button>
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

    <AdminModal :show="showModal" :title="editing ? 'Editar talla' : 'Nueva talla'" max-width="720px" @close="closeModal">
      <div class="admin-entity-form">
        <div class="form-group">
          <label for="size-name">
            Nombre *
            <AdminInfoTooltip text="Nombre corto de la talla tal como aparece al cliente. Ejemplos: XS, S, M, L, XL, XXL, 3XL." />
          </label>
          <input id="size-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" placeholder="Ej. XS, S, M, L, XL" @input="validateField('name')">
          <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
        </div>

        <div class="form-group">
          <label for="size-order">
            Posición en el listado
            <AdminInfoTooltip text="Número que define en qué orden aparece esta talla en los selectores del formulario. Las tallas con número menor aparecen primero. Ejemplo: XS=1, S=2, M=3, L=4." />
          </label>
          <input id="size-order" v-model="form.sort_order" type="number" min="0" class="form-control" placeholder="Ej. 1" @input="validateField('sort_order')">
          <p v-if="errors.sort_order" class="form-error">{{ errors.sort_order }}</p>
        </div>

        <div class="form-group admin-entity-form__full">
          <label for="size-description">
            Descripción
            <AdminInfoTooltip text="Nota interna sobre esta talla (rango de medidas, equivalencias, etc.). No es visible al cliente en la tienda." />
          </label>
          <textarea id="size-description" v-model="form.description" class="form-control" rows="3" placeholder="Opcional: rango de medidas, equivalencias internacionales, etc."></textarea>
        </div>

        <AdminToggleSwitch
          id="size-active"
          class="admin-entity-form__full"
          v-model="form.is_active"
          title="Talla activa"
          description="Si está activa, estará disponible para asignarla a variantes de productos. Si la desactivas, se conserva para historial."
        />
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveSize">{{ editing ? 'Actualizar talla' : 'Crear talla' }}</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const sizes = ref([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const showModal = ref(false)
const editing = ref(null)

const form = reactive({
  name: '',
  description: '',
  sort_order: '',
  is_active: true,
})

const errors = reactive({
  name: '',
  sort_order: '',
})

const filteredSizes = computed(() => {
  const term = search.value.trim().toLowerCase()

  return sizes.value.filter((size) => {
    const matchesSearch = !term || [size.name, size.description]
      .some((value) => String(value || '').toLowerCase().includes(term))

    const matchesStatus = !statusFilter.value
      || (statusFilter.value === 'active' && size.is_active)
      || (statusFilter.value === 'inactive' && !size.is_active)

    return matchesSearch && matchesStatus
  })
})

const pagination = useAdminPagination(filteredSizes, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value].filter(Boolean).length)

const stats = computed(() => {
  const total = sizes.value.length
  const active = sizes.value.filter((size) => size.is_active).length
  const inactive = total - active
  const linkedVariants = sizes.value.reduce((sum, size) => sum + Number(size.product_count || 0), 0)

  return [
    { key: 'total', label: 'Tallas totales', value: String(total), icon: 'fas fa-ruler-combined', color: 'primary' },
    { key: 'active', label: 'Tallas activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
    { key: 'inactive', label: 'Tallas inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
    { key: 'variants', label: 'Variantes asociadas', value: String(linkedVariants), icon: 'fas fa-boxes', color: 'info' },
  ]
})

function normalizeSize(rawSize) {
  return {
    ...rawSize,
    id: Number(rawSize.id),
    name: rawSize.name || rawSize.nombre || rawSize.size_label || 'Sin nombre',
    description: rawSize.description || rawSize.descripcion || '',
    sort_order: rawSize.sort_order ?? rawSize.order_position ?? rawSize.orden ?? null,
    product_count: Number(rawSize.product_count || 0),
    is_active: typeof rawSize.is_active === 'boolean' ? rawSize.is_active : Boolean(Number(rawSize.activo ?? 1)),
  }
}

function excerpt(value, max = 100) {
  const text = String(value || '').trim()
  if (!text) return 'Sin descripción'
  return text.length > max ? `${text.slice(0, max).trim()}...` : text
}

function validateField(field) {
  if (field === 'name') {
    errors.name = form.name.trim().length >= 1 ? '' : 'El nombre es obligatorio.'
  }

  if (field === 'sort_order') {
    errors.sort_order = form.sort_order === '' || Number(form.sort_order) >= 0
      ? ''
      : 'El orden no puede ser negativo.'
  }
}

function clearFilters() {
  search.value = ''
  statusFilter.value = ''
}

function resetForm() {
  form.name = ''
  form.description = ''
  form.sort_order = ''
  form.is_active = true
  errors.name = ''
  errors.sort_order = ''
}

function openModal(size = null) {
  editing.value = size
  resetForm()

  if (size) {
    form.name = size.name
    form.description = size.description
    form.sort_order = size.sort_order ?? ''
    form.is_active = size.is_active
  }

  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editing.value = null
}

async function loadSizes() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/sizes', { params: { include_inactive: true } })
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    sizes.value = rows.map(normalizeSize)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando tallas' })
  } finally {
    loading.value = false
  }
}

async function saveSize() {
  validateField('name')
  validateField('sort_order')

  if (errors.name || errors.sort_order) return

  const payload = {
    name: form.name.trim(),
    description: form.description?.trim() || null,
    sort_order: form.sort_order === '' ? null : Number(form.sort_order),
    is_active: form.is_active,
  }

  try {
    if (editing.value?.id) {
      await catalogHttp.put(`/admin/sizes/${editing.value.id}`, payload)
      showSnackbar({ type: 'success', message: 'Talla actualizada' })
    } else {
      await catalogHttp.post('/admin/sizes', payload)
      showSnackbar({ type: 'success', message: 'Talla creada' })
    }

    closeModal()
    await loadSizes()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error guardando talla' })
  }
}

function confirmDelete(size) {
  showAlert({
    type: 'warning',
    title: 'Eliminar talla',
    message: size.product_count > 0
      ? `La talla ${size.name} ya esta asociada a variantes y no se puede eliminar.`
      : `¿Deseas eliminar la talla ${size.name}?`,
    actions: size.product_count > 0
      ? [{ text: 'Entendido', style: 'primary' }]
      : [
          { text: 'Cancelar', style: 'secondary' },
          {
            text: 'Eliminar',
            style: 'danger',
            callback: async () => {
              try {
                await catalogHttp.delete(`/admin/sizes/${size.id}`)
                showSnackbar({ type: 'success', message: 'Talla eliminada' })
                await loadSizes()
              } catch (error) {
                showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error eliminando talla' })
              }
            },
          },
        ],
  })
}

async function toggleStatus(size) {
  try {
    await catalogHttp.put(`/admin/sizes/${size.id}`, {
      name: size.name,
      description: size.description || null,
      sort_order: size.sort_order,
      is_active: !size.is_active,
    })
    showSnackbar({ type: 'success', message: !size.is_active ? 'Talla activada' : 'Talla desactivada' })
    await loadSizes()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error actualizando estado' })
  }
}

onMounted(loadSizes)
</script>

<style scoped>
/* Estilos específicos de Tallas — los comunes están en admin.css */

@media (max-width: 768px) {
  .admin-entity-filters__form {
    grid-template-columns: 1fr;
  }
}
</style>
