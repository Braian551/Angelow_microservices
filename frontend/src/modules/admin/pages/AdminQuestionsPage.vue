<template>
  <div class="admin-questions-page">
    <AdminPageHeader
      icon="fas fa-circle-question"
      title="Preguntas"
      subtitle="Gestiona preguntas de productos con herramientas claras para revisar, responder y moderar."
      :breadcrumbs="[{ label: 'Preguntas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="questionStats" />

    <section class="insights-grid">
      <AdminCard title="Estado de preguntas" icon="fas fa-chart-pie">
        <AdminChartPanel
          :has-data="hasQuestionStatusChartData"
          type="doughnut"
          :labels="questionStatusChartLabels"
          :datasets="questionStatusChartDatasets"
          :options="questionStatusChartOptions"
          empty-icon="fas fa-chart-pie"
          empty-title="Sin preguntas registradas"
          empty-description="No hay preguntas suficientes para dibujar el estado de atención."
          :height="220"
          :max-width="360"
          :centered="true"
        />
      </AdminCard>

      <AdminCard title="Actividad reciente" icon="fas fa-clock-rotate-left">
        <div v-if="recentQuestions.length === 0" class="detail-empty">Sin actividad reciente.</div>
        <div v-else class="highlights-list">
          <button
            v-for="question in recentQuestions"
            :key="question.id"
            type="button"
            class="highlight-item"
            @click="openQuestionModal(question)"
          >
            <div class="highlight-item__info">
              <strong class="highlight-item__name">{{ question.customer.name }}</strong>
              <span class="highlight-item__product">{{ question.product_name }}</span>
            </div>
            <div class="highlight-meta">
              <span class="status-badge" :class="question.answer_count > 0 ? 'approved' : 'pending'">
                {{ question.answer_count > 0 ? 'Respondida' : 'Pendiente' }}
              </span>
              <small>{{ formatDate(question.created_at) }}</small>
            </div>
          </button>
        </div>
      </AdminCard>
    </section>

    <AdminFilterCard
      class="admin-insights-search-panel"
      icon="fas fa-filter"
      title="Bandeja de preguntas"
      placeholder="Buscar por pregunta o producto..."
      :modelValue="filters.search"
      @update:modelValue="filters.search = $event; debouncedLoadQuestions()"
      @search="loadQuestions"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--1">
          <div class="admin-filters__group">
            <label for="question-status"><i class="fas fa-comments"></i> Estado</label>
            <select id="question-status" v-model="filters.answered" @change="loadQuestions">
              <option value="all">Todas</option>
              <option value="pending">Sin responder</option>
              <option value="answered">Respondidas</option>
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
              <i class="fas fa-times-circle"></i>
              Limpiar todo
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} preguntas`">
      <template #actions>
        <AdminExportActions
          tone="results"
          :disabled="questions.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportQuestions('excel')"
          @pdf="exportQuestions('pdf')"
        />
      </template>
    </AdminResultsBar>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'pill']" />
      <AdminEmptyState
        v-else-if="questions.length === 0"
        icon="fas fa-circle-question"
        title="Sin preguntas"
        description="No se encontraron preguntas con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table questions-table">
          <thead>
            <tr>
              <th>Pregunta</th>
              <th>Producto</th>
              <th>Cliente</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="question in pagination.paginatedItems" :key="question.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ question.question }}</strong>
                  <span>{{ formatDateTime(question.created_at) }}</span>
                </div>
              </td>
              <td>{{ question.product_name }}</td>
              <td>
                <div class="question-customer-cell">
                  <img :src="avatarUrl(question.customer)" :alt="question.customer.name" @error="onAvatarError($event, question.customer.image)">
                  <div class="admin-entity-name">
                    <strong>{{ question.customer.name }}</strong>
                    <span>{{ question.customer.email || `ID ${question.user_id}` }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="question.answer_count > 0 ? 'approved' : 'pending'">
                  {{ question.answer_count > 0 ? 'Respondida' : 'Pendiente' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openQuestionModal(question)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar pregunta" @click="deleteQuestion(question)">
                    <i class="fas fa-trash"></i>
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

    <AdminModal :show="showDetailModal" :title="selectedQuestion ? `Pregunta #${selectedQuestion.id}` : 'Detalle de pregunta'" max-width="1040px" @close="closeQuestionModal">
      <template v-if="selectedQuestion">
        <div class="admin-questions-page admin-questions-page--modal">
          <div class="question-detail-grid">
          <div>
            <AdminCard title="Consulta del cliente" icon="fas fa-comment">
              <div class="question-customer-cell question-customer-cell--detail">
                <img :src="avatarUrl(selectedQuestion.customer)" :alt="selectedQuestion.customer.name" @error="onAvatarError($event, selectedQuestion.customer.image)">
                <div class="admin-entity-name">
                  <strong>{{ selectedQuestion.customer.name }}</strong>
                  <span>{{ selectedQuestion.customer.email || `ID ${selectedQuestion.user_id}` }}</span>
                </div>
              </div>
              <p class="question-detail-text">{{ selectedQuestion.question }}</p>
            </AdminCard>

            <AdminCard title="Respuestas" icon="fas fa-reply" style="margin-top: 1.2rem;">
              <div v-if="selectedQuestion.answers.length === 0" class="detail-empty">Aún no hay respuestas registradas.</div>
              <ul v-else class="timeline-list">
                <li v-for="answer in selectedQuestion.answers" :key="answer.id" class="timeline-item">
                  <strong>{{ answer.author.name }}</strong>
                  <span>{{ answer.answer }}</span>
                  <small>{{ formatDateTime(answer.created_at) }}</small>
                </li>
              </ul>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Resumen" icon="fas fa-box-open">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Producto</span><strong>{{ selectedQuestion.product_name }}</strong></div>
                <div class="admin-detail-summary__row"><span>Estado</span><strong>{{ selectedQuestion.answer_count > 0 ? 'Respondida' : 'Pendiente' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Fecha</span><strong>{{ formatDateTime(selectedQuestion.created_at) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Total respuestas</span><strong>{{ selectedQuestion.answer_count }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Responder" icon="fas fa-paper-plane" style="margin-top: 1.2rem;">
              <div class="form-group">
                <label for="question-answer">
                  Respuesta *
                  <AdminInfoTooltip text="Texto público que aparecerá como respuesta oficial a la pregunta del cliente." />
                </label>
                <textarea
                  id="question-answer"
                  v-model="answerForm.answer"
                  class="form-control"
                  rows="5"
                  :class="{ 'is-invalid': answerErrors.answer }"
                  placeholder="Escribe una respuesta útil y clara..."
                  @input="validateAnswerField"
                ></textarea>
                <p v-if="answerErrors.answer" class="form-error">{{ answerErrors.answer }}</p>
              </div>
              <div class="modal-actions-stack">
                <button class="btn btn-primary" type="button" @click="submitAnswer">
                  <i class="fas fa-paper-plane"></i>
                  Enviar respuesta
                </button>
                <button class="btn btn-danger" type="button" @click="deleteQuestion(selectedQuestion)">
                  <i class="fas fa-trash"></i>
                  Eliminar pregunta
                </button>
              </div>
            </AdminCard>
          </div>
        </div>
              </div>
      </template>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeQuestionModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import '../views/AdminQuestionsPage.css'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAdminQuestions } from '../composables/useAdminQuestions'
import AdminCard from '../components/AdminCard.vue'
import AdminChartPanel from '../components/AdminChartPanel.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const {
  activeFilterCount,
  answerErrors,
  answerForm,
  clearAllFilters,
  closeQuestionModal,
  debouncedLoadQuestions,
  deleteQuestion,
  exportQuestions,
  exportingFormat,
  filters,
  formatDate,
  formatDateTime,
  hasQuestionStatusChartData,
  loadQuestions,
  loading,
  openQuestionModal,
  pagination,
  questionStats,
  questionStatusChartDatasets,
  questionStatusChartLabels,
  questionStatusChartOptions,
  questions,
  recentQuestions,
  selectedQuestion,
  showDetailModal,
  submitAnswer,
  validateAnswerField,
} = useAdminQuestions()

function avatarUrl(customer) {
  return resolveMediaUrl(customer?.image, 'avatar')
}

function onAvatarError(event, originalPath) {
  handleMediaError(event, originalPath, 'avatar')
}
</script>
