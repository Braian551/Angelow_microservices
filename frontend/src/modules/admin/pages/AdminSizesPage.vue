<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-ruler"
      title="Tallas"
      subtitle="Controla las tallas activas del catalogo, su prioridad y uso real en variantes."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Tallas' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva talla
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <div class="filters-bar entity-filters">
      <div class="filter-group entity-filters__search">
        <label for="size-search">Buscar</label>
        <input id="size-search" v-model="search" type="text" placeholder="Nombre o descripcion">
      </div>
      <div class="filter-group">
        <label for="size-status">Estado</label>
        <select id="size-status" v-model="statusFilter">
          <option value="">Todas</option>
          <option value="active">Activas</option>
          <option value="inactive">Inactivas</option>
        </select>
      </div>
      <div class="entity-filters__summary">
        <span><i class="fas fa-list"></i> {{ filteredSizes.length }} resultado(s)</span>
      </div>
    </div>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredSizes.length === 0"
        icon="fas fa-ruler"
        title="Sin tallas visibles"
        description="Crea una talla nueva o ajusta los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Descripcion</th>
              <th>Orden</th>
              <th>Uso en variantes</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="size in filteredSizes" :key="size.id">
              <td>
                <div class="entity-name-cell">
                  <strong>{{ size.name }}</strong>
                  <span>ID #{{ size.id }}</span>
                </div>
              </td>
              <td><p class="entity-description">{{ excerpt(size.description, 120) }}</p></td>
              <td>{{ size.sort_order ?? '-' }}</td>
              <td><span class="entity-count-pill">{{ size.product_count }} variante(s)</span></td>
              <td>
                <span class="status-badge" :class="size.is_active ? 'active' : 'inactive'">
                  {{ size.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="entity-actions">
                  <button class="action-btn edit" type="button" @click="openModal(size)"><i class="fas fa-edit"></i></button>
                  <button class="action-btn view" type="button" @click="toggleStatus(size)"><i class="fas fa-power-off"></i></button>
                  <button class="action-btn delete" type="button" :disabled="size.product_count > 0" @click="confirmDelete(size)"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showModal" :title="editing ? 'Editar talla' : 'Nueva talla'" max-width="720px" @close="closeModal">
      <div class="entity-form-grid">
        <div class="form-group">
          <label for="size-name">Nombre *</label>
          <input id="size-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="validateField('name')">
          <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
        </div>

        <div class="form-group">
          <label for="size-order">Orden</label>
          <input id="size-order" v-model="form.sort_order" type="number" min="0" class="form-control" @input="validateField('sort_order')">
          <p v-if="errors.sort_order" class="form-error">{{ errors.sort_order }}</p>
        </div>

        <div class="form-group entity-form-grid__full">
          <label for="size-description">Descripcion</label>
          <textarea id="size-description" v-model="form.description" class="form-control" rows="4"></textarea>
        </div>

        <div class="form-group entity-form-grid__full entity-form-toggle">
          <div>
            <strong>Talla activa</strong>
            <p>Las tallas inactivas se conservan para historico, pero se excluyen del uso operativo.</p>
          </div>
          <label class="toggle-switch">
            <input v-model="form.is_active" type="checkbox">
            <span class="toggle-slider"></span>
          </label>
        </div>
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
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

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
  if (!text) return 'Sin descripcion'
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
.entity-filters {
  justify-content: space-between;
}

.entity-filters__search {
  flex: 1 1 340px;
}

.entity-filters__search input {
  width: 100%;
}

.entity-filters__summary {
  margin-left: auto;
  color: var(--admin-text-light);
  font-size: 1.3rem;
}

.entity-name-cell,
.entity-actions {
  display: flex;
  gap: 0.6rem;
}

.entity-name-cell {
  flex-direction: column;
  align-items: flex-start;
}

.entity-name-cell span,
.entity-description {
  color: var(--admin-text-light);
  font-size: 1.25rem;
}

.entity-description {
  margin: 0;
}

.entity-count-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.08);
  color: var(--admin-primary);
  font-size: 1.2rem;
  font-weight: 600;
}

.entity-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.2rem;
}

.entity-form-grid__full {
  grid-column: 1 / -1;
}

.entity-form-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.2rem 1.4rem;
  border: 1px solid var(--admin-border);
  border-radius: 12px;
}

.entity-form-toggle p {
  margin: 0.3rem 0 0;
  color: var(--admin-text-light);
  font-size: 1.2rem;
}

@media (max-width: 768px) {
  .entity-form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
