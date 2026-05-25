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
      </template>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeQuestionModal">Cerrar</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { loadAdminCustomerProfiles, resolveAdminCustomerProfile } from '../composables/useAdminCustomerProfiles'
import { useAdminDataExport } from '../composables/useAdminDataExport'
import { useAdminPagination } from '../composables/useAdminPagination'
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

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()
const { exportData, exportingFormat } = useAdminDataExport()

const loading = ref(true)
const showDetailModal = ref(false)
const questions = ref([])
const customerProfiles = ref({})
const selectedQuestionId = ref(null)
const filters = ref({
  search: '',
  answered: 'all',
})

const answerForm = reactive({
  answer: '',
})

const answerErrors = reactive({
  answer: '',
})

const selectedQuestion = computed(() => questions.value.find((question) => question.id === selectedQuestionId.value) || null)
const answeredCount = computed(() => questions.value.filter((question) => question.answer_count > 0).length)
const pendingCount = computed(() => questions.value.length - answeredCount.value)
const recentQuestions = computed(() => questions.value.slice(0, 6))

// Controla si el doughnut de estado debe renderizarse o mostrar el estado vacío compartido.
const hasQuestionStatusChartData = computed(() => questions.value.length > 0)

// Resume el estado operativo para reutilizar el mismo orden en etiquetas, tooltip y leyenda del doughnut.
const questionStatusChartLabels = computed(() => ['Respondidas', 'Pendientes'])

// Construye el dataset del doughnut a partir de los conteos ya calculados por la vista.
const questionStatusChartDatasets = computed(() => ([
  {
    label: 'Preguntas',
    data: [answeredCount.value, pendingCount.value],
    backgroundColor: ['#2fb35f', '#f4b23d'],
    borderColor: ['#ffffff', '#ffffff'],
    borderWidth: 2,
    hoverOffset: 6,
  },
]))

// Reutiliza los conteos calculados para que el tooltip del doughnut muestre cantidad y porcentaje real.
const questionStatusChartOptions = computed(() => ({
  cutout: '68%',
  radius: '88%',
  layout: {
    padding: {
      top: 10,
      right: 8,
      left: 8,
      bottom: 0,
    },
  },
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: '#42526b',
        boxWidth: 12,
        boxHeight: 12,
        usePointStyle: true,
        padding: 14,
      },
    },
    tooltip: {
      callbacks: {
        label: (context) => {
          const total = questions.value.length || 1
          const percentage = Math.round((Number(context.raw || 0) / total) * 100)
          return `${context.label}: ${context.raw} (${percentage}%)`
        },
      },
    },
  },
}))

const questionStats = computed(() => [
  { key: 'total', label: 'Total', value: String(questions.value.length), icon: 'fas fa-comments', color: 'primary' },
  { key: 'answered', label: 'Respondidas', value: String(answeredCount.value), icon: 'fas fa-circle-check', color: 'success' },
  { key: 'pending', label: 'Pendientes', value: String(pendingCount.value), icon: 'fas fa-clock', color: 'warning' },
  { key: 'rate', label: 'Tasa de respuesta', value: `${questions.value.length ? Math.round((answeredCount.value / questions.value.length) * 100) : 0}%`, icon: 'fas fa-chart-line', color: 'info' },
])

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.value.search.trim()) count++
  if (filters.value.answered !== 'all') count++
  return count
})

const pagination = useAdminPagination(questions, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

function normalizeAnswer(answer) {
  const profile = resolveAdminCustomerProfile(customerProfiles.value, answer.user_id)

  return {
    ...answer,
    id: Number(answer.id),
    answer: answer.answer || answer.answer_text || '',
    created_at: answer.created_at || null,
    author: {
      id: String(answer.user_id || ''),
      name: profile?.name || (answer.is_seller ? 'Administrador' : `Usuario ${answer.user_id || ''}`),
      image: profile?.image || '',
    },
  }
}

function normalizeQuestion(question) {
  const profile = resolveAdminCustomerProfile(customerProfiles.value, question.user_id)
  const answers = Array.isArray(question.answers) ? question.answers.map(normalizeAnswer) : []

  return {
    ...question,
    id: Number(question.id),
    user_id: String(question.user_id || ''),
    question: question.question || 'Sin pregunta',
    product_name: question.product_name || 'Producto',
    created_at: question.created_at || null,
    answers,
    answer_count: Number(question.answer_count ?? answers.length),
    customer: {
      id: String(question.user_id || ''),
      name: profile?.name || `Cliente ${question.user_id || ''}`,
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

function clearAllFilters() {
  filters.value = {
    search: '',
    answered: 'all',
  }
  loadQuestions()
}

let debounceTimer = null
function debouncedLoadQuestions() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadQuestions()
  }, 350)
}

async function loadQuestions() {
  loading.value = true

  try {
    const params = {}
    if (filters.value.search.trim()) params.search = filters.value.search.trim()
    if (filters.value.answered !== 'all') params.answered = String(filters.value.answered === 'answered')

    const response = await catalogHttp.get('/admin/questions', { params })
    const payload = response.data?.data || response.data || []
    const rows = Array.isArray(payload) ? payload : (payload.data || [])
    const userIds = []

    rows.forEach((row) => {
      if (row.user_id) userIds.push(row.user_id)
      if (Array.isArray(row.answers)) {
        row.answers.forEach((answer) => {
          if (answer.user_id) userIds.push(answer.user_id)
        })
      }
    })

    customerProfiles.value = await loadAdminCustomerProfiles(userIds)
    questions.value = rows.map(normalizeQuestion)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando preguntas' })
  } finally {
    loading.value = false
  }
}

function openQuestionModal(question) {
  selectedQuestionId.value = question.id
  answerForm.answer = ''
  answerErrors.answer = ''
  showDetailModal.value = true
}

function closeQuestionModal() {
  showDetailModal.value = false
}

function validateAnswerField() {
  answerErrors.answer = answerForm.answer.trim().length >= 3 ? '' : 'La respuesta debe tener al menos 3 caracteres.'
}

async function submitAnswer() {
  if (!selectedQuestion.value) {
    return
  }

  validateAnswerField()
  if (answerErrors.answer) {
    return
  }

  try {
    await catalogHttp.post(`/admin/questions/${selectedQuestion.value.id}/answer`, { answer: answerForm.answer.trim() })
    showSnackbar({ type: 'success', message: 'Respuesta enviada' })
    answerForm.answer = ''
    await loadQuestions()
  } catch {
    showSnackbar({ type: 'error', message: 'Error enviando respuesta' })
  }
}

function deleteQuestion(question) {
  showAlert({
    type: 'warning',
    title: 'Eliminar pregunta',
    message: `¿Deseas eliminar la pregunta de ${question.customer.name}? También se eliminarán sus respuestas.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'primary',
        callback: async () => {
          try {
            await catalogHttp.delete(`/admin/questions/${question.id}`)
            showSnackbar({ type: 'success', message: 'Pregunta eliminada' })
            if (selectedQuestionId.value === question.id) {
              closeQuestionModal()
            }
            await loadQuestions()
          } catch {
            showSnackbar({ type: 'error', message: 'Error eliminando la pregunta' })
          }
        },
      },
    ],
  })
}

// Usa la misma pregunta normalizada de la bandeja para que PDF y Excel no diverjan.
function buildQuestionExportColumns() {
  return [
    {
      header: 'Avatar',
      includeInExcel: false,
      pdfImage: (question) => avatarUrl(question.customer),
      fallbackType: 'avatar',
      pdfWidth: 18,
      pdfImageSize: 11,
    },
    { header: 'Cliente', value: (question) => question.customer.name },
    { header: 'Producto', value: (question) => question.product_name },
    { header: 'Pregunta', value: (question) => question.question, width: 34 },
    { header: 'Estado', value: (question) => (question.answer_count > 0 ? 'Respondida' : 'Pendiente'), width: 14 },
    { header: 'Respuestas', value: (question) => Number(question.answer_count || 0), excelType: 'number', align: 'center', width: 12 },
    { header: 'Fecha', value: (question) => formatDateTime(question.created_at), width: 18 },
  ]
}

// Exporta la bandeja visible y deja el formato al componente/composable compartido.
function exportQuestions(format) {
  return exportData({
    format,
    fileBaseName: 'preguntas-admin',
    sheetName: 'Preguntas',
    title: 'Preguntas',
    subtitle: 'Resumen exportado desde la bandeja administrativa de preguntas.',
    columns: buildQuestionExportColumns(),
    rows: questions.value,
    landscape: true,
    emptyMessage: 'No hay preguntas para exportar.',
  })
}

onMounted(loadQuestions)
</script>

<style scoped>
/* --- Layout de insights --- */
.insights-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

/* --- Highlights recientes --- */
.highlights-list {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.highlight-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  width: 100%;
  padding: 1rem 1.1rem;
  border: 1px solid var(--admin-border-light);
  border-radius: var(--admin-radius-md);
  background: #fff;
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

.highlight-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.highlight-item span,
.highlight-item small,
.question-detail-text,
.timeline-item small {
  color: var(--admin-text-light);
}

/* --- Celda de cliente con avatar --- */
.question-customer-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.question-customer-cell img {
  width: 3.6rem;
  height: 3.6rem;
  border-radius: 50%;
  object-fit: cover;
  background: var(--admin-bg-soft);
}

.question-customer-cell--detail img {
  width: 4.8rem;
  height: 4.8rem;
}

/* --- Grid de detalle --- */
.question-detail-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 1.5rem;
}

.question-detail-text {
  margin-top: 1rem;
  line-height: 1.6;
}

/* --- Timeline de respuestas --- */
.timeline-list {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  list-style: none;
  margin: 0;
  padding: 0;
}

.timeline-item {
  padding: 1rem 1.1rem;
  border: 1px solid var(--admin-border-light);
  border-radius: var(--admin-radius-md);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

/* --- Acciones del modal --- */
.modal-actions-stack {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.detail-empty {
  color: var(--admin-text-light);
  padding: 0.6rem 0;
}

/* --- Responsive --- */
@media (max-width: 980px) {
  .insights-grid,
  .question-detail-grid {
    grid-template-columns: 1fr;
  }

  .highlight-item,
  .question-customer-cell {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
