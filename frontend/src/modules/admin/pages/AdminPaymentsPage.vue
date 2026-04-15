<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-credit-card"
      title="Pagos"
      subtitle="Gestiona los pagos y comprobantes de pago."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Pagos' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="paymentStats" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Busqueda y control de pagos"
      placeholder="Buscar por orden, cliente o referencia..."
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--2">
          <div class="admin-filters__group">
            <label for="payment-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="payment-status" v-model="statusFilter">
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="approved">Aprobado</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>
          <div class="admin-filters__group">
            <label for="payment-method"><i class="fas fa-credit-card"></i> Metodo</label>
            <select id="payment-method" v-model="methodFilter">
              <option value="">Todos</option>
              <option value="transfer">Transferencia</option>
              <option value="cash">Efectivo</option>
              <option value="card">Tarjeta</option>
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} pagos`" />

    <AdminCard title="Listado de pagos" icon="fas fa-list" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'line', 'line', 'pill', 'btn', 'btn']" />
      <AdminEmptyState v-else-if="filtered.length === 0" icon="fas fa-credit-card" title="Sin pagos registrados" description="No se encontraron pagos con los filtros actuales." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Orden</th>
              <th>Cliente</th>
              <th>Monto</th>
              <th>Metodo</th>
              <th>Estado</th>
              <th>Comprobante</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pagination.paginatedItems" :key="p.id">
              <td>{{ p.id }}</td>
              <td><RouterLink :to="`/admin/ordenes/${p.order_id}`">#{{ p.order_id }}</RouterLink></td>
              <td>{{ p.customer_name || '—' }}</td>
              <td><strong>${{ Number(p.amount || 0).toLocaleString() }}</strong></td>
              <td>{{ methodLabel(p.method) }}</td>
              <td><span class="status-badge" :class="p.status">{{ statusLabel(p.status) }}</span></td>
              <td>
                <a v-if="p.proof_url" :href="p.proof_url" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-image"></i></a>
                <span v-else>—</span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button v-if="p.status === 'pending'" class="action-btn edit" type="button" title="Aprobar" @click="updatePayment(p.id, 'approved')">
                    <i class="fas fa-check"></i>
                  </button>
                  <button v-if="p.status === 'pending'" class="action-btn delete" type="button" title="Rechazar" @click="updatePayment(p.id, 'rejected')">
                    <i class="fas fa-times"></i>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { paymentHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminPagination from '../components/AdminPagination.vue'

const { showSnackbar } = useSnackbarSystem()
const payments = ref([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const methodFilter = ref('')

const paymentStats = computed(() => [
  { key: 'total', label: 'Total pagos', value: String(payments.value.length), icon: 'fas fa-credit-card', color: 'primary' },
  { key: 'pending', label: 'Pendientes', value: String(payments.value.filter(p => p.status === 'pending').length), icon: 'fas fa-clock', color: 'warning' },
  { key: 'approved', label: 'Aprobados', value: String(payments.value.filter(p => p.status === 'approved').length), icon: 'fas fa-check-circle', color: 'success' },
  { key: 'rejected', label: 'Rechazados', value: String(payments.value.filter(p => p.status === 'rejected').length), icon: 'fas fa-times-circle', color: 'danger' },
])

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  let list = payments.value
  if (statusFilter.value) list = list.filter((payment) => payment.status === statusFilter.value)
  if (methodFilter.value) list = list.filter((payment) => payment.method === methodFilter.value)
  if (term) {
    list = list.filter((payment) => [payment.id, payment.order_id, payment.customer_name, payment.reference_number, payment.method]
      .some((value) => String(value || '').toLowerCase().includes(term)))
  }
  return list
})

const pagination = useAdminPagination(filtered, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const activeFilterCount = computed(() => [search.value.trim(), statusFilter.value, methodFilter.value].filter(Boolean).length)

function statusLabel(s) { return { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' }[s] || s }
function methodLabel(m) { return { transfer: 'Transferencia', cash: 'Efectivo', card: 'Tarjeta' }[m] || m }

function clearFilters() {
  search.value = ''
  statusFilter.value = ''
  methodFilter.value = ''
}

async function loadPayments() {
  loading.value = true
  try {
    const { data } = await paymentHttp.get('/admin/payments')
    payments.value = data.data || data || []
  } catch { payments.value = [] } finally { loading.value = false }
}

async function updatePayment(id, status) {
  try {
    await paymentHttp.patch(`/admin/payments/${id}`, { status })
    showSnackbar({ type: 'success', message: `Pago ${status === 'approved' ? 'aprobado' : 'rechazado'}` })
    await loadPayments()
  } catch { showSnackbar({ type: 'error', message: 'Error al actualizar pago' }) }
}

onMounted(loadPayments)
</script>
