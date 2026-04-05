<template>
  <div>
    <AdminPageHeader
      icon="fas fa-question-circle"
      title="Preguntas"
      subtitle="Gestiona las preguntas de productos."
      :breadcrumbs="[{ label: 'Preguntas' }]"
    />

    <AdminStatsGrid :loading="loading" :count="3" :stats="questionStats" />

    <div class="filters-bar">
      <div class="filter-group"><label>Estado:</label>
        <select v-model="filterStatus"><option value="">Todas</option><option value="pending">Sin responder</option><option value="answered">Respondidas</option></select>
      </div>
    </div>

    <AdminCard :flush="true">
      <template v-if="loading">
        <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
          <div v-for="i in 3" :key="i" class="admin-shimmer shimmer-rect" style="height: 8rem;"></div>
        </div>
      </template>
      <AdminEmptyState v-else-if="filtered.length === 0" icon="fas fa-question-circle" title="Sin preguntas" description="No se encontraron preguntas." />
      <div v-else style="padding: 1.5rem;">
        <div v-for="q in filtered" :key="q.id" class="review-card">
          <div class="review-header">
            <strong>{{ q.question }}</strong>
            <span class="status-badge" :class="q.answer ? 'approved' : 'pending'">{{ q.answer ? 'Respondida' : 'Pendiente' }}</span>
          </div>
          <p class="review-meta">{{ q.user_name || 'Cliente' }} — {{ q.product_name || '' }}</p>
          <div v-if="q.answer" style="background:#f1f8e9;padding:0.75rem;border-radius:6px;margin-top:0.5rem;">
            <strong style="color:#2e7d32;"><i class="fas fa-reply"></i> Respuesta:</strong>
            <p style="margin:0.25rem 0 0 0;">{{ q.answer }}</p>
          </div>
          <div v-if="!q.answer" style="margin-top:0.75rem;display:flex;gap:0.5rem;">
            <input v-model="q._draft" class="form-input" placeholder="Escribe una respuesta..." style="flex:1;" />
            <button class="btn btn-primary" :disabled="!q._draft" @click="answerQuestion(q)"><i class="fas fa-reply"></i> Responder</button>
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
const questions = ref([]), loading = ref(true), filterStatus = ref('')

const questionStats = computed(() => [
  { key: 'total', label: 'Total', value: String(questions.value.length), icon: 'fas fa-question-circle', color: 'primary' },
  { key: 'unanswered', label: 'Sin responder', value: String(questions.value.filter(q => !q.answer).length), icon: 'fas fa-clock', color: 'warning' },
  { key: 'answered', label: 'Respondidas', value: String(questions.value.filter(q => q.answer).length), icon: 'fas fa-check-circle', color: 'success' },
])

const filtered = computed(() => {
  if (!filterStatus.value) return questions.value
  return filterStatus.value === 'answered'
    ? questions.value.filter(q => q.answer)
    : questions.value.filter(q => !q.answer)
})

async function loadQuestions() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/questions')
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    questions.value = rows.map((row) => ({
      ...row,
      question: row.question || row.question_text || 'Sin pregunta',
      answer: row.answer || row.answer_text || null,
      _draft: '',
    }))
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando preguntas' })
  } finally {
    loading.value = false
  }
}

async function answerQuestion(q) {
  if (!q._draft?.trim()) return

  try {
    await catalogHttp.post(`/admin/questions/${q.id}/answer`, { answer_text: q._draft.trim() })
    showSnackbar({ type: 'success', message: 'Respuesta enviada' })
    await loadQuestions()
  } catch {
    showSnackbar({ type: 'error', message: 'Error enviando respuesta' })
  }
}

onMounted(loadQuestions)
</script>
