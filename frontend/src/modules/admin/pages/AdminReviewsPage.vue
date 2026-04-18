<template>
  <div class="admin-reviews-page">
    <AdminPageHeader
      icon="fas fa-star"
      title="Reseñas"
      subtitle="Modera la reputación y la visibilidad del catálogo con la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Reseñas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="5" :stats="reviewStats" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Bandeja de moderación"
      placeholder="Buscar por título, comentario o producto..."
      @search="loadReviews"
      @update:model-value="debouncedLoadReviews"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--3">
          <div class="admin-filters__group">
            <label for="review-status"><i class="fas fa-flag"></i> Estado</label>
            <select id="review-status" v-model="filters.status" @change="loadReviews">
              <option value="all">Todas</option>
              <option value="pending">Pendientes</option>
              <option value="approved">Publicadas</option>
            </select>
          </div>

          <div class="admin-filters__group">
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

          <div class="admin-filters__group">
            <label for="review-verified"><i class="fas fa-circle-check"></i> Compra verificada</label>
            <select id="review-verified" v-model="filters.verified" @change="loadReviews">
              <option value="all">Todas</option>
              <option value="verified">Solo verificadas</option>
              <option value="unverified">Sin verificar</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearAllFilters">
              <i class="fas fa-times-circle"></i> Limpiar todo
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

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
            <div class="highlight-item__info">
              <strong class="highlight-item__name">{{ review.customer.name }}</strong>
              <span class="highlight-item__product">{{ review.product_name }}</span>
            </div>
            <div class="highlight-meta">
              <span class="stars-inline" v-html="renderStars(review.rating)"></span>
              <small>{{ formatDate(review.created_at) }}</small>
            </div>
          </button>
        </div>
      </AdminCard>
    </section>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} reseñas`">
      <template #actions>
        <button class="results-action-btn results-action-btn--neutral" type="button" @click="exportReviews">
          <span class="results-action-btn__icon"><i class="fas fa-file-export"></i></span>
          Exportar
        </button>
      </template>
    </AdminResultsBar>

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
        <article v-for="review in pagination.paginatedItems" :key="review.id" class="review-card review-card--admin">
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
            <div class="admin-entity-actions">
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
                <i class="fas fa-circle-check"></i>
              </button>
              <button class="action-btn delete" type="button" title="Eliminar reseña" @click="deleteReview(review)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </article>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

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
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Producto</span><strong>{{ selectedReview.product_name }}</strong></div>
                <div class="admin-detail-summary__row"><span>Estado</span><strong>{{ reviewStatusLabel(selectedReview.status) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Verificación</span><strong>{{ selectedReview.is_verified ? 'Verificada' : 'Pendiente' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Fecha</span><strong>{{ formatDateTime(selectedReview.created_at) }}</strong></div>
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
                  <i class="fas fa-circle-check"></i>
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

const pagination = useAdminPagination(reviews, {
  initialPageSize: 8,
  pageSizeOptions: [8, 16, 24],
})

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
    { key: 'verified', label: 'Verificadas', value: String(verified), icon: 'fas fa-circle-check', color: 'primary' },
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
/* Estilos específicos de Reseñas — los comunes están en admin.css */

.review-card__header,
.review-card__footer,
.review-customer,
.review-badges,
.rating-label,
.highlight-item,
.highlight-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.review-card__header,
.review-card__footer,
.highlight-item {
  justify-content: space-between;
}

.review-card__content h3,
.review-detail-title {
  margin: 0;
}

.insights-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.rating-distribution,
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
  border-radius: var(--admin-radius-pill);
  background: var(--admin-bg-dark);
  overflow: hidden;
}

.rating-bar-fill {
  height: 100%;
  background: var(--admin-primary);
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
  border: 1px solid var(--admin-border-soft);
  border-radius: var(--admin-radius-lg);
  background: var(--admin-bg);
  cursor: pointer;
  text-align: left;
}

.highlight-item__info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.highlight-item__name {
  color: var(--admin-text-heading);
  font-size: 1.28rem;
  font-weight: 700;
}

.highlight-item__product {
  color: var(--admin-text-light);
  font-size: 1.18rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 22rem;
}

.highlight-item span,
.highlight-item small {
  color: var(--admin-text-light);
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
  border: 1px solid var(--admin-border-soft);
  border-radius: var(--admin-radius-xl);
  padding: 1.15rem;
  background: var(--admin-bg);
}

.review-card--admin:hover {
  box-shadow: var(--admin-shadow-hover);
}

.review-customer img {
  width: 4rem;
  height: 4rem;
  border-radius: 50%;
  object-fit: cover;
  background: var(--admin-bg-dark);
}

.review-customer div,
.review-customer--detail div {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.review-customer span,
.review-card__footer small,
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
  color: var(--admin-star);
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

.detail-empty {
  color: var(--admin-text-light);
  padding: 0.6rem 0;
}

@media (max-width: 980px) {
  .reviews-grid,
  .insights-grid,
  .review-detail-grid {
    grid-template-columns: 1fr;
  }

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
