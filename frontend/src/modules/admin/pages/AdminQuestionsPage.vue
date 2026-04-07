<template>
  <div class="admin-questions-page">
    <AdminPageHeader
      icon="fas fa-circle-question"
      title="Preguntas"
      subtitle="Gestiona preguntas de productos con el flujo de detalle, respuesta y moderación del legacy sobre microservicios."
      :breadcrumbs="[{ label: 'Preguntas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="questionStats" />

    <!-- Filtros de preguntas -->
    <AdminFilterCard
      icon="fas fa-filter"
      title="Bandeja de preguntas"
      placeholder="Buscar por pregunta o producto..."
      :modelValue="filters.search"
      @update:modelValue="filters.search = $event; debouncedLoadQuestions()"
      @search="loadQuestions"
    >
      <template #advanced>
        <div class="filters-row filters-row--questions">
          <div class="filter-group">
            <label for="question-status"><i class="fas fa-comments"></i> Estado</label>
            <select id="question-status" v-model="filters.answered" class="form-control" @change="loadQuestions">
              <option value="all">Todas</option>
              <option value="pending">Sin responder</option>
              <option value="answered">Respondidas</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__count">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <button type="button" class="admin-filters__clear" @click="clearAllFilters">
            <i class="fas fa-times-circle"></i>
            Limpiar todo
          </button>
        </div>
      </template>
    </AdminFilterCard>

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

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${questions.length} preguntas`">
      <template #actions>
        <button class="btn btn-icon" type="button" @click="exportQuestions">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
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
            <tr v-for="question in questions" :key="question.id">
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
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

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
/* --- Layout de insights --- */
.insights-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

/* --- Pills de estado --- */
.status-split {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.status-pill {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.1rem;
  border-radius: var(--admin-radius-md);
}

.status-pill--answered {
  background: var(--admin-success-bg, #edf8f0);
  color: var(--admin-success-dark, #1f7a34);
}

.status-pill--pending {
  background: var(--admin-warning-bg, #fff7e8);
  color: var(--admin-warning-dark, #946200);
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

/* --- Fila de filtros --- */
.filters-row--questions {
  display: grid;
  grid-template-columns: minmax(220px, 320px);
  gap: 1rem;
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
  .question-detail-grid,
  .filters-row--questions {
    grid-template-columns: 1fr;
  }

  .highlight-item,
  .question-customer-cell {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
