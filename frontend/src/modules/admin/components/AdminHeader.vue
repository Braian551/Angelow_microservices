<template>
  <header class="admin-header">
    <div class="header-left">
      <button class="sidebar-toggle" type="button" aria-label="Abrir menu lateral" @click="$emit('toggle-sidebar')">
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
          placeholder="Buscar pedidos, clientes o modulos"
          autocomplete="off"
          spellcheck="false"
          @input="handleSearch"
          @keydown.escape="closeSearch"
        >
        <button type="button" aria-label="Buscar">
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
              <div v-for="n in notifications" :key="n.id" class="notification-item" :class="{ unread: !n.read_at }">
                <i :class="notificationIcon(n.type)"></i>
                <div>
                  <p class="notification-text">{{ n.message }}</p>
                  <span class="notification-time">{{ formatTime(n.created_at) }}</span>
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
import { RouterLink } from 'vue-router'
import { useAppShell } from '../../../composables/useAppShell'

defineEmits(['toggle-sidebar'])

const { settings } = useAppShell()
// Nombre de la tienda reactivo desde configuración general
const storeName = computed(() => settings.value?.store_name || 'Angelow')

const searchQuery = ref('')
const showResults = ref(false)
const searching = ref(false)
const searchResults = ref([])
const showNotifications = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
const showQuickActions = ref(false)

let searchTimeout = null

const quickActions = [
  { id: 'new-product', icon: 'fa-plus-circle', label: 'Nuevo producto', description: 'Agregar producto al catálogo', path: '/admin/productos/nuevo' },
  { id: 'new-discount', icon: 'fa-tag', label: 'Generar códigos descuento', description: 'Crear códigos promocionales', path: '/admin/descuentos/codigos' },
  { id: 'new-announcement', icon: 'fa-bullhorn', label: 'Publicar anuncio', description: 'Enviar aviso a clientes', path: '/admin/anuncios' },
  { id: 'check-payments', icon: 'fa-money-check-alt', label: 'Revisar pagos pendientes', description: 'Verificar comprobantes', path: '/admin/pagos' },
  { id: 'view-orders', icon: 'fa-receipt', label: 'Ver órdenes', description: 'Revisar pedidos recientes', path: '/admin/ordenes' },
  { id: 'manage-categories', icon: 'fa-folder-open', label: 'Gestionar categorías', description: 'Editar categorías de productos', path: '/admin/categorias' },
  { id: 'manage-shipping', icon: 'fa-shipping-fast', label: 'Configurar envíos', description: 'Métodos y reglas de envío', path: '/admin/envios/metodos' },
  { id: 'manage-sliders', icon: 'fa-images', label: 'Editar sliders', description: 'Carousel de la pagina principal', path: '/admin/sliders' },
  { id: 'view-reports', icon: 'fa-chart-bar', label: 'Ver informes', description: 'Estadísticas de ventas', path: '/admin/informes/ventas' },
]

function handleSearch() {
  clearTimeout(searchTimeout)
  if (searchQuery.value.length < 2) {
    showResults.value = searchQuery.value.length > 0
    searchResults.value = []
    return
  }
  showResults.value = true
  searching.value = true
  searchTimeout = setTimeout(async () => {
    // Búsqueda local en rutas/módulos del admin
    const q = searchQuery.value.toLowerCase()
    const modules = [
      { title: 'Dashboard', subtitle: 'Panel de control', url: '/admin', icon: 'fas fa-tachometer-alt' },
      { title: 'Productos', subtitle: 'Gestión de productos', url: '/admin/productos', icon: 'fas fa-tshirt' },
      { title: 'Órdenes', subtitle: 'Gestión de pedidos', url: '/admin/ordenes', icon: 'fas fa-shopping-bag' },
      { title: 'Clientes', subtitle: 'Gestión de clientes', url: '/admin/clientes', icon: 'fas fa-users' },
      { title: 'Categorías', subtitle: 'Categorías de productos', url: '/admin/categorias', icon: 'fas fa-folder-open' },
      { title: 'Colecciones', subtitle: 'Colecciones de productos', url: '/admin/colecciones', icon: 'fas fa-layer-group' },
      { title: 'Pagos', subtitle: 'Configuración de pagos', url: '/admin/pagos', icon: 'fas fa-money-bill-wave' },
      { title: 'Descuentos', subtitle: 'Códigos y descuentos', url: '/admin/descuentos/codigos', icon: 'fas fa-percentage' },
      { title: 'Envíos', subtitle: 'Métodos de envío', url: '/admin/envios/metodos', icon: 'fas fa-truck' },
      { title: 'Anuncios', subtitle: 'Gestión de anuncios', url: '/admin/anuncios', icon: 'fas fa-bullhorn' },
      { title: 'Informes', subtitle: 'Reportes y estadísticas', url: '/admin/informes/ventas', icon: 'fas fa-chart-line' },
      { title: 'Administradores', subtitle: 'Gestión de admins', url: '/admin/administradores', icon: 'fas fa-user-shield' },
    ]
    searchResults.value = modules
      .filter(m => m.title.toLowerCase().includes(q) || m.subtitle.toLowerCase().includes(q))
      .map((m, i) => ({ ...m, id: i }))
    searching.value = false
  }, 300)
}

function closeSearch() {
  showResults.value = false
  searchQuery.value = ''
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) loadNotifications()
}

async function loadNotifications() {
  // TODO: Integrar con notification-service admin endpoint
  notifications.value = []
  unreadCount.value = 0
}

async function markAllRead() {
  notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }))
  unreadCount.value = 0
}

function notificationIcon(type) {
  const icons = {
    order: 'fas fa-shopping-bag',
    payment: 'fas fa-money-bill-wave',
    review: 'fas fa-star',
    system: 'fas fa-cog',
  }
  return icons[type] || 'fas fa-bell'
}

function formatTime(date) {
  if (!date) return ''
  const d = new Date(date)
  const now = new Date()
  const diff = Math.floor((now - d) / 1000 / 60)
  if (diff < 1) return 'Ahora'
  if (diff < 60) return `Hace ${diff} min`
  if (diff < 1440) return `Hace ${Math.floor(diff / 60)} h`
  return d.toLocaleDateString('es-CO')
}

// Ctrl+K shortcut
function handleKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    showQuickActions.value = !showQuickActions.value
  }
}

// Click outside to close dropdowns
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
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('click', handleClickOutside)
})
</script>
