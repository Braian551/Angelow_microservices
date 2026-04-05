<template>
  <div>
    <AdminPageHeader
      icon="fas fa-star"
      title="Resenas"
      subtitle="Modera las resenas de productos."
      :breadcrumbs="[{ label: 'Resenas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="reviewStats" />

    <div class="filters-bar">
      <div class="filter-group"><label>Estado:</label>
        <select v-model="statusFilter" @change="filterReviews"><option value="">Todas</option><option value="pending">Pendientes</option><option value="approved">Aprobadas</option><option value="rejected">Rechazadas</option></select>
      </div>
    </div>

    <AdminCard :flush="true">
      <template v-if="loading">
        <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
          <div v-for="i in 3" :key="i" class="admin-shimmer shimmer-rect" style="height: 8rem;"></div>
        </div>
      </template>
      <AdminEmptyState v-else-if="filtered.length === 0" icon="fas fa-star" title="Sin resenas" description="No se encontraron resenas con los filtros actuales." />
      <div v-else style="padding: 1.5rem;">
        <div v-for="r in filtered" :key="r.id" class="review-card">
          <div class="review-header">
            <div><span class="review-rating"><i v-for="n in 5" :key="n" class="fas fa-star" :style="{ color: n <= r.rating ? '#ffcc00' : '#ddd' }"></i></span> <strong style="margin-left:0.5rem;">{{ r.title || 'Sin titulo' }}</strong></div>
            <span class="status-badge" :class="r.status">{{ statusLabel(r.status) }}</span>
          </div>
          <p class="review-body">{{ r.body || r.comment || '' }}</p>
          <div class="review-meta">
            <span>{{ r.user_name || 'Cliente' }} — {{ r.product_name || '' }}</span>
            <div style="display:flex;gap:0.5rem;">
              <button v-if="r.status !== 'approved'" class="btn btn-sm btn-success" @click="updateReview(r.id, 'approved')"><i class="fas fa-check"></i> Aprobar</button>
              <button v-if="r.status !== 'rejected'" class="btn btn-sm btn-danger" @click="updateReview(r.id, 'rejected')"><i class="fas fa-times"></i> Rechazar</button>
            </div>
          </div>
        </div>
      </div>
    </AdminCard>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

const { showSnackbar } = useSnackbarSystem()
const reviews = ref([]), loading = ref(true), statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? reviews.value.filter(r => r.status === statusFilter.value) : reviews.value)
const avgRating = computed(() => { if (!reviews.value.length) return '0.0'; return (reviews.value.reduce((s, r) => s + (r.rating || 0), 0) / reviews.value.length).toFixed(1) })

const reviewStats = computed(() => [
  { key: 'total', label: 'Total resenas', value: String(reviews.value.length), icon: 'fas fa-star', color: 'primary' },
  { key: 'pending', label: 'Pendientes', value: String(reviews.value.filter(r => r.status === 'pending').length), icon: 'fas fa-clock', color: 'warning' },
  { key: 'approved', label: 'Aprobadas', value: String(reviews.value.filter(r => r.status === 'approved').length), icon: 'fas fa-check-circle', color: 'success' },
  { key: 'avg', label: 'Promedio', value: avgRating.value, icon: 'fas fa-chart-bar', color: 'info' },
])

function statusLabel(s) { return { pending: 'Pendiente', approved: 'Aprobada', rejected: 'Rechazada' }[s] || s }
function filterReviews() {}

async function loadReviews() {
  loading.value = true
  try {
    const params = {}
    if (statusFilter.value) params.status = statusFilter.value

    const response = await catalogHttp.get('/admin/reviews', { params })
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    reviews.value = rows.map((row) => ({
      ...row,
      title: row.title || row.titulo || 'Sin titulo',
      rating: Number(row.rating ?? row.calificacion ?? 0),
      body: row.body || row.comment || row.comentario || '',
      status: row.status || 'pending',
    }))
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando reseñas' })
  } finally {
    loading.value = false
  }
}

async function updateReview(id, status) {
  try {
    await catalogHttp.patch(`/admin/reviews/${id}`, { status })
    showSnackbar({ type: 'success', message: `Reseña ${status === 'approved' ? 'aprobada' : 'rechazada'}` })
    await loadReviews()
  } catch {
    showSnackbar({ type: 'error', message: 'Error actualizando reseña' })
  }
}

onMounted(loadReviews)
</script>
