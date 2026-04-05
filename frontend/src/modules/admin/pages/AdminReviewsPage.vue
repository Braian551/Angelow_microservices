<template>
  <div class="admin-reviews-page">
    <AdminPageHeader
      icon="fas fa-star"
      title="Reseñas"
      subtitle="Modera reputación y visibilidad del catálogo con el mismo flujo de Angelow legacy sobre microservicios."
      :breadcrumbs="[{ label: 'Reseñas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="5" :stats="reviewStats" />

    <AdminCard class="filters-card" :flush="true">
      <template #header>
        <div class="filters-header">
          <div class="filters-title">
            <i class="fas fa-filter"></i>
            <h3>Bandeja de moderación</h3>
          </div>
          <button type="button" class="filters-toggle" :class="{ collapsed: !showAdvanced }" @click="showAdvanced = !showAdvanced">
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
      </template>

      <div class="search-bar">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input
            v-model="filters.search"
            type="text"
            class="search-input"
            placeholder="Buscar por título, comentario o producto..."
            @input="debouncedLoadReviews"
          >
          <button v-if="filters.search" type="button" class="search-clear" @click="clearSearch">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <button type="button" class="search-submit-btn" @click="loadReviews">
          <i class="fas fa-search"></i>
          <span>Buscar</span>
        </button>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row filters-row--reviews">
          <div class="filter-group">
            <label for="review-status"><i class="fas fa-flag"></i> Estado</label>
            <select id="review-status" v-model="filters.status" @change="loadReviews">
              <option value="all">Todas</option>
              <option value="pending">Pendientes</option>
              <option value="approved">Publicadas</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="review-rating"><i class="fas fa-star"></i> Rating</label>
            <select id="review-rating" v-model="filters.rating" @change="loadReviews">
              <option value="">Cualquiera</option>
              <option value="5">5 estrellas</option>
              <option value="4">4 estrellas</option>
              <option value="3">3 estrellas</option>
              <option value="2">2 estrellas</option>
              <option value="1">1 estrella</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="review-verified"><i class="fas fa-badge-check"></i> Compra verificada</label>
            <select id="review-verified" v-model="filters.verified" @change="loadReviews">
              <option value="all">Todas</option>
              <option value="verified">Solo verificadas</option>
              <option value="unverified">Sin verificar</option>
            </select>
          </div>
        </div>

        <div class="filters-actions-bar">
          <div class="active-filters">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="filters-buttons">
            <button type="button" class="btn-clear-filters" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar todo
            </button>
          </div>
        </div>
      </div>
    </AdminCard>

    <section class="insights-grid">
      <AdminCard title="Distribución de rating" icon="fas fa-chart-pie">
        <div class="rating-distribution" v-if="ratingDistribution.length">
          <div v-for="bucket in ratingDistribution" :key="bucket.rating" class="rating-row">
            <div class="rating-label">
              <span>{{ bucket.rating }} estrellas</span>
              <strong>{{ bucket.count }}</strong>
            </div>
            <div class="rating-bar-track">
              <div class="rating-bar-fill" :style="{ width: `${bucket.share}%` }"></div>
            </div>
            <small>{{ bucket.share.toFixed(0) }}%</small>
          </div>
        </div>
        <div v-else class="detail-empty">Sin datos para graficar.</div>
      </AdminCard>

      <AdminCard title="Últimas reseñas" icon="fas fa-comments">
        <div v-if="highlightReviews.length === 0" class="detail-empty">Sin actividad reciente.</div>
        <div v-else class="highlights-list">
          <button
            v-for="review in highlightReviews"
            :key="review.id"
            type="button"
            class="highlight-item"
            @click="openReviewModal(review)"
          >
            <div>
              <strong>{{ review.customer.name }}</strong>
              <span>{{ review.product_name }}</span>
            </div>
            <div class="highlight-meta">
              <span class="stars-inline" v-html="renderStars(review.rating)"></span>
              <small>{{ formatDate(review.created_at) }}</small>
            </div>
          </button>
        </div>
      </AdminCard>
    </section>

    <div class="results-summary">
      <div class="results-info">
        <i class="fas fa-list"></i>
        <p>Mostrando {{ reviews.length }} reseñas</p>
      </div>
      <div class="quick-actions">
        <button class="btn btn-icon" type="button" @click="exportReviews">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
      </div>
    </div>

    <AdminCard :flush="true">
      <div v-if="loading" class="cards-loading">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'pill']" />
      </div>
      <AdminEmptyState
        v-else-if="reviews.length === 0"
        icon="fas fa-star"
        title="Sin reseñas"
        description="No se encontraron reseñas con los filtros actuales."
      />
      <div v-else class="reviews-grid">
        <article v-for="review in reviews" :key="review.id" class="review-card review-card--admin">
          <div class="review-card__header">
            <div class="review-customer">
              <img :src="avatarUrl(review.customer)" :alt="review.customer.name" @error="onAvatarError($event, review.customer.image)">
              <div>
                <strong>{{ review.customer.name }}</strong>
                <span>{{ review.product_name }}</span>
              </div>
            </div>
            <div class="review-badges">
              <span class="status-badge" :class="review.status">{{ reviewStatusLabel(review.status) }}</span>
              <span v-if="review.is_verified" class="status-badge active">Verificada</span>
            </div>
          </div>

          <div class="review-card__content" @click="openReviewModal(review)">
            <div class="review-card__rating" v-html="renderStars(review.rating)"></div>
            <h3>{{ review.title || 'Sin título' }}</h3>
            <p>{{ review.comment || 'Sin comentario' }}</p>
          </div>

          <div class="review-card__footer">
            <small>{{ formatDateTime(review.created_at) }}</small>
            <div class="entity-actions">
              <button class="action-btn view" type="button" title="Ver detalle" @click="openReviewModal(review)">
                <i class="fas fa-eye"></i>
              </button>
              <button
                v-if="review.status !== 'approved'"
                class="action-btn edit"
                type="button"
                title="Publicar reseña"
                @click="confirmReviewStatus(review, 'approved')"
              >
                <i class="fas fa-check"></i>
              </button>
              <button
                v-else
                class="action-btn"
                type="button"
                title="Enviar a revisión"
                @click="confirmReviewStatus(review, 'pending')"
              >
                <i class="fas fa-rotate-left"></i>
              </button>
              <button
                class="action-btn"
                :class="review.is_verified ? 'edit' : 'view'"
                type="button"
                :title="review.is_verified ? 'Quitar verificación' : 'Marcar verificada'"
                @click="toggleReviewVerified(review)"
              >
                <i class="fas fa-badge-check"></i>
              </button>
              <button class="action-btn delete" type="button" title="Eliminar reseña" @click="deleteReview(review)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </article>
      </div>
    </AdminCard>

    <AdminModal :show="showDetailModal" :title="selectedReview ? selectedReview.title || 'Detalle de reseña' : 'Detalle de reseña'" max-width="980px" @close="closeReviewModal">
      <template v-if="selectedReview">
        <div class="review-detail-grid">
          <div>
            <AdminCard title="Contenido" icon="fas fa-message">
              <div class="review-customer review-customer--detail">
                <img :src="avatarUrl(selectedReview.customer)" :alt="selectedReview.customer.name" @error="onAvatarError($event, selectedReview.customer.image)">
                <div>
                  <strong>{{ selectedReview.customer.name }}</strong>
                  <span>{{ selectedReview.customer.email || `ID ${selectedReview.user_id}` }}</span>
                </div>
              </div>
              <div class="review-card__rating review-card__rating--detail" v-html="renderStars(selectedReview.rating)"></div>
              <p class="review-detail-title">{{ selectedReview.title || 'Sin título' }}</p>
              <p class="review-detail-comment">{{ selectedReview.comment || 'Sin comentario' }}</p>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Moderación" icon="fas fa-shield-halved">
              <div class="summary-stack">
                <div class="summary-row"><span>Producto</span><strong>{{ selectedReview.product_name }}</strong></div>
                <div class="summary-row"><span>Estado</span><strong>{{ reviewStatusLabel(selectedReview.status) }}</strong></div>
                <div class="summary-row"><span>Verificación</span><strong>{{ selectedReview.is_verified ? 'Verificada' : 'Pendiente' }}</strong></div>
                <div class="summary-row"><span>Fecha</span><strong>{{ formatDateTime(selectedReview.created_at) }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Acciones" icon="fas fa-bolt" style="margin-top: 1.2rem;">
              <div class="modal-actions-stack">
                <button class="btn btn-primary" type="button" @click="confirmReviewStatus(selectedReview, 'approved')">
                  <i class="fas fa-check"></i>
                  Publicar
                </button>
                <button class="btn btn-secondary" type="button" @click="confirmReviewStatus(selectedReview, 'pending')">
                  <i class="fas fa-rotate-left"></i>
                  Enviar a revisión
                </button>
                <button class="btn btn-secondary" type="button" @click="toggleReviewVerified(selectedReview)">
                  <i class="fas fa-badge-check"></i>
                  {{ selectedReview.is_verified ? 'Quitar verificación' : 'Marcar verificada' }}
                </button>
                <button class="btn btn-danger" type="button" @click="deleteReview(selectedReview)">
                  <i class="fas fa-trash"></i>
                  Eliminar reseña
                </button>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeReviewModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { loadAdminCustomerProfiles, resolveAdminCustomerProfile } from '../composables/useAdminCustomerProfiles'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const showAdvanced = ref(true)
const showDetailModal = ref(false)
const reviews = ref([])
const customerProfiles = ref({})
const selectedReviewId = ref(null)
const filters = ref({
  search: '',
  status: 'all',
  rating: '',
  verified: 'all',
})

const selectedReview = computed(() => reviews.value.find((review) => review.id === selectedReviewId.value) || null)

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.value.search.trim()) count++
  if (filters.value.status !== 'all') count++
  if (filters.value.rating) count++
  if (filters.value.verified !== 'all') count++
  return count
})

const ratingDistribution = computed(() => {
  const total = reviews.value.length || 1
  return [5, 4, 3, 2, 1].map((rating) => {
    const count = reviews.value.filter((review) => review.rating === rating).length
    return {
      rating,
      count,
      share: reviews.value.length ? (count / total) * 100 : 0,
    }
  })
})

const highlightReviews = computed(() => reviews.value.slice(0, 6))

const reviewStats = computed(() => {
  const total = reviews.value.length
  const approved = reviews.value.filter((review) => review.status === 'approved').length
  const pending = total - approved
  const verified = reviews.value.filter((review) => review.is_verified).length
  const average = total > 0 ? (reviews.value.reduce((sum, review) => sum + review.rating, 0) / total).toFixed(1) : '0.0'

  return [
    { key: 'pending', label: 'Pendientes', value: String(pending), icon: 'fas fa-clock', color: 'warning' },
    { key: 'approved', label: 'Publicadas', value: String(approved), icon: 'fas fa-check-circle', color: 'success' },
    { key: 'average', label: 'Rating promedio', value: average, icon: 'fas fa-star', color: 'info' },
    { key: 'verified', label: 'Verificadas', value: String(verified), icon: 'fas fa-badge-check', color: 'primary' },
    { key: 'total', label: 'Total reseñas', value: String(total), icon: 'fas fa-comments', color: 'primary' },
  ]
})

function normalizeReviewStatus(review) {
  if (review.status) {
    return review.status
  }

  return review.is_approved ? 'approved' : 'pending'
}

function normalizeReview(review) {
  const profile = resolveAdminCustomerProfile(customerProfiles.value, review.user_id)

  return {
    ...review,
    id: Number(review.id),
    user_id: String(review.user_id || ''),
    rating: Number(review.rating || 0),
    title: review.title || '',
    comment: review.comment || review.body || '',
    is_verified: Boolean(review.is_verified),
    is_approved: Boolean(review.is_approved),
    status: normalizeReviewStatus(review),
    product_name: review.product_name || review.product?.name || 'Producto',
    created_at: review.created_at || null,
    customer: {
      id: String(review.user_id || ''),
      name: profile?.name || `Cliente ${review.user_id || ''}`,
      email: profile?.email || '',
      image: profile?.image || '',
    },
  }
}

function avatarUrl(customer) {
  return resolveMediaUrl(customer?.image, 'avatar')
}

function onAvatarError(event, originalPath) {
  handleMediaError(event, originalPath, 'avatar')
}

function reviewStatusLabel(status) {
  return status === 'approved' ? 'Publicada' : 'Pendiente'
}

function formatDate(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleDateString('es-CO')
}

function formatDateTime(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
}

function renderStars(rating) {
  return Array.from({ length: 5 }, (_, index) => {
    const filled = index < rating
    return `<i class="${filled ? 'fas' : 'far'} fa-star"></i>`
  }).join('')
}

function clearSearch() {
  filters.value.search = ''
  loadReviews()
}

function clearAllFilters() {
  filters.value = {
    search: '',
    status: 'all',
    rating: '',
    verified: 'all',
  }
  loadReviews()
}

let debounceTimer = null
function debouncedLoadReviews() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadReviews()
  }, 350)
}

async function loadReviews() {
  loading.value = true

  try {
    const params = {}
    if (filters.value.search.trim()) params.search = filters.value.search.trim()
    if (filters.value.status !== 'all') params.status = filters.value.status
    if (filters.value.rating) params.rating = filters.value.rating
    if (filters.value.verified !== 'all') params.verified = String(filters.value.verified === 'verified')

    const response = await catalogHttp.get('/admin/reviews', { params })
    const payload = response.data?.data || response.data || []
    const rows = Array.isArray(payload) ? payload : (payload.data || [])
    customerProfiles.value = await loadAdminCustomerProfiles(rows.map((row) => row.user_id).filter(Boolean))
    reviews.value = rows.map(normalizeReview)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando reseñas' })
  } finally {
    loading.value = false
  }
}

function openReviewModal(review) {
  selectedReviewId.value = review.id
  showDetailModal.value = true
}

function closeReviewModal() {
  showDetailModal.value = false
}

function confirmReviewStatus(review, status) {
  showAlert({
    type: 'warning',
    title: 'Actualizar reseña',
    message: status === 'approved'
      ? `¿Deseas publicar la reseña de ${review.customer.name}?`
      : `¿Deseas devolver la reseña de ${review.customer.name} a revisión?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: status === 'approved' ? 'Publicar' : 'Enviar a revisión',
        style: 'primary',
        callback: async () => {
          try {
            await catalogHttp.patch(`/admin/reviews/${review.id}`, { status })
            showSnackbar({ type: 'success', message: 'Reseña actualizada' })
            await loadReviews()
          } catch {
            showSnackbar({ type: 'error', message: 'Error actualizando la reseña' })
          }
        },
      },
    ],
  })
}

function toggleReviewVerified(review) {
  const targetValue = !review.is_verified

  showAlert({
    type: 'warning',
    title: targetValue ? 'Marcar compra verificada' : 'Quitar verificación',
    message: targetValue
      ? `¿Deseas marcar como verificada la reseña de ${review.customer.name}?`
      : `¿Deseas quitar la verificación de la reseña de ${review.customer.name}?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: targetValue ? 'Verificar' : 'Quitar',
        style: 'primary',
        callback: async () => {
          try {
            await catalogHttp.patch(`/admin/reviews/${review.id}`, { is_verified: targetValue })
            showSnackbar({ type: 'success', message: 'Verificación actualizada' })
            await loadReviews()
          } catch {
            showSnackbar({ type: 'error', message: 'Error actualizando la verificación' })
          }
        },
      },
    ],
  })
}

function deleteReview(review) {
  showAlert({
    type: 'warning',
    title: 'Eliminar reseña',
    message: `¿Deseas eliminar la reseña de ${review.customer.name}? Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'primary',
        callback: async () => {
          try {
            await catalogHttp.delete(`/admin/reviews/${review.id}`)
            showSnackbar({ type: 'success', message: 'Reseña eliminada' })
            if (selectedReviewId.value === review.id) {
              closeReviewModal()
            }
            await loadReviews()
          } catch {
            showSnackbar({ type: 'error', message: 'Error eliminando la reseña' })
          }
        },
      },
    ],
  })
}

function exportReviews() {
  if (reviews.value.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay reseñas para exportar' })
    return
  }

  const rows = [['Cliente', 'Producto', 'Rating', 'Estado', 'Verificada', 'Fecha', 'Comentario']]
  reviews.value.forEach((review) => {
    rows.push([
      `"${review.customer.name.replace(/"/g, '""')}"`,
      `"${review.product_name.replace(/"/g, '""')}"`,
      review.rating,
      `"${reviewStatusLabel(review.status)}"`,
      `"${review.is_verified ? 'Si' : 'No'}"`,
      `"${formatDateTime(review.created_at)}"`,
      `"${(review.comment || '').replace(/"/g, '""')}"`,
    ])
  })

  const csv = rows.map((row) => row.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'resenas.csv'
  link.click()
  URL.revokeObjectURL(url)
  showSnackbar({ type: 'success', message: 'Reseñas exportadas correctamente' })
}

onMounted(loadReviews)
</script>

<style scoped>
.filters-card {
  margin-bottom: 2rem;
  border: 1px solid #d9e8f4;
  border-radius: 26px;
  box-shadow: 0 14px 32px rgba(15, 55, 96, 0.08);
  overflow: hidden;
}

.filters-header,
.results-summary,
.filters-actions-bar,
.review-card__header,
.review-card__footer,
.review-customer,
.review-badges,
.results-info,
.filters-buttons,
.active-filters,
.entity-actions,
.rating-label,
.highlight-item,
.highlight-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filters-header,
.results-summary,
.filters-actions-bar,
.review-card__header,
.review-card__footer,
.highlight-item {
  justify-content: space-between;
}

.filters-header {
  width: 100%;
  padding: 1.75rem 2rem;
  border-bottom: 1px solid #edf3f8;
}

.filters-title {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.filters-title i {
  width: 3rem;
  height: 3rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  background: #eef7ff;
  color: #0077b6;
}

.filters-title h3,
.results-summary p,
.review-card__content h3,
.review-detail-title {
  margin: 0;
}

.filters-toggle {
  width: 3.25rem;
  height: 3.25rem;
  border: 1px solid #cfe2f2;
  background: #fff;
  color: #45617d;
  border-radius: 1.1rem;
  cursor: pointer;
}

.filters-toggle.collapsed {
  transform: rotate(180deg);
}

.search-bar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 1rem;
  padding: 1.75rem 2rem;
}

.search-input-wrapper {
  position: relative;
}

.search-input,
.filter-group select {
  width: 100%;
  border: 1px solid #cfe2f2;
  border-radius: 1.4rem;
  color: #24364b;
}

.search-input {
  height: 4.25rem;
  padding: 0 3.25rem 0 3.25rem;
  font-size: 1.08rem;
}

.filter-group select {
  height: 3.3rem;
  padding: 0 0.95rem;
  border-radius: 1rem;
}

.search-icon {
  position: absolute;
  left: 1.15rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6a96cf;
}

.search-clear {
  position: absolute;
  right: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #90a4b7;
  cursor: pointer;
}

.search-submit-btn,
.btn.btn-icon {
  min-height: 3.15rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  padding: 0 1.2rem;
  border-radius: 1rem;
  font-weight: 700;
  cursor: pointer;
}

.search-submit-btn {
  min-width: 9.5rem;
  height: 4.25rem;
  border: 1px solid #8bc7f0;
  background: #f3fbff;
  color: #0077b6;
}

.filters-advanced {
  padding: 0 2rem 2rem;
}

.filters-row {
  display: grid;
  gap: 1rem;
}

.filters-row--reviews {
  grid-template-columns: repeat(3, minmax(180px, 1fr));
}

.filter-group label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.55rem;
  font-size: 0.95rem;
  color: #4f657b;
  font-weight: 600;
}

.filters-actions-bar {
  margin-top: 1.5rem;
  padding-top: 1.3rem;
  border-top: 1px solid #edf2f7;
}

.active-filters,
.btn-clear-filters {
  font-size: 0.95rem;
  color: #4f657b;
  font-weight: 600;
}

.btn-clear-filters {
  border: none;
  background: transparent;
  color: #0077b6;
  cursor: pointer;
}

.insights-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.rating-distribution,
.summary-stack,
.modal-actions-stack {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.rating-row {
  display: grid;
  grid-template-columns: 10rem minmax(0, 1fr) 4rem;
  gap: 1rem;
  align-items: center;
}

.rating-bar-track {
  width: 100%;
  height: 0.85rem;
  border-radius: 999px;
  background: #edf3f8;
  overflow: hidden;
}

.rating-bar-fill {
  height: 100%;
  background: #0077b6;
  border-radius: inherit;
}

.highlights-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.highlight-item {
  width: 100%;
  padding: 1rem 1.1rem;
  border: 1px solid #e2edf5;
  border-radius: 1rem;
  background: #fff;
  cursor: pointer;
  text-align: left;
}

.highlight-item span,
.highlight-item small {
  color: var(--admin-text-light);
}

.results-summary {
  margin-bottom: 1.25rem;
  padding: 1.25rem 1.6rem;
  background: #fff;
  border: 1px solid #d9e8f4;
  border-radius: 1.65rem;
  box-shadow: 0 14px 28px rgba(15, 55, 96, 0.08);
}

.results-summary p {
  color: #0077b6;
  font-size: 1.12rem;
  font-weight: 700;
}

.btn.btn-icon {
  border: 1px solid #cde0ef;
  background: #fff;
  color: #0e5f99;
}

.cards-loading {
  padding: 1.5rem;
}

.reviews-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.1rem;
  padding: 1.5rem;
}

.review-card--admin {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  border: 1px solid #dfe9f2;
  border-radius: 1.4rem;
  padding: 1.15rem;
  background: #fff;
}

.review-card--admin:hover {
  box-shadow: 0 14px 24px rgba(15, 55, 96, 0.08);
}

.review-customer img {
  width: 4rem;
  height: 4rem;
  border-radius: 50%;
  object-fit: cover;
  background: #eef3f7;
}

.review-customer div,
.review-customer--detail div {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.review-customer span,
.review-card__footer small,
.summary-row span,
.review-detail-comment {
  color: var(--admin-text-light);
}

.review-card__content {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  cursor: pointer;
}

.review-card__content p,
.review-detail-comment {
  margin: 0;
  line-height: 1.55;
}

.review-card__rating,
.stars-inline {
  display: inline-flex;
  gap: 0.2rem;
  color: #f2ab00;
}

.review-card__rating--detail {
  margin-top: 1rem;
}

.review-detail-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 1.5rem;
}

.review-customer--detail {
  align-items: flex-start;
}

.review-customer--detail img {
  width: 5rem;
  height: 5rem;
}

.review-detail-title {
  font-size: 1.35rem;
  font-weight: 700;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.detail-empty {
  color: var(--admin-text-light);
  padding: 0.6rem 0;
}

@media (max-width: 980px) {
  .filters-row--reviews,
  .reviews-grid,
  .insights-grid,
  .review-detail-grid,
  .search-bar {
    grid-template-columns: 1fr;
  }

  .filters-actions-bar,
  .results-summary,
  .review-card__header,
  .review-card__footer,
  .highlight-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .rating-row {
    grid-template-columns: 1fr;
  }
}
</style>
