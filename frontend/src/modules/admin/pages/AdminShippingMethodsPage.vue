<template>
  <div class="admin-shipping-methods-page">
    <AdminPageHeader
      icon="fas fa-truck"
      title="Definir envíos"
      subtitle="Gestiona el costo base de cada método. Los recargos por rango se administran por separado y se suman como valor adicional."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Definir envíos' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="exportMethods">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo método
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="shippingStats" />

    <!-- Filtros de metodos de envio -->
    <AdminFilterCard
      icon="fas fa-filter"
      title="Búsqueda y cobertura"
      placeholder="Buscar por nombre, ciudad o descripción..."
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
              <option value="free-threshold">Con envío gratis</option>
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
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} métodos`" />

    <AdminCard title="Bandeja de métodos" icon="fas fa-truck" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredMethods.length === 0"
        icon="fas fa-truck"
        title="Sin métodos de envío"
        description="No hay métodos configurados o ninguno coincide con los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table shipping-methods-table">
          <thead>
            <tr>
              <th>Método</th>
              <th>Cobertura</th>
              <th>Tiempo</th>
              <th>Costo base del método</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="method in pagination.paginatedItems" :key="method.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ method.name }}</strong>
                  <span>{{ method.description || 'Sin descripción operativa' }}</span>
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
                  <button class="action-btn edit" type="button" title="Editar método" @click="openEditModal(method)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar método" @click="confirmDeleteMethod(method)">
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

    <AdminModal :show="showDetailModal" :title="selectedMethod ? selectedMethod.name : 'Detalle del método'" max-width="940px" @close="closeDetailModal">
      <template v-if="selectedMethod">
        <div class="shipping-detail-grid admin-detail-grid">
          <AdminCard title="Resumen del método" icon="fas fa-shipping-fast">
            <div class="admin-detail-summary">
              <div class="admin-detail-summary__row"><span>Descripción</span><strong>{{ selectedMethod.description || 'Sin descripción' }}</strong></div>
              <div class="admin-detail-summary__row"><span>Costo base</span><strong>{{ formatCurrency(selectedMethod.base_cost) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Recargos por rango</span><strong>Se suman como cargo adicional</strong></div>
              <div class="admin-detail-summary__row"><span>Promesa</span><strong>{{ deliveryWindowLabel(selectedMethod) }}</strong></div>
              <div class="admin-detail-summary__row"><span>Tiempo libre</span><strong>{{ selectedMethod.delivery_time || 'No definido' }}</strong></div>
              <div class="admin-detail-summary__row"><span>Ciudad</span><strong>{{ selectedMethod.city || 'Cobertura general' }}</strong></div>
              <div class="admin-detail-summary__row"><span>Envío gratis desde</span><strong>{{ selectedMethod.free_shipping_minimum ? formatCurrency(selectedMethod.free_shipping_minimum) : 'No aplica' }}</strong></div>
            </div>
          </AdminCard>

          <AdminCard title="Estado comercial" icon="fas fa-clipboard-check">
            <div class="shipping-method-hero admin-surface-card">
              <p class="shipping-method-hero__label admin-surface-card__label">Método</p>
              <h3>{{ selectedMethod.name }}</h3>
              <p>{{ selectedMethod.city || 'Disponible sin restricción de ciudad' }}</p>
              <span class="status-badge" :class="selectedMethod.active ? 'active' : 'rejected'">{{ selectedMethod.active ? 'Activo' : 'Inactivo' }}</span>
            </div>
          </AdminCard>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedMethod" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar método
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" :title="editingMethodId ? 'Editar método de envío' : 'Nuevo método de envío'" max-width="860px" @close="closeEditorModal">
      <div class="editor-grid editor-grid--shipping admin-editor-grid">
        <div>
          <div class="form-group">
            <label for="shipping-method-name">
              Nombre *
              <AdminInfoTooltip text="Nombre visible del método de envío tal como aparece al cliente al finalizar la compra." />
            </label>
            <input id="shipping-method-name" v-model.trim="form.name" type="text" class="form-control" :class="{ 'is-invalid': formErrors.name }" @input="validateField('name')">
            <p v-if="formErrors.name" class="form-error">{{ formErrors.name }}</p>
          </div>

          <div class="form-group">
            <label for="shipping-method-description">
              Descripción
              <AdminInfoTooltip text="Texto interno descriptivo del método. No visible al cliente durante la compra." />
            </label>
            <textarea id="shipping-method-description" v-model.trim="form.description" rows="3" class="form-control" @input="validateField('description')"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="shipping-base-cost">
                Costo base *
                <AdminInfoTooltip text="Tarifa base del método. Los recargos por rango se suman aparte como un valor adicional." />
              </label>
              <input id="shipping-base-cost" v-model.number="form.base_cost" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.base_cost }" @input="validateField('base_cost')">
              <p v-if="formErrors.base_cost" class="form-error">{{ formErrors.base_cost }}</p>
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="shipping-free-threshold">
                Envío gratis desde
                <AdminInfoTooltip text="Monto mínimo del pedido a partir del cual el envío es gratuito con este método." />
              </label>
              <input id="shipping-free-threshold" v-model.number="form.free_shipping_minimum" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.free_shipping_minimum }" @input="validateField('free_shipping_minimum')">
              <p v-if="formErrors.free_shipping_minimum" class="form-error">{{ formErrors.free_shipping_minimum }}</p>
            </div>
          </div>

          <p class="shipping-editor-hint">
            Los valores de <strong>Recargos por rango</strong> se aplican como adicional sobre este costo base en checkout.
          </p>
        </div>

        <div>
          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="shipping-days-min">
                Días mínimos
                <AdminInfoTooltip text="Número mínimo de días hábiles estimados para la entrega." />
              </label>
              <input id="shipping-days-min" v-model.number="form.estimated_days_min" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.estimated_days }" @input="validateField('estimated_days')">
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="shipping-days-max">
                Días máximos
                <AdminInfoTooltip text="Número máximo de días hábiles estimados para la entrega." />
              </label>
              <input id="shipping-days-max" v-model.number="form.estimated_days_max" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.estimated_days }" @input="validateField('estimated_days')">
            </div>
          </div>
          <p v-if="formErrors.estimated_days" class="form-error">{{ formErrors.estimated_days }}</p>

          <div class="form-group">
            <label for="shipping-delivery-time">
              Texto visible de entrega
              <AdminInfoTooltip text="Mensaje que verá el cliente sobre el tiempo estimado. Ejemplo: «Entrega entre 24 y 48 horas»." />
            </label>
            <input id="shipping-delivery-time" v-model.trim="form.delivery_time" type="text" class="form-control" placeholder="Ej: Entrega entre 24 y 48 horas" @input="validateField('delivery_time')">
            <p v-if="formErrors.delivery_time" class="form-error">{{ formErrors.delivery_time }}</p>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="shipping-city">
                Ciudad
                <AdminInfoTooltip text="Ciudad o zona de cobertura del método. Dejar vacío si aplica a nivel nacional." />
              </label>
              <input id="shipping-city" v-model.trim="form.city" type="text" class="form-control" placeholder="Ej: Medellín" @input="validateField('city')">
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="shipping-icon">
                Icono
                <AdminInfoTooltip text="Ícono representativo del método que se muestra durante la compra." />
              </label>
              <select id="shipping-icon" v-model="form.icon" class="form-control">
                <option value="fa-truck">Camión</option>
                <option value="fa-shipping-fast">Rápido</option>
                <option value="fa-box-open">Paquetería</option>
                <option value="fa-store">Recogida</option>
              </select>
            </div>
          </div>

            <AdminToggleSwitch
              id="shipping-method-active"
              v-model="form.active"
              title="Método activo"
              description="Si está activo, el método se puede seleccionar durante el checkout."
            />

          <div class="shipping-preview-card admin-surface-card">
            <p class="shipping-preview-card__label admin-surface-card__label">Vista previa del cliente</p>
            <div class="shipping-method-mock-card" :class="{ 'shipping-method-mock-card--active': form.active }">
              <div class="shipping-method-mock-icon">
                <i :class="`fas ${form.icon || 'fa-truck'}`"></i>
              </div>
              <div class="shipping-method-mock-copy">
                <h4>{{ form.name || 'Nombre del método' }}</h4>
                <p>{{ form.description || 'Entrega segura con seguimiento de tu pedido.' }}</p>
                <span v-if="form.delivery_time" class="shipping-method-mock-time">
                  <i class="fas fa-clock"></i>
                  {{ form.delivery_time }}
                </span>
                <div class="shipping-method-mock-breakdown">
                  <span>Costo base: {{ form.base_cost > 0 ? formatCurrency(form.base_cost) : 'Gratis' }}</span>
                  <span class="shipping-method-mock-breakdown__hint">+ recargo adicional según rango del pedido</span>
                </div>
              </div>
              <div class="shipping-method-mock-cost">
                <small>Total envío</small>
                <strong>{{ form.base_cost > 0 ? formatCurrency(form.base_cost) : 'Gratis' }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveMethod">
          <i class="fas fa-save"></i>
          {{ editingMethodId ? 'Guardar cambios' : 'Crear método' }}
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
  { key: 'total', label: 'Total métodos', value: methods.value.length, icon: 'fas fa-truck', color: 'primary' },
  { key: 'active', label: 'Activos', value: methods.value.filter((method) => method.active).length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'cities', label: 'Ciudades fijas', value: availableCities.value.length, icon: 'fas fa-map-marker-alt', color: 'info' },
  { key: 'free', label: 'Con envío gratis', value: methods.value.filter((method) => Number(method.free_shipping_minimum || 0) > 0).length, icon: 'fas fa-gift', color: 'warning' },
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
        : 'El umbral de envío gratis no puede ser negativo.'
      break
    case 'estimated_days':
      formErrors.estimated_days = ''
      if (form.estimated_days_min && Number(form.estimated_days_min) < 1) formErrors.estimated_days = 'Los días mínimos deben ser mayores que cero.'
      if (!formErrors.estimated_days && form.estimated_days_max && Number(form.estimated_days_max) < 1) formErrors.estimated_days = 'Los días máximos deben ser mayores que cero.'
      if (!formErrors.estimated_days && form.estimated_days_min && form.estimated_days_max && Number(form.estimated_days_max) < Number(form.estimated_days_min)) {
        formErrors.estimated_days = 'El máximo debe ser mayor o igual al mínimo.'
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
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los métodos de envío.') })
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
      showSnackbar({ type: 'success', message: 'Método actualizado correctamente.' })
    } else {
      await shippingHttp.post('/admin/shipping-methods', payload)
      showSnackbar({ type: 'success', message: 'Método creado correctamente.' })
    }

    closeEditorModal()
    await loadMethods()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el método.') })
  }
}

function confirmDeleteMethod(method) {
  showAlert({
    type: 'warning',
    title: 'Eliminar método',
    message: `Vas a eliminar ${method.name}. Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await shippingHttp.delete(`/admin/shipping-methods/${method.id}`)
            showSnackbar({ type: 'success', message: 'Método eliminado correctamente.' })
            if (selectedMethod.value?.id === method.id) closeDetailModal()
            await loadMethods()
          } catch (error) {
            showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el método.') })
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
  const csv = [['Método', 'Cobertura', 'Costo base', 'Gratis desde', 'Promesa', 'Estado'].join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv('metodos-envio.csv', csv)
}

function deliveryWindowLabel(method) {
  const min = Number(method.estimated_days_min || 0)
  const max = Number(method.estimated_days_max || 0)
  if (min && max) return `${min} a ${max} días`
  if (max) return `Hasta ${max} días`
  if (min) return `${min} días`
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
.shipping-method-hero h3 {
  font-size: 2.2rem;
}

.shipping-editor-hint {
  margin: 0.4rem 0 0;
  color: #5a6571;
  font-size: 1.28rem;
  line-height: 1.45;
}

/* Mock card: réplica del card de método en checkout */
.shipping-method-mock-card {
  display: flex;
  align-items: flex-start;
  gap: 1.2rem;
  padding: 1.2rem;
  border-radius: 1.4rem;
  border: 2px solid #d0d7de;
  background: #fafbfc;
  margin-bottom: 1rem;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.shipping-method-mock-card--active {
  border-color: #0077b6;
  background: #f0f8ff;
}

.shipping-method-mock-icon {
  width: 4rem;
  height: 4rem;
  border-radius: 1rem;
  background: #e8f4fd;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0077b6;
  font-size: 1.6rem;
  flex-shrink: 0;
}

.shipping-method-mock-copy {
  flex: 1;
  min-width: 0;
}

.shipping-method-mock-copy h4 {
  margin: 0 0 0.35rem;
  font-size: 1.45rem;
  font-weight: 700;
  color: #1a1a2e;
}

.shipping-method-mock-copy p {
  margin: 0 0 0.45rem;
  font-size: 1.22rem;
  color: #5c6773;
  line-height: 1.45;
}

.shipping-method-mock-time {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  color: #0077b6;
  font-size: 1.18rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.shipping-method-mock-breakdown {
  margin-top: 0.5rem;
  display: grid;
  gap: 0.28rem;
  font-size: 1.15rem;
}

.shipping-method-mock-breakdown span {
  color: #5c6773;
}

.shipping-method-mock-breakdown__hint {
  color: #0077b6 !important;
  font-weight: 600;
}

.shipping-method-mock-cost {
  display: grid;
  justify-items: end;
  gap: 0.28rem;
  flex-shrink: 0;
  text-align: right;
}

.shipping-method-mock-cost small {
  color: #6b7280;
  font-size: 1.02rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 700;
}

.shipping-method-mock-cost strong {
  color: #0077b6;
  font-size: 1.65rem;
  font-weight: 700;
}
</style>
