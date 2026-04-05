<template>
  <div>
    <AdminPageHeader
      icon="fas fa-users"
      title="Clientes"
      subtitle="Informacion general de clientes registrados."
      :breadcrumbs="[{ label: 'Clientes' }]"
    />

    <AdminStatsGrid :loading="loading" :count="5" :stats="hubStatsFormatted" />

    <div class="filters-bar">
      <div class="filter-group">
        <label for="admin-customers-search">Buscar:</label>
        <input id="admin-customers-search" name="admin-customers-search" type="text" v-model="search" placeholder="Nombre o email..." @input="loadCustomers">
      </div>
    </div>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'line', 'pill']" />
      <AdminEmptyState v-else-if="customers.length === 0" icon="fas fa-users" title="Sin clientes" description="No se encontraron clientes." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Registro</th>
              <th>Pedidos</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in customers" :key="c.id">
              <td><img :src="avatarUrl(c)" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;"></td>
              <td><strong>{{ c.name }}</strong></td>
              <td>{{ c.email }}</td>
              <td>{{ new Date(c.created_at).toLocaleDateString('es-CO') }}</td>
              <td>{{ c.orders_count || 0 }}</td>
              <td><span class="status-badge" :class="c.is_blocked ? 'cancelled' : 'active'">{{ c.is_blocked ? 'Bloqueado' : 'Activo' }}</span></td>
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
import { authHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

const { showSnackbar } = useSnackbarSystem()

const customers = ref([])
const loading = ref(true)
const search = ref('')
const hubStats = ref([
  { label: 'Total clientes', value: '0' },
  { label: 'Nuevos (30d)', value: '0' },
  { label: 'Tasa de repeticion', value: '0%' },
  { label: 'LTV promedio', value: '$ 0' },
  { label: 'Activos', value: '0' },
])

const hubStatsFormatted = computed(() => {
  const icons = ['fas fa-users', 'fas fa-user-plus', 'fas fa-redo', 'fas fa-dollar-sign', 'fas fa-check-circle']
  const colors = ['primary', 'info', 'warning', 'success', 'primary']
  return hubStats.value.map((s, i) => ({
    key: s.label,
    label: s.label,
    value: s.value,
    icon: icons[i],
    color: colors[i],
  }))
})

function avatarUrl(c) {
  if (c.image) {
    if (c.image.startsWith('http') || c.image.startsWith('/')) return c.image
    return `/uploads/users/${c.image}`
  }
  return '/assets/default-avatar.png'
}

async function loadCustomers() {
  loading.value = true
  try {
    // Endpoint admin (no interno) para evitar 403 desde frontend.
    const res = await authHttp.get('/admin/customers', { params: { search: search.value || undefined } })
    const data = res.data?.data || res.data || []
    customers.value = Array.isArray(data) ? data : (data.data || [])
    hubStats.value[0].value = String(customers.value.length)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando clientes' })
  } finally {
    loading.value = false
  }
}

onMounted(loadCustomers)
</script>
