<template>
  <aside class="admin-sidebar" :class="{ collapsed: collapsed, mobile: isMobile }">
    <div class="sidebar-header">
      <img src="/logo.png" alt="Angelow Logo" class="admin-logo">
      <h1>Panel</h1>
      <button class="close-sidebar" @click="$emit('close')">&times;</button>
    </div>

    <nav class="sidebar-nav">
      <ul class="nav-menu">
        <li class="nav-item" :class="{ active: isDashboardActive }">
          <RouterLink to="/admin">
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
            <li><RouterLink to="/admin/productos">Todos los productos</RouterLink></li>
            <li><RouterLink to="/admin/productos/nuevo">Agregar nuevo</RouterLink></li>
            <li><RouterLink to="/admin/categorias">Categorías</RouterLink></li>
            <li><RouterLink to="/admin/colecciones">Colecciones</RouterLink></li>
            <li><RouterLink to="/admin/tallas">Tallas</RouterLink></li>
            <li><RouterLink to="/admin/inventario">Inventario</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/ordenes') }">
          <RouterLink to="/admin/ordenes">
            <i class="fas fa-shopping-bag"></i>
            <span>Órdenes</span>
            <span v-if="newOrdersCount > 0" class="badge">{{ newOrdersCount }}</span>
          </RouterLink>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/clientes') }">
          <RouterLink to="/admin/clientes">
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
            <li><RouterLink to="/admin/resenas">Reseñas</RouterLink></li>
            <li><RouterLink to="/admin/preguntas">Preguntas</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/pagos') }">
          <RouterLink to="/admin/pagos">
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
            <li><RouterLink to="/admin/envios/reglas">Reglas por precio</RouterLink></li>
            <li><RouterLink to="/admin/envios/metodos">Definir envíos</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('descuentos') }">
          <div class="menu-item" @click="toggleSubmenu('descuentos')">
            <i class="fas fa-percentage"></i>
            <span>Descuentos</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.descuentos ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.descuentos">
            <li><RouterLink to="/admin/descuentos/cantidad">Descuentos por cantidad</RouterLink></li>
            <li><RouterLink to="/admin/descuentos/codigos">Códigos de Descuento</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/anuncios') }">
          <RouterLink to="/admin/anuncios">
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
            <li><RouterLink to="/admin/informes/ventas">Ventas</RouterLink></li>
            <li><RouterLink to="/admin/informes/productos">Productos populares</RouterLink></li>
            <li><RouterLink to="/admin/informes/clientes">Clientes recurrentes</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item with-submenu" :class="{ active: isSubmenuActive('configuracion') }">
          <div class="menu-item" @click="toggleSubmenu('configuracion')">
            <i class="fas fa-cog"></i>
            <span>Configuración</span>
            <i class="fas fa-chevron-down submenu-toggle" :style="{ transform: openMenus.configuracion ? 'rotate(180deg)' : '' }"></i>
          </div>
          <ul class="submenu" v-show="openMenus.configuracion">
            <li><RouterLink to="/admin/sliders">Sliders</RouterLink></li>
            <li><RouterLink to="/admin/configuracion/general">General</RouterLink></li>
          </ul>
        </li>

        <li class="nav-item" :class="{ active: isActive('/admin/administradores') }">
          <RouterLink to="/admin/administradores">
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
import { computed, reactive, ref, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useSession } from '../../../composables/useSession'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

const props = defineProps({
  collapsed: Boolean,
  user: Object,
})

defineEmits(['toggle', 'close'])

const route = useRoute()
const router = useRouter()
const { clearSession } = useSession()
const { showSnackbar } = useSnackbarSystem()

const isMobile = ref(window.innerWidth <= 768)
const newOrdersCount = ref(0)

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

function onAvatarError(event) {
  handleMediaError(event, props.user?.image, 'avatar')
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

function handleResize() {
  isMobile.value = window.innerWidth <= 768
}

watch(() => route.path, autoOpenSubmenu, { immediate: true })

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>
