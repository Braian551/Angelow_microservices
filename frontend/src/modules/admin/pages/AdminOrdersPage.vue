<template>
  <div>
    <AdminPageHeader
      icon="fas fa-shopping-bag"
      title="Ordenes"
      subtitle="Gestiona los pedidos de la tienda."
      :breadcrumbs="[{ label: 'Ordenes' }]"
    />

    <div class="filters-bar">
      <div class="filter-group">
        <label>Buscar:</label>
        <input type="text" v-model="search" placeholder="Numero de orden..." @input="loadOrders">
      </div>
      <div class="filter-group">
        <label>Estado:</label>
        <select v-model="statusFilter" @change="loadOrders">
          <option value="">Todos</option>
          <option value="pending">Pendiente</option>
          <option value="processing">En proceso</option>
          <option value="shipped">Enviado</option>
          <option value="delivered">Entregado</option>
          <option value="cancelled">Cancelado</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Pago:</label>
        <select v-model="paymentFilter" @change="loadOrders">
          <option value="">Todos</option>
          <option value="pending">Pendiente</option>
          <option value="paid">Pagado</option>
          <option value="verified">Verificado</option>
        </select>
      </div>
    </div>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState v-else-if="orders.length === 0" icon="fas fa-inbox" title="Sin ordenes" description="No se encontraron ordenes con los filtros actuales." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Orden</th>
              <th>Cliente</th>
              <th>Fecha</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Pago</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id">
              <td><strong>#{{ order.id }}</strong></td>
              <td>{{ order.user_name || order.customer_name || 'Cliente' }}</td>
              <td>{{ new Date(order.created_at).toLocaleDateString('es-CO') }}</td>
              <td>$ {{ Number(order.total || 0).toLocaleString('es-CO') }}</td>
              <td>
                <select class="form-control" style="padding: 0.4rem; font-size: 1.2rem; width: auto;"
                  :value="order.order_status || order.status"
                  @change="updateStatus(order.id, $event.target.value)">
                  <option value="pending">Pendiente</option>
                  <option value="processing">En proceso</option>
                  <option value="shipped">Enviado</option>
                  <option value="delivered">Entregado</option>
                  <option value="cancelled">Cancelado</option>
                </select>
              </td>
              <td>
                <span class="status-badge" :class="order.payment_status || 'pending'">
                  {{ paymentMap[order.payment_status] || 'Pendiente' }}
                </span>
              </td>
              <td>
                <RouterLink :to="`/admin/ordenes/${order.id}`" class="action-btn view" title="Ver detalle">
                  <i class="fas fa-eye"></i>
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { orderHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

const { showSnackbar } = useSnackbarSystem()

const orders = ref([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const paymentFilter = ref('')

const paymentMap = { pending: 'Pendiente', paid: 'Pagado', verified: 'Verificado', rejected: 'Rechazado' }

async function loadOrders() {
  loading.value = true
  try {
    const params = { limit: 50 }
    if (statusFilter.value) params.status = statusFilter.value
    const res = await orderHttp.get('/orders', { params })
    const data = res.data?.data || res.data || []
    orders.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando ordenes' })
  } finally {
    loading.value = false
  }
}

async function updateStatus(orderId, status) {
  try {
    await orderHttp.patch(`/orders/${orderId}/status`, { order_status: status })
    showSnackbar({ type: 'success', message: `Orden #${orderId} actualizada a "${status}"` })
    loadOrders()
  } catch {
    showSnackbar({ type: 'error', message: 'Error actualizando estado' })
  }
}

onMounted(loadOrders)
</script>
