import { computed, onMounted, reactive, ref } from 'vue'
import { shippingHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const LABELS = {
  pending: 'Disponible', assigned: 'Asignada', en_route: 'En ruta', arrived: 'En destino', delivered: 'Entregada', cancelled: 'Cancelada',
}

export function useAdminDeliveries() {
  const { showSnackbar } = useSnackbarSystem()
  const loading = ref(false)
  const rows = ref([])
  const summary = ref({})
  const filters = reactive({ status: '' })
  const pagination = reactive({ currentPage: 1, pageSize: 20, totalItems: 0 })
  const stats = computed(() => [
    { key: 'available', label: 'Disponibles', value: summary.value.pending_deliveries || 0, icon: 'fas fa-box-open', color: 'warning' },
    { key: 'active', label: 'En operación', value: summary.value.active_deliveries || 0, icon: 'fas fa-route', color: 'primary' },
    { key: 'couriers', label: 'Repartidores activos', value: summary.value.active_couriers || 0, icon: 'fas fa-motorcycle', color: 'success' },
  ])

  async function load() {
    loading.value = true
    try {
      const [listResponse, summaryResponse] = await Promise.all([
        shippingHttp.get('/admin/deliveries', { params: {
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
      showSnackbar({ type: 'error', message: 'No fue posible cargar los envíos asignados.' })
    } finally {
      loading.value = false
    }
  }

  function statusLabel(value) { return LABELS[value] || 'Sin estado' }
  function statusClass(value) { return ['delivered'].includes(value) ? 'active' : (value === 'cancelled' ? 'cancelled' : 'pending') }
  function formatDate(value) { return value ? new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Sin fecha' }

  onMounted(load)
  return { filters, formatDate, load, loading, pagination, rows, stats, statusClass, statusLabel }
}
