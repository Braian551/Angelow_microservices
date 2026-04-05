<template>
  <div class="admin-questions-page">
    <AdminPageHeader
      icon="fas fa-circle-question"
      title="Preguntas"
      subtitle="Gestiona preguntas de productos con el flujo de detalle, respuesta y moderación del legacy sobre microservicios."
      :breadcrumbs="[{ label: 'Preguntas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="questionStats" />

    <AdminCard class="filters-card" :flush="true">
      <template #header>
        <div class="filters-header">
          <div class="filters-title">
            <i class="fas fa-filter"></i>
            <h3>Bandeja de preguntas</h3>
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
            placeholder="Buscar por pregunta o producto..."
            @input="debouncedLoadQuestions"
          >
          <button v-if="filters.search" type="button" class="search-clear" @click="clearSearch">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <button type="button" class="search-submit-btn" @click="loadQuestions">
          <i class="fas fa-search"></i>
          <span>Buscar</span>
        </button>
      </div>

      <div v-show="showAdvanced" class="filters-advanced">
        <div class="filters-row filters-row--questions">
          <div class="filter-group">
            <label for="question-status"><i class="fas fa-comments"></i> Estado</label>
            <select id="question-status" v-model="filters.answered" @change="loadQuestions">
              <option value="all">Todas</option>
              <option value="pending">Sin responder</option>
              <option value="answered">Respondidas</option>
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
      <AdminCard title="Estado de preguntas" icon="fas fa-chart-pie">
        <div class="status-split" v-if="questions.length">
          <div class="status-pill status-pill--answered">
            <strong>{{ answeredCount }}</strong>
            <span>Respondidas</span>
          </div>
          <div class="status-pill status-pill--pending">
            <strong>{{ pendingCount }}</strong>
            <span>Pendientes</span>
          </div>
        </div>
        <div v-else class="detail-empty">Sin preguntas registradas.</div>
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
            <div>
              <strong>{{ question.customer.name }}</strong>
              <span>{{ question.product_name }}</span>
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

    <div class="results-summary">
      <div class="results-info">
        <i class="fas fa-list"></i>
        <p>Mostrando {{ questions.length }} preguntas</p>
      </div>
      <div class="quick-actions">
        <button class="btn btn-icon" type="button" @click="exportQuestions">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
      </div>
    </div>

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
            <tr v-for="question in questions" :key="question.id">
              <td>
                <div class="entity-name-cell">
                  <strong>{{ question.question }}</strong>
                  <span>{{ formatDateTime(question.created_at) }}</span>
                </div>
              </td>
              <td>{{ question.product_name }}</td>
              <td>
                <div class="question-customer-cell">
                  <img :src="avatarUrl(question.customer)" :alt="question.customer.name" @error="onAvatarError($event, question.customer.image)">
                  <div class="entity-name-cell">
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
                <div class="entity-actions">
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

    <AdminModal :show="showDetailModal" :title="selectedQuestion ? `Pregunta #${selectedQuestion.id}` : 'Detalle de pregunta'" max-width="1040px" @close="closeQuestionModal">
      <template v-if="selectedQuestion">
        <div class="question-detail-grid">
          <div>
            <AdminCard title="Consulta del cliente" icon="fas fa-comment">
              <div class="question-customer-cell question-customer-cell--detail">
                <img :src="avatarUrl(selectedQuestion.customer)" :alt="selectedQuestion.customer.name" @error="onAvatarError($event, selectedQuestion.customer.image)">
                <div class="entity-name-cell">
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
              <div class="summary-stack">
                <div class="summary-row"><span>Producto</span><strong>{{ selectedQuestion.product_name }}</strong></div>
                <div class="summary-row"><span>Estado</span><strong>{{ selectedQuestion.answer_count > 0 ? 'Respondida' : 'Pendiente' }}</strong></div>
                <div class="summary-row"><span>Fecha</span><strong>{{ formatDateTime(selectedQuestion.created_at) }}</strong></div>
                <div class="summary-row"><span>Total respuestas</span><strong>{{ selectedQuestion.answer_count }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Responder" icon="fas fa-paper-plane" style="margin-top: 1.2rem;">
              <div class="form-group">
                <label for="question-answer">Respuesta *</label>
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

function clearSearch() {
  filters.value.search = ''
  loadQuestions()
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

function exportQuestions() {
  if (questions.value.length === 0) {
    showSnackbar({ type: 'info', message: 'No hay preguntas para exportar' })
    return
  }

  const rows = [['Cliente', 'Producto', 'Pregunta', 'Estado', 'Fecha']]
  questions.value.forEach((question) => {
    rows.push([
      `"${question.customer.name.replace(/"/g, '""')}"`,
      `"${question.product_name.replace(/"/g, '""')}"`,
      `"${question.question.replace(/"/g, '""')}"`,
      `"${question.answer_count > 0 ? 'Respondida' : 'Pendiente'}"`,
      `"${formatDateTime(question.created_at)}"`,
    ])
  })

  const csv = rows.map((row) => row.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'preguntas.csv'
  link.click()
  URL.revokeObjectURL(url)
  showSnackbar({ type: 'success', message: 'Preguntas exportadas correctamente' })
}

onMounted(loadQuestions)
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
.results-info,
.filters-buttons,
.active-filters,
.highlight-item,
.highlight-meta,
.question-customer-cell,
.entity-actions,
.entity-name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filters-header,
.results-summary,
.filters-actions-bar,
.highlight-item {
  justify-content: space-between;
}

.entity-name-cell {
  flex-direction: column;
  align-items: flex-start;
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
.question-detail-text {
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

.filters-row--questions {
  grid-template-columns: minmax(220px, 320px);
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

.status-split,
.summary-stack,
.modal-actions-stack,
.highlights-list,
.timeline-list {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.status-pill {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.1rem;
  border-radius: 1rem;
}

.status-pill--answered {
  background: #edf8f0;
  color: #1f7a34;
}

.status-pill--pending {
  background: #fff7e8;
  color: #946200;
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
.highlight-item small,
.entity-name-cell span,
.question-detail-text,
.timeline-item small {
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

.question-customer-cell img {
  width: 3.6rem;
  height: 3.6rem;
  border-radius: 50%;
  object-fit: cover;
  background: #eef3f7;
}

.question-customer-cell--detail img {
  width: 4.8rem;
  height: 4.8rem;
}

.question-detail-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 1.5rem;
}

.question-detail-text {
  margin-top: 1rem;
  line-height: 1.6;
}

.timeline-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.timeline-item {
  padding: 1rem 1.1rem;
  border: 1px solid #e2edf5;
  border-radius: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
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
  .insights-grid,
  .question-detail-grid,
  .search-bar,
  .filters-row--questions {
    grid-template-columns: 1fr;
  }

  .filters-actions-bar,
  .results-summary,
  .highlight-item,
  .question-customer-cell {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
