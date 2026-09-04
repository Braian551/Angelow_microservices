import { computed, onMounted, reactive, ref } from 'vue'
import { authHttp, shippingHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const STATUS_LABELS = {
  pending: 'Pendiente',
  approved: 'Aprobado',
  rejected: 'Rechazado',
  inactive: 'Inactivo',
}

export function useAdminCouriers() {
  const { showSnackbar } = useSnackbarSystem()
  const loading = ref(false)
  const saving = ref(false)
  const rows = ref([])
  const summary = ref({})
  const selected = ref(null)
  const showModal = ref(false)
  const showEditorModal = ref(false)
  const savingEditor = ref(false)
  const requestingChanges = ref(false)
  const editorOriginal = ref(null)
  const courierForm = reactive({
    id: null,
    userId: '',
    name: '',
    email: '',
    phone: '',
    address: '',
    role: 'courier',
    accountActive: true,
    courierActive: false,
  })
  const courierErrors = reactive({})
  const filters = reactive({ search: '', status: '' })
  const pagination = reactive({ currentPage: 1, pageSize: 20, totalItems: 0 })
  const canReviewSelected = computed(() => selected.value?.status === 'pending')

  const stats = computed(() => [
    { key: 'pending', label: 'Por revisar', value: summary.value.pending_applications || 0, icon: 'fas fa-user-clock', color: 'warning' },
    { key: 'active', label: 'Repartidores activos', value: summary.value.active_couriers || 0, icon: 'fas fa-motorcycle', color: 'success' },
    { key: 'available', label: 'Entregas disponibles', value: summary.value.pending_deliveries || 0, icon: 'fas fa-box-open', color: 'primary' },
    { key: 'route', label: 'Entregas activas', value: summary.value.active_deliveries || 0, icon: 'fas fa-route', color: 'info' },
  ])

  async function load() {
    loading.value = true
    try {
      const [listResponse, summaryResponse] = await Promise.all([
        shippingHttp.get('/admin/couriers', { params: {
          search: filters.search.trim() || undefined,
          status: filters.status || undefined,
          page: pagination.currentPage,
          per_page: pagination.pageSize,
        } }),
        shippingHttp.get('/admin/couriers/summary'),
      ])
      const payload = listResponse.data?.data || {}
      rows.value = payload.data || []
      pagination.totalItems = Number(payload.total || 0)
      summary.value = summaryResponse.data?.data || {}
    } catch {
      showSnackbar({ type: 'error', message: 'No fue posible cargar los repartidores.' })
    } finally {
      loading.value = false
    }
  }

  function open(row) {
    selected.value = JSON.parse(JSON.stringify(row))
    const isPending = selected.value.status === 'pending'
    selected.value.rejection_reason = isPending ? '' : String(selected.value.rejection_reason || '')
    selected.value.documents = (selected.value.documents || []).map((document) => ({
      ...document,
      needs_change: false,
      review_note: isPending ? '' : String(document.review_note || ''),
    }))
    requestingChanges.value = false
    showModal.value = true
  }

  function close() {
    showModal.value = false
    selected.value = null
    requestingChanges.value = false
  }

  async function openEditor(row) {
    if (!row?.user_id || savingEditor.value) return

    savingEditor.value = true
    try {
      const response = await authHttp.get(`/admin/users/${row.user_id}`)
      const user = response.data?.data || response.data || {}
      Object.assign(courierForm, {
        id: row.id,
        userId: String(row.user_id),
        name: user.name || '',
        email: user.email || row.email || '',
        phone: row.phone || user.phone || '',
        address: row.address || '',
        role: user.role || 'courier',
        accountActive: user.active !== false,
        courierActive: Boolean(row.is_active),
      })
      editorOriginal.value = {
        courierActive: Boolean(row.is_active),
      }
      clearCourierErrors()
      showEditorModal.value = true
    } catch (error) {
      showSnackbar({ type: 'error', message: error.response?.data?.message || 'No fue posible cargar el repartidor para edición.' })
    } finally {
      savingEditor.value = false
    }
  }

  function closeEditor() {
    showEditorModal.value = false
    editorOriginal.value = null
    clearCourierErrors()
  }

  function clearCourierErrors() {
    Object.keys(courierErrors).forEach((key) => delete courierErrors[key])
  }

  function validateCourierField(field) {
    delete courierErrors[field]

    if (field === 'name' && courierForm.name.trim().length < 2) {
      courierErrors.name = 'Ingresa un nombre de al menos 2 caracteres.'
    }
    if (field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(courierForm.email.trim())) {
      courierErrors.email = 'Ingresa un correo electrónico válido.'
    }
    if (field === 'phone' && courierForm.phone.trim() && !/^3[0-9]{9}$/.test(courierForm.phone.trim())) {
      courierErrors.phone = 'Ingresa un celular colombiano de 10 dígitos.'
    }
  }

  function validateCourierForm() {
    clearCourierErrors()
    for (const field of ['name', 'email', 'phone']) {
      validateCourierField(field)
    }
    return Object.keys(courierErrors).length === 0
  }

  async function saveEditor() {
    if (savingEditor.value || !validateCourierForm()) return

    savingEditor.value = true
    try {
      const desiredCourierActive = courierForm.role === 'courier' && courierForm.courierActive
      await authHttp.put(`/admin/users/${courierForm.userId}`, {
        name: courierForm.name.trim(),
        email: courierForm.email.trim(),
        phone: courierForm.phone.trim() || null,
        role: courierForm.role,
        active: courierForm.accountActive,
      })
      await shippingHttp.put(`/admin/couriers/${courierForm.id}`, {
        email: courierForm.email.trim(),
        phone: courierForm.phone.trim(),
        address: courierForm.address.trim(),
      })
      if (desiredCourierActive !== editorOriginal.value?.courierActive) {
        await shippingHttp.patch(`/admin/couriers/${courierForm.id}/active`, { is_active: desiredCourierActive })
      }
      showSnackbar({ type: 'success', message: 'Repartidor actualizado correctamente.' })
      closeEditor()
      await load()
    } catch (error) {
      const errors = error.response?.data?.errors
      if (errors && typeof errors === 'object') {
        Object.entries(errors).forEach(([field, messages]) => {
          courierErrors[field] = Array.isArray(messages) ? messages[0] : String(messages)
        })
      } else {
        showSnackbar({ type: 'error', message: error.response?.data?.message || 'No fue posible actualizar el repartidor.' })
      }
    } finally {
      savingEditor.value = false
    }
  }

  function beginChangeRequest() {
    if (!selected.value || !canReviewSelected.value) return
    requestingChanges.value = true
  }

  function cancelChangeRequest() {
    if (!selected.value) return
    requestingChanges.value = false
    selected.value.rejection_reason = ''
    selected.value.documents.forEach((document) => {
      document.needs_change = false
      document.review_note = ''
    })
  }

  function toggleDocumentChange(document) {
    document.needs_change = !document.needs_change
    if (!document.needs_change) {
      document.review_note = ''
    }
  }

  async function saveReview(status) {
    if (!selected.value || saving.value || !canReviewSelected.value) return
    const isChangeRequest = status === 'rejected'
    const documentsNeedingChanges = (selected.value.documents || []).filter((document) => document.needs_change)

    if (isChangeRequest && documentsNeedingChanges.length === 0) {
      showSnackbar({ type: 'warning', message: 'Selecciona al menos un documento que deba corregirse.' })
      return
    }
    if (isChangeRequest && !String(selected.value.rejection_reason || '').trim()) {
      showSnackbar({ type: 'warning', message: 'Escribe un mensaje general para orientar al repartidor.' })
      return
    }
    if (isChangeRequest && documentsNeedingChanges.some((document) => !String(document.review_note || '').trim())) {
      showSnackbar({ type: 'warning', message: 'Explica el cambio requerido en cada documento seleccionado.' })
      return
    }

    saving.value = true
    try {
      const documents = Object.fromEntries((selected.value.documents || []).map((document) => [document.id, {
        status: isChangeRequest && document.needs_change ? 'rejected' : 'approved',
        review_note: isChangeRequest && document.needs_change ? document.review_note.trim() : null,
      }]))
      await shippingHttp.put(`/admin/couriers/${selected.value.id}`, {
        status,
        rejection_reason: isChangeRequest ? selected.value.rejection_reason.trim() : null,
        documents,
      })
      showSnackbar({
        type: 'success',
        message: isChangeRequest
          ? 'La solicitud de cambios fue enviada al repartidor.'
          : 'Repartidor aprobado y notificado.',
      })
      close()
      await load()
    } catch (error) {
      showSnackbar({ type: 'error', message: error.response?.data?.message || 'No fue posible guardar la revisión.' })
    } finally {
      saving.value = false
    }
  }

  async function toggleActive(row) {
    if (saving.value) return
    saving.value = true
    try {
      await shippingHttp.patch(`/admin/couriers/${row.id}/active`, { is_active: !row.is_active })
      showSnackbar({ type: 'success', message: row.is_active ? 'Repartidor desactivado.' : 'Repartidor activado.' })
      await load()
    } catch {
      showSnackbar({ type: 'error', message: 'No fue posible cambiar el estado.' })
    } finally {
      saving.value = false
    }
  }

  function statusLabel(value) {
    return STATUS_LABELS[value] || 'Sin estado'
  }

  function statusClass(value) {
    return { pending: 'pending', approved: 'active', rejected: 'cancelled', inactive: 'cancelled' }[value] || 'pending'
  }

  onMounted(load)

  return {
    beginChangeRequest,
    canReviewSelected,
    cancelChangeRequest,
    close,
    closeEditor,
    courierErrors,
    courierForm,
    filters,
    load,
    loading,
    open,
    openEditor,
    pagination,
    requestingChanges,
    rows,
    saveReview,
    saveEditor,
    saving,
    savingEditor,
    selected,
    showModal,
    showEditorModal,
    stats,
    statusClass,
    statusLabel,
    toggleActive,
    toggleDocumentChange,
    validateCourierField,
  }
}
