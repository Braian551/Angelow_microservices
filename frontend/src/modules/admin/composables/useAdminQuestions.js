import { computed, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { loadAdminCustomerProfiles, resolveAdminCustomerProfile } from './useAdminCustomerProfiles'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

/**
 * Composable para la gestión de preguntas de clientes sobre productos.
 * Administra listado, filtros, respuesta a preguntas, perfiles de clientes
 * y exportación. Reutiliza useAdminPagination y useAdminDataExport.
 */
export function useAdminQuestions() {
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
    if (filters.value.search.trim()) count += 1
    if (filters.value.answered !== 'all') count += 1
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

      // Reutiliza el composable de perfiles para enriquecer autores de preguntas y respuestas.
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

  function buildQuestionExportColumns() {
    return [
      {
        header: 'Avatar',
        includeInExcel: false,
        pdfImage: (question) => question.customer.image,
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

  return {
    activeFilterCount,
    answerErrors,
    answerForm,
    answeredCount,
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
    pendingCount,
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
  }
}
