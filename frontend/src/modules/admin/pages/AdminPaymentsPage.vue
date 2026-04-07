<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-credit-card"
      title="Pagos"
      subtitle="Gestiona los pagos y comprobantes de pago."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Pagos' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="paymentStats" />

    <div class="filters-bar admin-entity-filters">
      <div class="filter-group">
        <label for="payment-status">Estado</label>
        <select id="payment-status" v-model="statusFilter">
          <option value="">Todos</option>
          <option value="pending">Pendiente</option>
          <option value="approved">Aprobado</option>
          <option value="rejected">Rechazado</option>
        </select>
      </div>
      <div class="filter-group">
        <label for="payment-method">Metodo</label>
        <select id="payment-method" v-model="methodFilter">
          <option value="">Todos</option>
          <option value="transfer">Transferencia</option>
          <option value="cash">Efectivo</option>
          <option value="card">Tarjeta</option>
        </select>
      </div>
      <div class="admin-entity-filters__summary">
        <span><i class="fas fa-list"></i> {{ filtered.length }} pago(s)</span>
      </div>
    </div>

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
            <tr v-for="p in filtered" :key="p.id">
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { paymentHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

const { showSnackbar } = useSnackbarSystem()
const payments = ref([])
const loading = ref(true)
const statusFilter = ref('')
const methodFilter = ref('')

const paymentStats = computed(() => [
  { key: 'total', label: 'Total pagos', value: String(payments.value.length), icon: 'fas fa-credit-card', color: 'primary' },
  { key: 'pending', label: 'Pendientes', value: String(payments.value.filter(p => p.status === 'pending').length), icon: 'fas fa-clock', color: 'warning' },
  { key: 'approved', label: 'Aprobados', value: String(payments.value.filter(p => p.status === 'approved').length), icon: 'fas fa-check-circle', color: 'success' },
  { key: 'rejected', label: 'Rechazados', value: String(payments.value.filter(p => p.status === 'rejected').length), icon: 'fas fa-times-circle', color: 'danger' },
])

const filtered = computed(() => {
  let list = payments.value
  if (statusFilter.value) list = list.filter(p => p.status === statusFilter.value)
  if (methodFilter.value) list = list.filter(p => p.method === methodFilter.value)
  return list
})

function statusLabel(s) { return { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' }[s] || s }
function methodLabel(m) { return { transfer: 'Transferencia', cash: 'Efectivo', card: 'Tarjeta' }[m] || m }

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
