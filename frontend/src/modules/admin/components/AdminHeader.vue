<template>
  <header class="admin-header">
    <div class="header-left">
      <button class="sidebar-toggle" type="button" aria-label="Abrir menú lateral" @click="$emit('toggle-sidebar')">
        <i class="fas fa-bars"></i>
      </button>
      <h2>{{ storeName }}</h2>
    </div>

    <div class="header-right">
      <div class="search-box" role="search">
        <input
          id="admin-global-search"
          name="admin-global-search"
          type="search"
          v-model="searchQuery"
          placeholder="Buscar pedidos, facturas, clientes o módulos"
          autocomplete="off"
          spellcheck="false"
          @input="handleSearch"
          @keydown.enter.prevent="submitSearch"
          @keydown.escape="closeSearch"
        >
        <button type="button" aria-label="Buscar" @click="submitSearch">
          <i class="fas fa-search"></i>
        </button>
        <div v-if="showResults" class="search-results-panel">
          <div v-if="searching" class="dropdown-empty">Buscando...</div>
          <div v-else-if="searchResults.length === 0" class="dropdown-empty">
            {{ searchQuery.length < 2 ? 'Escribe al menos 2 letras para comenzar.' : 'No se encontraron resultados.' }}
          </div>
          <ul v-else class="search-results-list">
            <li v-for="result in searchResults" :key="result.id" class="search-result-item">
              <RouterLink :to="result.url" @click="closeSearch">
                <i :class="result.icon"></i>
                <div>
                  <strong>{{ result.title }}</strong>
                  <p>{{ result.subtitle }}</p>
                </div>
              </RouterLink>
            </li>
          </ul>
        </div>
      </div>

      <div class="header-actions">
        <div class="header-action">
          <button
            class="notification-btn"
            type="button"
            @click="toggleNotifications"
          >
            <i class="fas fa-bell"></i>
            <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
          </button>
          <div v-if="showNotifications" class="header-dropdown notifications-panel">
            <div class="dropdown-header">
              <div>
                <h4>Notificaciones</h4>
                <p class="dropdown-subtitle">Eventos recientes del sistema</p>
              </div>
              <div class="dropdown-actions">
                <button type="button" class="link-button" @click="loadNotifications">Actualizar</button>
                <button type="button" class="link-button" @click="markAllRead">Marcar todo</button>
              </div>
            </div>
            <div class="dropdown-body">
              <p v-if="notifications.length === 0" class="dropdown-empty">Sin notificaciones nuevas.</p>
              <div
                v-for="n in notifications"
                :key="n.id"
                class="notification-item"
                :class="{ unread: !n.read_at }"
                role="button"
                tabindex="0"
                @click="openNotification(n)"
                @keydown.enter.prevent="openNotification(n)"
                @keydown.space.prevent="openNotification(n)"
              >
                <i :class="notificationIcon(n.type)"></i>
                <div class="notification-item__body">
                  <p class="notification-text">{{ n.message }}</p>
                  <div class="notification-item__meta">
                    <span class="notification-module-badge">{{ notificationModuleLabel(n) }}</span>
                    <span class="notification-time">{{ formatTime(n.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="header-action">
          <button
            class="quick-action-btn"
            type="button"
            @click="showQuickActions = !showQuickActions"
          >
            <i class="fas fa-plus"></i>
            <span>Acción rápida</span>
          </button>
          <div v-if="showQuickActions" class="header-dropdown quick-actions-panel">
            <div class="dropdown-header">
              <div>
                <h4>Acciones rápidas</h4>
                <p class="dropdown-subtitle">Atajo Ctrl + K</p>
              </div>
              <button type="button" class="link-button" @click="showQuickActions = false">Cerrar</button>
            </div>
            <div class="dropdown-body">
              <ul class="quick-actions-list">
                <li v-for="action in quickActions" :key="action.id" class="quick-action-item">
                  <RouterLink :to="action.path" @click="showQuickActions = false" class="quick-action-link">
                    <span class="icon"><i :class="'fas ' + action.icon"></i></span>
                    <div>
                      <strong>{{ action.label }}</strong>
                      <p>{{ action.description }}</p>
                    </div>
                  </RouterLink>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAppShell } from '../../../composables/useAppShell'
import { authHttp, orderHttp } from '../../../services/http'
import { getAdminInvoices } from '../../../services/invoiceApi'
import { useAdminNotifications } from '../composables/useAdminNotifications'

defineEmits(['toggle-sidebar'])

const router = useRouter()
const { settings } = useAppShell()
// Nombre de la tienda reactivo desde configuración general
const storeName = computed(() => settings.value?.store_name || 'Angelow')

const searchQuery = ref('')
const showResults = ref(false)
const searching = ref(false)
const searchResults = ref([])
const showNotifications = ref(false)
const showQuickActions = ref(false)

const {
  notifications,
  unreadCount,
  loadNotifications,
  markAllNotificationsAsRead,
  markNotificationAsRead,
  resolveNotificationRoute,
  resolveNotificationModuleLabel,
  startAdminNotifications,
  stopAdminNotifications,
  markRouteNotificationsAsRead,
} = useAdminNotifications()

let searchTimeout = null
let searchRequestId = 0

const SEARCH_MIN_LENGTH = 2
const SEARCH_DEBOUNCE_MS = 220
const SEARCH_RESULTS_LIMIT = 12
const SEARCH_RESULTS_PER_SOURCE = 4

const searchTypePriority = {
  order: 0,
  invoice: 1,
  customer: 2,
  module: 3,
}

const quickActions = [
  { id: 'new-product', icon: 'fa-plus-circle', label: 'Nuevo producto', description: 'Agregar producto al catálogo', path: '/admin/productos/nuevo' },
  { id: 'new-discount', icon: 'fa-tag', label: 'Generar códigos de descuento', description: 'Crear códigos promocionales', path: '/admin/descuentos/codigos' },
  { id: 'new-announcement', icon: 'fa-bullhorn', label: 'Publicar anuncio', description: 'Enviar aviso a clientes', path: '/admin/anuncios' },
  { id: 'check-payments', icon: 'fa-money-check-alt', label: 'Revisar pagos pendientes', description: 'Verificar comprobantes', path: '/admin/pagos' },
  { id: 'view-invoices', icon: 'fa-file-invoice-dollar', label: 'Ver facturas', description: 'Facturas automáticas y reenvíos', path: '/admin/facturas' },
  { id: 'view-orders', icon: 'fa-receipt', label: 'Ver órdenes', description: 'Revisar pedidos recientes', path: '/admin/ordenes' },
  { id: 'manage-categories', icon: 'fa-folder-open', label: 'Gestionar categorías', description: 'Editar categorías de productos', path: '/admin/categorias' },
  { id: 'manage-shipping', icon: 'fa-shipping-fast', label: 'Configurar envíos', description: 'Métodos y reglas de envío', path: '/admin/envios/metodos' },
  { id: 'manage-sliders', icon: 'fa-images', label: 'Editar sliders', description: 'Carrusel de la página principal', path: '/admin/sliders' },
  { id: 'view-reports', icon: 'fa-chart-bar', label: 'Ver informes', description: 'Estadísticas de ventas', path: '/admin/informes/ventas' },
]

const searchableModules = [
  { title: 'Dashboard', subtitle: 'Panel de control', url: '/admin', icon: 'fas fa-tachometer-alt', keywords: ['inicio', 'resumen', 'panel'] },
  { title: 'Productos', subtitle: 'Gestión de productos', url: '/admin/productos', icon: 'fas fa-tshirt', keywords: ['producto', 'productos', 'inventario'] },
  { title: 'Órdenes', subtitle: 'Gestión de pedidos', url: '/admin/ordenes', icon: 'fas fa-shopping-bag', keywords: ['orden', 'ordenes', 'pedido', 'pedidos', 'compras'] },
  { title: 'Clientes', subtitle: 'Gestión de clientes', url: '/admin/clientes', icon: 'fas fa-users', keywords: ['cliente', 'clientes', 'usuarios'] },
  { title: 'Categorías', subtitle: 'Categorías de productos', url: '/admin/categorias', icon: 'fas fa-folder-open', keywords: ['categoria', 'categorias'] },
  { title: 'Colecciones', subtitle: 'Colecciones de productos', url: '/admin/colecciones', icon: 'fas fa-layer-group', keywords: ['coleccion', 'colecciones'] },
  { title: 'Pagos', subtitle: 'Configuración de pagos', url: '/admin/pagos', icon: 'fas fa-money-bill-wave', keywords: ['pago', 'pagos', 'comprobante'] },
  { title: 'Facturas', subtitle: 'Facturas generadas automáticamente', url: '/admin/facturas', icon: 'fas fa-file-invoice-dollar', keywords: ['factura', 'facturas'] },
  { title: 'Descuentos', subtitle: 'Códigos y descuentos', url: '/admin/descuentos/codigos', icon: 'fas fa-percentage', keywords: ['descuento', 'descuentos', 'cupon', 'cupones'] },
  { title: 'Envíos', subtitle: 'Métodos de envío', url: '/admin/envios/metodos', icon: 'fas fa-truck', keywords: ['envio', 'envios', 'domicilio'] },
  { title: 'Anuncios', subtitle: 'Gestión de anuncios', url: '/admin/anuncios', icon: 'fas fa-bullhorn', keywords: ['anuncio', 'anuncios', 'campana'] },
  { title: 'Informes', subtitle: 'Reportes y estadísticas', url: '/admin/informes/ventas', icon: 'fas fa-chart-line', keywords: ['reporte', 'reportes', 'informe', 'informes', 'estadisticas'] },
  { title: 'Administradores', subtitle: 'Gestión de administradores', url: '/admin/administradores', icon: 'fas fa-user-shield', keywords: ['admin', 'administrador', 'administradores', 'usuarios'] },
]

function normalizeSearchText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

function normalizeOrderSource(value) {
  return String(value || '').trim().toLowerCase() === 'legacy' ? 'legacy' : 'microservice'
}

function buildOrderDetailRoute(order) {
  const isLegacy = normalizeOrderSource(order?.order_source) === 'legacy'

  return {
    name: 'admin-order-detail',
    params: { id: Number(order?.id || 0) },
    query: isLegacy ? { vista: 'archivo' } : {},
  }
}

function calculateSearchScore(query, candidates = []) {
  let bestScore = Number.POSITIVE_INFINITY

  candidates
    .map((candidate) => normalizeSearchText(candidate))
    .filter(Boolean)
    .forEach((candidate) => {
      const matchPosition = candidate.indexOf(query)

      if (matchPosition === -1) {
        return
      }

      if (candidate === query) {
        bestScore = Math.min(bestScore, 0)
        return
      }

      if (candidate.startsWith(query)) {
        bestScore = Math.min(bestScore, 1 + (candidate.length / 1000))
        return
      }

      bestScore = Math.min(bestScore, 2 + (matchPosition / 1000))
    })

  return bestScore
}

function sortSearchResults(results) {
  return [...results].sort((left, right) => {
    if (left.score !== right.score) {
      return left.score - right.score
    }

    const typeGap = (searchTypePriority[left.type] ?? 99) - (searchTypePriority[right.type] ?? 99)
    if (typeGap !== 0) {
      return typeGap
    }

    return left.title.localeCompare(right.title, 'es', { sensitivity: 'base' })
  })
}

function buildModuleResults(query) {
  return searchableModules
    .map((module) => {
      const score = calculateSearchScore(query, [module.title, module.subtitle, module.url, ...(module.keywords || [])])

      if (!Number.isFinite(score)) {
        return null
      }

      return {
        ...module,
        id: `module:${module.url}`,
        type: 'module',
        score,
      }
    })
    .filter(Boolean)
}

function extractOrderRows(response) {
  const payload = response?.data?.data || {}
  return Array.isArray(payload) ? payload : (payload.rows || [])
}

function extractInvoiceRows(response) {
  const payload = response?.data || {}
  return Array.isArray(payload.rows) ? payload.rows : []
}

function extractCustomerRows(response) {
  const payload = response?.data?.data || response?.data || []
  return Array.isArray(payload) ? payload : (payload.data || [])
}

function mapOrderResult(order, query) {
  const id = Number(order?.id || 0)
  if (!id) {
    return null
  }

  const orderNumber = order.order_number || `#${id}`
  const customerName = order.customer_name || order.user_name || order.billing_name || (order.user_id ? `Cliente ${order.user_id}` : 'Cliente')
  const customerEmail = order.customer_email || order.user_email || order.billing_email || ''
  const score = calculateSearchScore(query, [orderNumber, id, customerName, customerEmail])

  if (!Number.isFinite(score)) {
    return null
  }

  return {
    id: `order:${normalizeOrderSource(order.order_source)}:${id}`,
    title: orderNumber,
    subtitle: ['Pedido', customerName, customerEmail || `ID ${id}`].filter(Boolean).join(' · '),
    url: buildOrderDetailRoute(order),
    icon: 'fas fa-shopping-bag',
    type: 'order',
    score,
  }
}

function mapInvoiceResult(invoice, query) {
  const id = Number(invoice?.id || 0)
  if (!id) {
    return null
  }

  const orderSource = normalizeOrderSource(invoice.order_source)
  const orderNumber = invoice.order_number || `#${id}`
  const invoiceNumber = invoice.invoice_number || `FAC-${orderNumber}`
  const customerName = invoice.customer_name || invoice.user_name || invoice.billing_name || 'Cliente'
  const customerEmail = invoice.customer_email || invoice.user_email || invoice.billing_email || ''
  const score = calculateSearchScore(query, [invoiceNumber, orderNumber, id, customerName, customerEmail])

  if (!Number.isFinite(score)) {
    return null
  }

  return {
    id: `invoice:${orderSource}:${id}`,
    title: invoiceNumber,
    subtitle: ['Factura', `Pedido ${orderNumber}`, customerName, customerEmail].filter(Boolean).join(' · '),
    url: {
      path: '/admin/facturas',
      query: {
        search: invoiceNumber,
        invoice: String(id),
        source: orderSource,
      },
    },
    icon: 'fas fa-file-invoice-dollar',
    type: 'invoice',
    score,
  }
}

function mapCustomerResult(customer, query) {
  const id = String(customer?.id || '').trim()
  if (!id) {
    return null
  }

  const customerName = customer.name || 'Cliente'
  const customerEmail = customer.email || ''
  const customerPhone = customer.phone || ''
  const score = calculateSearchScore(query, [id, customerName, customerEmail, customerPhone])

  if (!Number.isFinite(score)) {
    return null
  }

  const searchSeed = customerEmail || customerName || customerPhone || id

  return {
    id: `customer:${id}`,
    title: customerName,
    subtitle: ['Cliente', customerEmail || customerPhone || `ID ${id}`].filter(Boolean).join(' · '),
    url: {
      path: '/admin/clientes',
      query: {
        search: searchSeed,
        customer: id,
      },
    },
    icon: 'fas fa-users',
    type: 'customer',
    score,
  }
}

async function runSearch(query) {
  const normalizedQuery = normalizeSearchText(query)
  if (normalizedQuery.length < SEARCH_MIN_LENGTH) {
    return []
  }

  const staticResults = buildModuleResults(normalizedQuery)

  // El buscador mezcla módulos fijos con entidades reales del admin.
  const [ordersResponse, invoicesResponse, customersResponse] = await Promise.allSettled([
    orderHttp.get('/admin/orders', { params: { search: query, limit: SEARCH_RESULTS_PER_SOURCE } }),
    getAdminInvoices({ search: query, limit: SEARCH_RESULTS_PER_SOURCE }),
    authHttp.get('/admin/customers', { params: { search: query, limit: SEARCH_RESULTS_PER_SOURCE } }),
  ])

  const liveResults = []

  if (ordersResponse.status === 'fulfilled') {
    liveResults.push(
      ...extractOrderRows(ordersResponse.value)
        .map((order) => mapOrderResult(order, normalizedQuery))
        .filter(Boolean)
        .slice(0, SEARCH_RESULTS_PER_SOURCE),
    )
  }

  if (invoicesResponse.status === 'fulfilled') {
    liveResults.push(
      ...extractInvoiceRows(invoicesResponse.value)
        .map((invoice) => mapInvoiceResult(invoice, normalizedQuery))
        .filter(Boolean)
        .slice(0, SEARCH_RESULTS_PER_SOURCE),
    )
  }

  if (customersResponse.status === 'fulfilled') {
    liveResults.push(
      ...extractCustomerRows(customersResponse.value)
        .map((customer) => mapCustomerResult(customer, normalizedQuery))
        .filter(Boolean)
        .slice(0, SEARCH_RESULTS_PER_SOURCE),
    )
  }

  return sortSearchResults([...liveResults, ...staticResults]).slice(0, SEARCH_RESULTS_LIMIT)
}

function handleSearch() {
  clearTimeout(searchTimeout)
  if (normalizeSearchText(searchQuery.value).length < SEARCH_MIN_LENGTH) {
    showResults.value = searchQuery.value.length > 0
    searchResults.value = []
    searching.value = false
    return
  }

  showResults.value = true
  searching.value = true

  const requestId = ++searchRequestId
  searchTimeout = setTimeout(() => {
    runSearch(searchQuery.value)
      .then((results) => {
        if (requestId !== searchRequestId) {
          return
        }

        searchResults.value = results
      })
      .finally(() => {
        if (requestId === searchRequestId) {
          searching.value = false
        }
      })
  }, SEARCH_DEBOUNCE_MS)
}

async function submitSearch() {
  if (normalizeSearchText(searchQuery.value).length < SEARCH_MIN_LENGTH) {
    showResults.value = searchQuery.value.length > 0
    searchResults.value = []
    return
  }

  clearTimeout(searchTimeout)

  const requestId = ++searchRequestId
  showResults.value = true
  searching.value = true
  searchResults.value = await runSearch(searchQuery.value)

  if (requestId !== searchRequestId) {
    return
  }

  searching.value = false

  const firstResult = searchResults.value[0]
  if (!firstResult) return

  const currentPath = router.currentRoute.value?.fullPath || router.currentRoute.value?.path || ''
  const resolvedTarget = router.resolve(firstResult.url)
  if (currentPath !== resolvedTarget.fullPath) {
    await router.push(firstResult.url)
  }
  closeSearch()
}

function closeSearch() {
  clearTimeout(searchTimeout)
  searchRequestId += 1
  showResults.value = false
  searchQuery.value = ''
  searchResults.value = []
  searching.value = false
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) loadNotifications()
}

async function markAllRead() {
  markAllNotificationsAsRead()
}

async function openNotification(notification) {
  if (!notification) {
    return
  }

  if (notification.id !== undefined && notification.id !== null) {
    markNotificationAsRead(notification.id)
  }

  const targetRoute = resolveNotificationRoute(notification)
  showNotifications.value = false

  if (!targetRoute) {
    return
  }

  const resolvedTarget = router.resolve(targetRoute)
  const currentPath = router.currentRoute.value?.fullPath || router.currentRoute.value?.path || ''
  if (currentPath !== resolvedTarget.fullPath) {
    await router.push(targetRoute)
  }

  markRouteNotificationsAsRead(resolvedTarget.path)
}

function notificationModuleLabel(notification) {
  return resolveNotificationModuleLabel(notification?.module_key)
}

function notificationIcon(type) {
  const icons = {
    order: 'fas fa-shopping-bag',
    payment: 'fas fa-money-bill-wave',
    invoice: 'fas fa-file-invoice-dollar',
    inventory: 'fas fa-warehouse',
    review: 'fas fa-star',
    system: 'fas fa-cog',
  }
  return icons[type] || 'fas fa-bell'
}

function formatTime(date) {
  if (!date) return ''
  const d = new Date(date)
  if (Number.isNaN(d.getTime())) return ''
  const now = new Date()
  const diff = Math.floor((now - d) / 1000 / 60)
  if (diff < 1) return 'Ahora'
  if (diff < 60) return `Hace ${diff} min`
  if (diff < 1440) return `Hace ${Math.floor(diff / 60)} h`
  return d.toLocaleDateString('es-CO')
}

// Atajo Ctrl + K
function handleKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    showQuickActions.value = !showQuickActions.value
  }
}

// Cierra los paneles al hacer clic fuera.
function handleClickOutside(e) {
  if (!e.target.closest('.header-action') && !e.target.closest('.search-box')) {
    showNotifications.value = false
    showQuickActions.value = false
    showResults.value = false
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  document.addEventListener('click', handleClickOutside)
  startAdminNotifications()
  markRouteNotificationsAsRead(router.currentRoute.value?.path || '')
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('click', handleClickOutside)
  stopAdminNotifications()
})
</script>
