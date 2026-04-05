<template>
  <div>
    <AdminPageHeader icon="fas fa-receipt" :title="`Orden #${orderId}`" subtitle="Detalles del pedido." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Ordenes', to: '/admin/ordenes' }, { label: `#${orderId}` }]">
      <template #actions>
        <RouterLink to="/admin/ordenes" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver
        </RouterLink>
      </template>
    </AdminPageHeader>

    <div v-if="loading" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
      <AdminCard title="Items del pedido" icon="fas fa-box" :flush="true">
        <AdminTableShimmer :rows="3" :columns="['line','line','line','line']" />
      </AdminCard>
      <div>
        <AdminCard title="Resumen" icon="fas fa-calculator">
          <div v-for="i in 4" :key="i" class="admin-shimmer shimmer-line" :style="{ width: (80 - i * 10) + '%', marginBottom: '1rem' }"></div>
        </AdminCard>
      </div>
    </div>

    <div v-else-if="order" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
      <div>
        <AdminCard title="Items del pedido" icon="fas fa-box" :flush="true">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in order.items || []" :key="item.id">
                <td>{{ item.product_name || item.name || 'Producto' }}</td>
                <td>{{ item.quantity }}</td>
                <td>$ {{ Number(item.unit_price || item.price || 0).toLocaleString('es-CO') }}</td>
                <td>$ {{ Number((item.unit_price || item.price || 0) * item.quantity).toLocaleString('es-CO') }}</td>
              </tr>
            </tbody>
          </table>
        </AdminCard>
      </div>

      <div>
        <AdminCard title="Resumen" icon="fas fa-calculator">
          <div style="display: flex; flex-direction: column; gap: 0.8rem; font-size: 1.4rem;">
            <div style="display: flex; justify-content: space-between;">
              <span>Subtotal:</span>
              <strong>$ {{ Number(order.subtotal || 0).toLocaleString('es-CO') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>Envio:</span>
              <strong>$ {{ Number(order.shipping_cost || 0).toLocaleString('es-CO') }}</strong>
            </div>
            <div v-if="order.discount_amount" style="display: flex; justify-content: space-between; color: var(--admin-success);">
              <span>Descuento:</span>
              <strong>-$ {{ Number(order.discount_amount).toLocaleString('es-CO') }}</strong>
            </div>
            <hr style="border: none; border-top: 1px solid var(--admin-border);">
            <div style="display: flex; justify-content: space-between; font-size: 1.6rem;">
              <strong>Total:</strong>
              <strong>$ {{ Number(order.total || 0).toLocaleString('es-CO') }}</strong>
            </div>
          </div>
        </AdminCard>

        <AdminCard title="Estado" icon="fas fa-flag" style="margin-top: 1rem;">
          <div class="form-group">
            <label>Estado del pedido:</label>
            <select class="form-control" :value="order.order_status || order.status" @change="updateStatus($event.target.value)">
              <option value="pending">Pendiente</option>
              <option value="processing">En proceso</option>
              <option value="shipped">Enviado</option>
              <option value="delivered">Entregado</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>
          <div class="form-group">
            <label>Estado del pago:</label>
            <span class="status-badge" :class="order.payment_status || 'pending'">
              {{ paymentMap[order.payment_status] || 'Pendiente' }}
            </span>
          </div>
        </AdminCard>

        <AdminCard title="Cliente" icon="fas fa-user" style="margin-top: 1rem;">
          <p style="margin: 0; font-size: 1.4rem;">{{ order.user_name || 'Cliente' }}</p>
          <p style="margin: 0; font-size: 1.3rem; color: var(--admin-text-light);">{{ order.user_email || '' }}</p>
        </AdminCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { orderHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const route = useRoute()
const { showSnackbar } = useSnackbarSystem()

const orderId = route.params.id
const order = ref(null)
const loading = ref(true)

const paymentMap = { pending: 'Pendiente', paid: 'Pagado', verified: 'Verificado', rejected: 'Rechazado' }

async function loadOrder() {
  loading.value = true
  try {
    const res = await orderHttp.get(`/orders/${orderId}`)
    order.value = res.data?.data || res.data
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando orden' })
  } finally {
    loading.value = false
  }
}

async function updateStatus(status) {
  try {
    await orderHttp.patch(`/orders/${orderId}/status`, { order_status: status })
    showSnackbar({ type: 'success', message: 'Estado actualizado' })
    loadOrder()
  } catch {
    showSnackbar({ type: 'error', message: 'Error actualizando estado' })
  }
}

onMounted(loadOrder)
</script>
