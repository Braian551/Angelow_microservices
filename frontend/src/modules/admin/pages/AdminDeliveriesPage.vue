<template>
  <div class="admin-courier-page">
    <AdminPageHeader icon="fas fa-route" title="Envíos asignados" subtitle="Consulta entregas disponibles, repartidor asignado y progreso operativo." :breadcrumbs="[{ label: 'Repartidores', to: '/admin/repartidores' }, { label: 'Envíos' }]" />
    <AdminStatsGrid :loading="loading" :stats="stats" :count="3" />
    <AdminFilterCard title="Filtrar envíos" :hide-toggle="true" initially-expanded @search="load">
      <div class="admin-filters__row"><div class="admin-filters__group"><label for="delivery-status">Estado</label><select id="delivery-status" v-model="filters.status" @change="load"><option value="">Todos</option><option value="pending">Disponibles</option><option value="assigned">Asignados</option><option value="en_route">En ruta</option><option value="arrived">En destino</option><option value="delivered">Entregados</option></select></div></div>
    </AdminFilterCard>
    <AdminResultsBar :text="`${pagination.totalItems} envíos encontrados`" />
    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'pill', 'line']" />
      <AdminEmptyState v-else-if="rows.length === 0" icon="fas fa-route" title="Sin envíos" description="No hay envíos en este estado." />
      <div v-else class="table-responsive"><table class="dashboard-table"><thead><tr><th>Orden</th><th>Método</th><th>Destino</th><th>Repartidor</th><th>Estado</th><th>Actualización</th></tr></thead><tbody><tr v-for="row in rows" :key="row.id"><td><strong>{{ row.order_number || `#${row.order_id}` }}</strong></td><td><div class="admin-entity-name"><strong>{{ row.shipping_method_name }}</strong><span>{{ row.delivery_time || 'Tiempo no definido' }}</span></div></td><td>{{ row.destination_city || row.destination_address || 'Sin destino' }}</td><td>{{ row.courier?.email || 'Sin asignar' }}</td><td><span class="status-badge" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td><td>{{ formatDate(row.updated_at) }}</td></tr></tbody></table></div>
    </AdminCard>
    <AdminPagination v-model:page="pagination.currentPage" v-model:page-size="pagination.pageSize" :total-items="pagination.totalItems" @update:page="load" @update:page-size="load" />
  </div>
</template>

<script setup>
import { useAdminDeliveries } from '../composables/useAdminDeliveries'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import '../views/AdminCouriersPage.css'
const { filters, formatDate, load, loading, pagination, rows, stats, statusClass, statusLabel } = useAdminDeliveries()
</script>
