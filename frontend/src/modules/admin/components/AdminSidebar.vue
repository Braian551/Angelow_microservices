<template>
  <aside class="admin-sidebar" :class="{ collapsed: collapsed, mobile: isMobile }">
    <div class="sidebar-header">
      <img :src="adminLogo" alt="Angelow Logo" class="admin-logo" @error="onLogoError">
      <h1>Panel</h1>
      <button class="close-sidebar" @click="$emit('close')">&times;</button>
    </div>

    <nav class="sidebar-nav">
      <ul class="nav-menu">
        <li class="nav-item" :class="{ active: isDashboardActive }">
          <RouterLink to="/admin" @click="handleNavigate">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
          </RouterLink>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('productos') }">
          <div class="menu-item" @click="toggleSubmenu('productos')">
            <i class="fas fa-tshirt"></i>
            <span>Productos</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.productos ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.productos">
            <li><RouterLink to="/admin/productos" @click="handleNavigate">Todos los productos</RouterLink></li>
            <li><RouterLink to="/admin/productos/nuevo" @click="handleNavigate">Agregar nuevo</RouterLink></li>
            <li><RouterLink to="/admin/categorias" @click="handleNavigate">Categorías</RouterLink></li>
            <li><RouterLink to="/admin/colecciones" @click="handleNavigate">Colecciones</RouterLink></li>
            <li><RouterLink to="/admin/tallas" @click="handleNavigate">Tallas</RouterLink></li>
            <li><RouterLink to="/admin/inventario" @click="handleNavigate">Inventario</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/ordenes') }">
          <RouterLink to="/admin/ordenes" @click="handleNavigate">
            <i class="fas fa-shopping-bag"></i>
            <span>Órdenes</span>
            <span v-if="newOrdersCount > 0" class="badge">{{ newOrdersCount }}</span>
          </RouterLink>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/clientes') }">
          <RouterLink to="/admin/clientes" @click="handleNavigate">
            <i class="fas fa-users"></i>
            <span>Clientes</span>
          </RouterLink>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('resenas') }">
          <div class="menu-item" @click="toggleSubmenu('resenas')">
            <i class="fas fa-star"></i>
            <span>Reseñas</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.resenas ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.resenas">
            <li><RouterLink to="/admin/resenas" @click="handleNavigate">Reseñas</RouterLink></li>
            <li><RouterLink to="/admin/preguntas" @click="handleNavigate">Preguntas</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/pagos') }">
          <RouterLink to="/admin/pagos" @click="handleNavigate">
            <i class="fas fa-money-bill-wave"></i>
            <span>Pagos</span>
          </RouterLink>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('envios') }">
          <div class="menu-item" @click="toggleSubmenu('envios')">
            <i class="fas fa-truck"></i>
            <span>Envíos</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.envios ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.envios">
            <li><RouterLink to="/admin/envios/reglas" @click="handleNavigate">Reglas por precio</RouterLink></li>
            <li><RouterLink to="/admin/envios/metodos" @click="handleNavigate">Definir envíos</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('descuentos') }">
          <div class="menu-item" @click="toggleSubmenu('descuentos')">
            <i class="fas fa-percentage"></i>
            <span>Descuentos</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.descuentos ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.descuentos">
            <li><RouterLink to="/admin/descuentos/cantidad" @click="handleNavigate">Descuentos por cantidad</RouterLink></li>
            <li><RouterLink to="/admin/descuentos/codigos" @click="handleNavigate">Códigos de Descuento</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/anuncios') }">
          <RouterLink to="/admin/anuncios" @click="handleNavigate">
            <i class="fas fa-bullhorn"></i>
            <span>Anuncios</span>
          </RouterLink>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('informes') }">
          <div class="menu-item" @click="toggleSubmenu('informes')">
            <i class="fas fa-chart-line"></i>
            <span>Informes</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.informes ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.informes">
            <li><RouterLink to="/admin/informes/ventas" @click="handleNavigate">Ventas</RouterLink></li>
            <li><RouterLink to="/admin/informes/productos" @click="handleNavigate">Productos populares</RouterLink></li>
            <li><RouterLink to="/admin/informes/clientes" @click="handleNavigate">Clientes recurrentes</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('configuracion') }">
          <div class="menu-item" @click="toggleSubmenu('configuracion')">
            <i class="fas fa-cog"></i>
            <span>Configuración</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.configuracion ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.configuracion">
            <li><RouterLink to="/admin/sliders" @click="handleNavigate">Sliders</RouterLink></li>
            <li><RouterLink to="/admin/configuracion/general" @click="handleNavigate">General</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/administradores') }">
          <RouterLink to="/admin/administradores" @click="handleNavigate">
            <i class="fas fa-user-shield"></i>
            <span>Administradores</span>
          </RouterLink>
        </li>
      </ul>
    </nav>

    <div class="sidebar-footer">
      <div class="admin-profile">
        <img :src="avatarUrl" alt="Foto de perfil" class="profile-avatar" @error="onAvatarError">
        <div class="profile-info">
          <span class="profile-name">{{ user?.name || 'Administrador' }}</span>
          <span class="profile-email">{{ user?.email || '' }}</span>
        </div>
      </div>
      <button class="logout-btn" @click="handleLogout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Cerrar sesión</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { SITE_SETTINGS_UPDATED_EVENT } from '../../../constants/siteSettingsEvents'
import { useSession } from '../../../composables/useSession'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { getFallbackMediaUrl, handleMediaError, resolveMediaUrl } from '../../../utils/media'

const props = defineProps({
  collapsed: Boolean,
  isMobile: Boolean,
  user: Object,
})

const emit = defineEmits(['toggle', 'close'])

const route = useRoute()
const router = useRouter()
const { clearSession } = useSession()
const { showSnackbar } = useSnackbarSystem()

const newOrdersCount = ref(0)
const sidebarSettings = ref({})

const openMenus = reactive({
  productos: false,
  resenas: false,
  envios: false,
  descuentos: false,
  informes: false,
  configuracion: false,
})

const isDashboardActive = computed(() => route.path === '/admin' || route.name === 'admin-dashboard')

const avatarUrl = computed(() => {
  return resolveMediaUrl(props.user?.image, 'avatar')
})

const adminLogo = computed(() => {
  return resolveMediaUrl(sidebarSettings.value?.brand_logo_secondary || sidebarSettings.value?.brand_logo, 'brand')
})

function onAvatarError(event) {
  handleMediaError(event, props.user?.image, 'avatar')
}

function onLogoError(event) {
  const logoPath = sidebarSettings.value?.brand_logo_secondary || sidebarSettings.value?.brand_logo || ''
  handleMediaError(event, logoPath, 'brand')
}

function isActive(path) {
  return route.path === path || route.path.startsWith(path + '/')
}

function isSubmenuActive(menu) {
  const paths = {
    productos: ['/admin/productos', '/admin/categorias', '/admin/colecciones', '/admin/tallas', '/admin/inventario'],
    resenas: ['/admin/resenas', '/admin/preguntas'],
    envios: ['/admin/envios'],
    descuentos: ['/admin/descuentos'],
    informes: ['/admin/informes'],
    configuracion: ['/admin/sliders', '/admin/configuracion'],
  }
  return (paths[menu] || []).some(p => route.path.startsWith(p))
}

function toggleSubmenu(menu) {
  Object.keys(openMenus).forEach(key => {
    if (key !== menu) openMenus[key] = false
  })
  openMenus[menu] = !openMenus[menu]
}

function autoOpenSubmenu() {
  Object.keys(openMenus).forEach(key => {
    openMenus[key] = isSubmenuActive(key)
  })
}

async function handleLogout() {
  clearSession()
  showSnackbar({ type: 'info', message: 'Sesión cerrada' })
  router.push({ name: 'login' })
}

function handleNavigate() {
  if (props.isMobile) {
    emit('close')
  }
}

async function loadSidebarSettings() {
  try {
    const { data } = await catalogHttp.get('/settings')
    sidebarSettings.value = data?.data || {}
  } catch {
    sidebarSettings.value = {
      brand_logo_secondary: getFallbackMediaUrl('brand'),
    }
  }
}

function handleSiteSettingsUpdated(event) {
  const incomingSettings = event?.detail?.settings
  if (!incomingSettings || typeof incomingSettings !== 'object') {
    loadSidebarSettings()
    return
  }

  sidebarSettings.value = {
    ...sidebarSettings.value,
    ...incomingSettings,
  }
}

watch(() => route.path, autoOpenSubmenu, { immediate: true })

onMounted(() => {
  loadSidebarSettings()
  window.addEventListener(SITE_SETTINGS_UPDATED_EVENT, handleSiteSettingsUpdated)
})

onBeforeUnmount(() => {
  window.removeEventListener(SITE_SETTINGS_UPDATED_EVENT, handleSiteSettingsUpdated)
})
</script>
