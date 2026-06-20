<template>
  <div class="admin-reviews-page">
    <AdminPageHeader
      icon="fas fa-star"
      title="Reseñas"
      subtitle="Modera la reputación y la visibilidad del catálogo con la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Reseñas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="5" :stats="reviewStats" />

    <section class="insights-grid">
      <AdminCard title="Distribución de rating" icon="fas fa-chart-column">
        <AdminChartPanel
          :has-data="hasRatingChartData"
          type="bar"
          :labels="ratingChartLabels"
          :datasets="ratingChartDatasets"
          :options="ratingChartOptions"
          empty-icon="fas fa-chart-column"
          empty-title="Sin datos para graficar"
          empty-description="No hay reseñas suficientes para dibujar la distribución de rating."
          :height="220"
        />
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

    <AdminFilterCard
      v-model="filters.search"
      class="admin-insights-search-panel"
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} reseñas`">
      <template #actions>
        <AdminExportActions
          tone="results"
          :disabled="reviews.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportReviews('excel')"
          @pdf="exportReviews('pdf')"
        />
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
        <div class="admin-reviews-page admin-reviews-page--modal">
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
              <div class="modal-actions-grid">
                <button
                  class="modal-action-button modal-action-button--primary"
                  type="button"
                  @click="confirmReviewStatus(selectedReview, 'approved')"
                >
                  <i class="fas fa-check"></i>
                  <span class="modal-action-button__content">
                    <strong>Publicar</strong>
                    <small>Hace visible la reseña en el catálogo.</small>
                  </span>
                </button>
                <button
                  class="modal-action-button modal-action-button--neutral"
                  type="button"
                  @click="confirmReviewStatus(selectedReview, 'pending')"
                >
                  <i class="fas fa-rotate-left"></i>
                  <span class="modal-action-button__content">
                    <strong>Enviar a revisión</strong>
                    <small>La devuelve a moderación antes de mostrarla.</small>
                  </span>
                </button>
                <button
                  class="modal-action-button modal-action-button--soft"
                  type="button"
                  @click="toggleReviewVerified(selectedReview)"
                >
                  <i class="fas fa-circle-check"></i>
                  <span class="modal-action-button__content">
                    <strong>{{ selectedReview.is_verified ? 'Quitar verificación' : 'Marcar verificada' }}</strong>
                    <small>Actualiza la compra verificada del cliente.</small>
                  </span>
                </button>
                <button
                  class="modal-action-button modal-action-button--danger"
                  type="button"
                  @click="deleteReview(selectedReview)"
                >
                  <i class="fas fa-trash"></i>
                  <span class="modal-action-button__content">
                    <strong>Eliminar reseña</strong>
                    <small>Quita definitivamente este comentario del panel.</small>
                  </span>
                </button>
              </div>
            </AdminCard>
          </div>
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
import '../views/AdminReviewsPage.css'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAdminReviews } from '../composables/useAdminReviews'
import AdminCard from '../components/AdminCard.vue'
import AdminChartPanel from '../components/AdminChartPanel.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const {
  activeFilterCount,
  clearAllFilters,
  closeReviewModal,
  confirmReviewStatus,
  debouncedLoadReviews,
  deleteReview,
  exportReviews,
  exportingFormat,
  filters,
  formatDate,
  formatDateTime,
  hasRatingChartData,
  highlightReviews,
  loadReviews,
  loading,
  openReviewModal,
  pagination,
  ratingChartDatasets,
  ratingChartLabels,
  ratingChartOptions,
  renderStars,
  reviews,
  reviewStats,
  reviewStatusLabel,
  selectedReview,
  showDetailModal,
  toggleReviewVerified,
} = useAdminReviews()

function avatarUrl(customer) {
  return resolveMediaUrl(customer?.image, 'avatar')
}

function onAvatarError(event, originalPath) {
  handleMediaError(event, originalPath, 'avatar')
}
</script>
