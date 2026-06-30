<template>
  <!--
    Layout principal del panel de administración (AdminLayout).
    Responsabilidad: Orquestar la estructura visual del dashboard:
    - Barra lateral (AdminSidebar) con navegación y perfil de usuario.
    - Encabezado superior (AdminHeader) con acciones globales y toggle del sidebar.
    - Área de contenido principal (RouterView) donde se renderizan las páginas hijas.
    - Footer fijo con información de la tienda (nombre, tagline, año).
    Maneja estado responsive: colapsa sidebar en móvil y gestiona overlay de cierre.
  -->
  <div
    class="admin-container"
    :class="{
      'sidebar-collapsed': sidebarCollapsed,
      'mobile-sidebar-open': isMobile && !sidebarCollapsed,
    }"
  >
    <!-- Sidebar lateral: navegación principal, colapsable y responsive -->
    <AdminSidebar
      :collapsed="sidebarCollapsed"
      :is-mobile="isMobile"
      :user="user"
      @toggle="toggleSidebar"
      @close="closeSidebar"
    />
    <!-- Overlay semitransparente para cerrar sidebar en móvil al hacer clic fuera -->
    <button
      v-if="isMobile && !sidebarCollapsed"
      type="button"
      class="admin-sidebar-overlay"
      aria-label="Cerrar menú lateral"
      @click="closeSidebar"
    ></button>
    <main class="admin-content">
      <!-- Header superior: título de página, breadcrumbs, acciones globales, toggle sidebar -->
      <AdminHeader
        @toggle-sidebar="toggleSidebar"
      />
      <!-- Contenedor del contenido de la página activa + footer fijo -->
      <div class="dashboard-content">
        <RouterView />
        <!-- Footer del panel con datos de configuración (store_name, store_tagline) -->
        <footer class="admin-panel-footer">
          <span class="admin-panel-footer__name">{{ storeNameFooter }}</span>
          <span class="admin-panel-footer__sep" aria-hidden="true">&middot;</span>
          <span class="admin-panel-footer__tagline">{{ storeTagline }}</span>
          <span class="admin-panel-footer__sep" aria-hidden="true">&middot;</span>
          <span class="admin-panel-footer__copy">&copy; {{ currentYear }}</span>
        </footer>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterView } from 'vue-router'
import { useSession } from '../../../composables/useSession'
import { useAppShell } from '../../../composables/useAppShell'
import AdminSidebar from '../components/AdminSidebar.vue'
import AdminHeader from '../components/AdminHeader.vue'
import '../styles/admin.css'

// Composable global del shell de la app: provee settings de la tienda (nombre, tagline, logo, etc.)
const { settings: shellSettings } = useAppShell()
// Tagline de la tienda para el footer del panel (fallback por si no hay settings)
const storeTagline = computed(() => shellSettings.value?.store_tagline || 'Moda con propósito')
// Nombre de la tienda para el footer del panel (fallback por si no hay settings)
const storeNameFooter = computed(() => shellSettings.value?.store_name || 'Angelow')
// Año actual para el copyright del footer
const currentYear = new Date().getFullYear()

// Usuario autenticado desde la sesión global (para mostrar en sidebar/header)
const { user } = useSession()
// Estado reactivo: true si viewport <= 768px (breakpoint móvil)
const isMobile = ref(window.innerWidth <= 768)
// Estado reactivo: sidebar colapsado; en móvil inicia colapsado, en desktop expandido
const sidebarCollapsed = ref(isMobile.value)

/**
 * Sincroniza el estado responsive (isMobile/sidebarCollapsed) al cambiar el tamaño de ventana.
 * Si cambia el breakpoint, fuerza colapso del sidebar en móvil para evitar solapes.
 */
function syncViewportState() {
  const nextIsMobile = window.innerWidth <= 768
  const changed = nextIsMobile !== isMobile.value
  isMobile.value = nextIsMobile

  if (changed) {
    sidebarCollapsed.value = nextIsMobile
  }
}

/**
 * Alterna el estado del sidebar (expandido/colapsado).
 * Se dispara desde AdminSidebar (botón hamburguesa) y AdminHeader (toggle global).
 */
function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

/**
 * Fuerza el cierre del sidebar (colapsado = true).
 * Se usa al hacer clic en overlay móvil o al navegar en móvil.
 */
function closeSidebar() {
  sidebarCollapsed.value = true
}

// Watcher: bloquea scroll del body cuando el sidebar está abierto en móvil (overlay activo)
watch([isMobile, sidebarCollapsed], ([mobile, collapsed]) => {
  document.body.style.overflow = mobile && !collapsed ? 'hidden' : ''
})

// Listener de resize para mantener sincronizado el estado responsive
onMounted(() => {
  window.addEventListener('resize', syncViewportState)
})

// Limpieza de listener y restauración de scroll al desmontar el layout
onUnmounted(() => {
  window.removeEventListener('resize', syncViewportState)
  document.body.style.overflow = ''
})
</script>
