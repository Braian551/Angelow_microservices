<template>
  <div class="admin-shipping-methods-page">
    <AdminPageHeader
      icon="fas fa-truck"
      title="Definir envios"
      subtitle="Gestiona metodos de envio con modales, detalle operativo y validacion consistente."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Definir envios' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="exportMethods">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo metodo
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="shippingStats" />

    <!-- Filtros de metodos de envio -->
    <AdminFilterCard
      icon="fas fa-filter"
      title="Busqueda y cobertura"
      placeholder="Buscar por nombre, ciudad o descripcion..."
      :modelValue="filters.search"
      @update:modelValue="filters.search = $event"
      @search="loadMethods"
    >
      <template #advanced>
        <div class="filters-row filters-row--shipping-methods">
          <div class="filter-group">
            <label for="shipping-method-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="shipping-method-status" v-model="filters.state" class="form-control">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
              <option value="free-threshold">Con envio gratis</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="shipping-method-city"><i class="fas fa-map-marker-alt"></i> Cobertura</label>
            <select id="shipping-method-city" v-model="filters.city" class="form-control">
              <option value="all">Todas</option>
              <option value="national">Sin ciudad fija</option>
              <option v-for="city in availableCities" :key="city" :value="city">{{ city }}</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__count">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <button type="button" class="admin-filters__clear" @click="clearFilters">
            <i class="fas fa-times-circle"></i>
            Limpiar filtros
          </button>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} metodos`" />

    <AdminCard title="Bandeja de metodos" icon="fas fa-truck" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredMethods.length === 0"
        icon="fas fa-truck"
        title="Sin metodos de envio"
        description="No hay metodos configurados o ninguno coincide con los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table shipping-methods-table">
          <thead>
            <tr>
              <th>Metodo</th>
              <th>Cobertura</th>
              <th>Tiempo</th>
              <th>Costo base</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="method in pagination.paginatedItems" :key="method.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ method.name }}</strong>
                  <span>{{ method.description || 'Sin descripcion operativa' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ method.city || 'Cobertura general' }}</strong>
                  <span>{{ method.free_shipping_minimum ? `Gratis desde ${formatCurrency(method.free_shipping_minimum)}` : 'Sin umbral gratis' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ deliveryWindowLabel(method) }}</strong>
                  <span>{{ method.delivery_time || 'Sin promesa adicional' }}</span>
                </div>
              </td>
              <td><strong>{{ formatCurrency(method.base_cost) }}</strong></td>
              <td>
                <span class="status-badge" :class="method.active ? 'active' : 'rejected'">
                  {{ method.active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(method)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Editar metodo" @click="openEditModal(method)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar metodo" @click="confirmDeleteMethod(method)">
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

    <AdminModal :show="showDetailModal" :title="selectedMethod ? selectedMethod.name : 'Detalle del metodo'" max-width="940px" @close="closeDetailModal">
      <template v-if="selectedMethod">
        <div class="shipping-detail-grid admin-detail-grid">
          <AdminCard title="Resumen operativo" icon="fas fa-shipping-fast">
            <div class="admin-detail-summary">
              <div class="admin-detail-summary__row"><span>Descripcion</span><strong>{{ selectedMethod.description || 'Sin descripcion' }}</strong></div>
              <div class="admin-detail-summary__row"><span>Costo base</span><strong>{{ formatCurrency(selectedMethod.base_cost) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Promesa</span><strong>{{ deliveryWindowLabel(selectedMethod) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Tiempo libre</span><strong>{{ selectedMethod.delivery_time || 'No definido' }}</strong></div>
              <div class="admin-detail-summary__row"><span>Ciudad</span><strong>{{ selectedMethod.city || 'Cobertura general' }}</strong></div>
              <div class="admin-detail-summary__row"><span>Envio gratis desde</span><strong>{{ selectedMethod.free_shipping_minimum ? formatCurrency(selectedMethod.free_shipping_minimum) : 'No aplica' }}</strong></div>
            </div>
          </AdminCard>

          <AdminCard title="Estado comercial" icon="fas fa-clipboard-check">
            <div class="shipping-method-hero admin-surface-card">
              <p class="shipping-method-hero__label admin-surface-card__label">Metodo</p>
              <h3>{{ selectedMethod.name }}</h3>
              <p>{{ selectedMethod.city || 'Disponible sin restriccion de ciudad' }}</p>
              <span class="status-badge" :class="selectedMethod.active ? 'active' : 'rejected'">{{ selectedMethod.active ? 'Activo' : 'Inactivo' }}</span>
            </div>
          </AdminCard>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedMethod" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar metodo
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" :title="editingMethodId ? 'Editar metodo de envio' : 'Nuevo metodo de envio'" max-width="860px" @close="closeEditorModal">
      <div class="editor-grid editor-grid--shipping admin-editor-grid">
        <div>
          <div class="form-group">
            <label for="shipping-method-name">Nombre *</label>
            <input id="shipping-method-name" v-model.trim="form.name" type="text" class="form-control" :class="{ 'is-invalid': formErrors.name }" @input="validateField('name')">
            <p v-if="formErrors.name" class="form-error">{{ formErrors.name }}</p>
          </div>

          <div class="form-group">
            <label for="shipping-method-description">Descripcion</label>
            <textarea id="shipping-method-description" v-model.trim="form.description" rows="3" class="form-control" @input="validateField('description')"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="shipping-base-cost">Costo base *</label>
              <input id="shipping-base-cost" v-model.number="form.base_cost" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.base_cost }" @input="validateField('base_cost')">
              <p v-if="formErrors.base_cost" class="form-error">{{ formErrors.base_cost }}</p>
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="shipping-free-threshold">Envio gratis desde</label>
              <input id="shipping-free-threshold" v-model.number="form.free_shipping_minimum" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.free_shipping_minimum }" @input="validateField('free_shipping_minimum')">
              <p v-if="formErrors.free_shipping_minimum" class="form-error">{{ formErrors.free_shipping_minimum }}</p>
            </div>
          </div>
        </div>

        <div>
          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="shipping-days-min">Dias minimos</label>
              <input id="shipping-days-min" v-model.number="form.estimated_days_min" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.estimated_days }" @input="validateField('estimated_days')">
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="shipping-days-max">Dias maximos</label>
              <input id="shipping-days-max" v-model.number="form.estimated_days_max" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.estimated_days }" @input="validateField('estimated_days')">
            </div>
          </div>
          <p v-if="formErrors.estimated_days" class="form-error">{{ formErrors.estimated_days }}</p>

          <div class="form-group">
            <label for="shipping-delivery-time">Texto visible de entrega</label>
            <input id="shipping-delivery-time" v-model.trim="form.delivery_time" type="text" class="form-control" placeholder="Ej: Entrega entre 24 y 48 horas" @input="validateField('delivery_time')">
            <p v-if="formErrors.delivery_time" class="form-error">{{ formErrors.delivery_time }}</p>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="shipping-city">Ciudad</label>
              <input id="shipping-city" v-model.trim="form.city" type="text" class="form-control" placeholder="Ej: Medellin" @input="validateField('city')">
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="shipping-icon">Icono</label>
              <select id="shipping-icon" v-model="form.icon" class="form-control">
                <option value="fa-truck">Camion</option>
                <option value="fa-shipping-fast">Rapido</option>
                <option value="fa-box-open">Paqueteria</option>
                <option value="fa-store">Recogida</option>
              </select>
            </div>
          </div>

          <div class="form-group form-group--toggle">
            <label class="toggle-label">
              <input v-model="form.active" type="checkbox">
              Metodo activo para el checkout
            </label>
          </div>

          <div class="shipping-preview-card admin-surface-card">
            <p class="shipping-preview-card__label admin-surface-card__label">Vista previa</p>
            <h3>{{ form.name || 'Metodo de envio' }}</h3>
            <p>{{ deliveryWindowLabel(form) }}</p>
            <p>{{ form.city || 'Cobertura general' }}</p>
            <span class="status-badge" :class="form.active ? 'active' : 'rejected'">{{ form.active ? 'Activo' : 'Inactivo' }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveMethod">
          <i class="fas fa-save"></i>
          {{ editingMethodId ? 'Guardar cambios' : 'Crear metodo' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { shippingHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const methods = ref([])
const selectedMethod = ref(null)
const showDetailModal = ref(false)
const showEditorModal = ref(false)
const editingMethodId = ref(null)

const filters = reactive({ search: '', state: 'all', city: 'all' })
const form = reactive({
  name: '',
  description: '',
  base_cost: 0,
  delivery_time: '',
  estimated_days_min: null,
  estimated_days_max: null,
  free_shipping_minimum: null,
  city: '',
  icon: 'fa-truck',
  active: true,
})
const formErrors = reactive({
  name: '',
  base_cost: '',
  free_shipping_minimum: '',
  estimated_days: '',
  delivery_time: '',
  city: '',
})

const availableCities = computed(() => [...new Set(methods.value.map((method) => String(method.city || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b)))
const filteredMethods = computed(() => {
  const term = filters.search.trim().toLowerCase()

  return methods.value.filter((method) => {
    if (filters.state === 'active' && !method.active) return false
    if (filters.state === 'inactive' && method.active) return false
    if (filters.state === 'free-threshold' && !method.free_shipping_minimum) return false
    if (filters.city === 'national' && method.city) return false
    if (filters.city !== 'all' && filters.city !== 'national' && method.city !== filters.city) return false
    if (!term) return true

    return [method.name, method.description, method.city, method.delivery_time].join(' ').toLowerCase().includes(term)
  })
})

const pagination = useAdminPagination(filteredMethods, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const activeFilterCount = computed(() => [filters.search, filters.state !== 'all', filters.city !== 'all'].filter(Boolean).length)
const shippingStats = computed(() => [
  { key: 'total', label: 'Total metodos', value: methods.value.length, icon: 'fas fa-truck', color: 'primary' },
  { key: 'active', label: 'Activos', value: methods.value.filter((method) => method.active).length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'cities', label: 'Ciudades fijas', value: availableCities.value.length, icon: 'fas fa-map-marker-alt', color: 'info' },
  { key: 'free', label: 'Con envio gratis', value: methods.value.filter((method) => Number(method.free_shipping_minimum || 0) > 0).length, icon: 'fas fa-gift', color: 'warning' },
])

function resetForm() {
  form.name = ''
  form.description = ''
  form.base_cost = 0
  form.delivery_time = ''
  form.estimated_days_min = null
  form.estimated_days_max = null
  form.free_shipping_minimum = null
  form.city = ''
  form.icon = 'fa-truck'
  form.active = true
  clearErrors()
}

function clearErrors() {
  Object.keys(formErrors).forEach((key) => {
    formErrors[key] = ''
  })
}

function clearFilters() {
  filters.search = ''
  filters.state = 'all'
  filters.city = 'all'
}

function openCreateModal() {
  editingMethodId.value = null
  resetForm()
  showEditorModal.value = true
}

function openEditModal(method) {
  editingMethodId.value = method.id
  clearErrors()
  form.name = method.name || ''
  form.description = method.description || ''
  form.base_cost = Number(method.base_cost || 0)
  form.delivery_time = method.delivery_time || ''
  form.estimated_days_min = method.estimated_days_min ?? null
  form.estimated_days_max = method.estimated_days_max ?? null
  form.free_shipping_minimum = method.free_shipping_minimum ?? null
  form.city = method.city || ''
  form.icon = method.icon || 'fa-truck'
  form.active = Boolean(method.active)
  showEditorModal.value = true
}

function closeEditorModal() {
  showEditorModal.value = false
  editingMethodId.value = null
  resetForm()
}

function openDetailModal(method) {
  selectedMethod.value = method
  showDetailModal.value = true
}

function closeDetailModal() {
  selectedMethod.value = null
  showDetailModal.value = false
}

function openEditFromDetail() {
  if (!selectedMethod.value) return
  const current = selectedMethod.value
  closeDetailModal()
  openEditModal(current)
}

function validateField(field) {
  switch (field) {
    case 'name':
      formErrors.name = form.name.trim().length >= 3 ? '' : 'El nombre debe tener al menos 3 caracteres.'
      break
    case 'base_cost':
      formErrors.base_cost = Number(form.base_cost) >= 0 ? '' : 'El costo base no puede ser negativo.'
      break
    case 'free_shipping_minimum':
      formErrors.free_shipping_minimum = form.free_shipping_minimum === null || form.free_shipping_minimum === '' || Number(form.free_shipping_minimum) >= 0
        ? ''
        : 'El umbral de envio gratis no puede ser negativo.'
      break
    case 'estimated_days':
      formErrors.estimated_days = ''
      if (form.estimated_days_min && Number(form.estimated_days_min) < 1) formErrors.estimated_days = 'Los dias minimos deben ser mayores que cero.'
      if (!formErrors.estimated_days && form.estimated_days_max && Number(form.estimated_days_max) < 1) formErrors.estimated_days = 'Los dias maximos deben ser mayores que cero.'
      if (!formErrors.estimated_days && form.estimated_days_min && form.estimated_days_max && Number(form.estimated_days_max) < Number(form.estimated_days_min)) {
        formErrors.estimated_days = 'El maximo debe ser mayor o igual al minimo.'
      }
      break
    case 'delivery_time':
      formErrors.delivery_time = form.delivery_time && form.delivery_time.trim().length < 4 ? 'Describe mejor la promesa de entrega.' : ''
      break
    case 'city':
      formErrors.city = form.city && form.city.trim().length < 3 ? 'La ciudad debe tener al menos 3 caracteres.' : ''
      break
    default:
      break
  }
}

function validateForm() {
  validateField('name')
  validateField('base_cost')
  validateField('free_shipping_minimum')
  validateField('estimated_days')
  validateField('delivery_time')
  validateField('city')
  return Object.values(formErrors).every((value) => !value)
}

async function loadMethods() {
  loading.value = true
  try {
    const { data } = await shippingHttp.get('/admin/shipping-methods')
    methods.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    methods.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los metodos de envio.') })
  } finally {
    loading.value = false
  }
}

async function saveMethod() {
  if (!validateForm()) {
    showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
    return
  }

  const payload = {
    name: form.name.trim(),
    description: form.description.trim() || null,
    base_cost: Number(form.base_cost || 0),
    delivery_time: form.delivery_time.trim() || null,
    estimated_days_min: form.estimated_days_min || null,
    estimated_days_max: form.estimated_days_max || null,
    free_shipping_minimum: form.free_shipping_minimum || null,
    city: form.city.trim() || null,
    icon: form.icon,
    active: form.active,
  }

  try {
    if (editingMethodId.value) {
      await shippingHttp.put(`/admin/shipping-methods/${editingMethodId.value}`, payload)
      showSnackbar({ type: 'success', message: 'Metodo actualizado correctamente.' })
    } else {
      await shippingHttp.post('/admin/shipping-methods', payload)
      showSnackbar({ type: 'success', message: 'Metodo creado correctamente.' })
    }

    closeEditorModal()
    await loadMethods()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el metodo.') })
  }
}

function confirmDeleteMethod(method) {
  showAlert({
    type: 'warning',
    title: 'Eliminar metodo',
    message: `Vas a eliminar ${method.name}. Esta accion no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await shippingHttp.delete(`/admin/shipping-methods/${method.id}`)
            showSnackbar({ type: 'success', message: 'Metodo eliminado correctamente.' })
            if (selectedMethod.value?.id === method.id) closeDetailModal()
            await loadMethods()
          } catch (error) {
            showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el metodo.') })
          }
        },
      },
    ],
  })
}

function exportMethods() {
  const rows = filteredMethods.value.map((method) => [
    method.name,
    method.city || 'Cobertura general',
    formatCurrency(method.base_cost),
    method.free_shipping_minimum ? formatCurrency(method.free_shipping_minimum) : 'No aplica',
    deliveryWindowLabel(method),
    method.active ? 'Activo' : 'Inactivo',
  ])
  const csv = [['Metodo', 'Cobertura', 'Costo base', 'Gratis desde', 'Promesa', 'Estado'].join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv('metodos-envio.csv', csv)
}

function deliveryWindowLabel(method) {
  const min = Number(method.estimated_days_min || 0)
  const max = Number(method.estimated_days_max || 0)
  if (min && max) return `${min} a ${max} dias`
  if (max) return `Hasta ${max} dias`
  if (min) return `${min} dias`
  return 'Sin rango definido'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
}

function extractErrorMessage(error, fallback) {
  return error?.response?.data?.message || fallback
}

function csvSafe(value) {
  return `"${String(value ?? '').replaceAll('"', '""')}"`
}

function downloadCsv(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

onMounted(loadMethods)
</script>

<style scoped>
.shipping-method-hero h3,
.shipping-preview-card h3 {
  font-size: 2.2rem;
}
</style>
