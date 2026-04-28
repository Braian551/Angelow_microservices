<template>
  <div
    class="admin-container"
    :class="{
      'sidebar-collapsed': sidebarCollapsed,
      'mobile-sidebar-open': isMobile && !sidebarCollapsed,
    }"
  >
    <AdminSidebar
      :collapsed="sidebarCollapsed"
      :is-mobile="isMobile"
      :user="user"
      @toggle="toggleSidebar"
      @close="closeSidebar"
    />
    <button
      v-if="isMobile && !sidebarCollapsed"
      type="button"
      class="admin-sidebar-overlay"
      aria-label="Cerrar menu lateral"
      @click="closeSidebar"
    ></button>
    <main class="admin-content">
      <AdminHeader
        @toggle-sidebar="toggleSidebar"
      />
      <div class="dashboard-content">
        <RouterView />
        <!-- Footer del panel con datos de configuración -->
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

const { settings: shellSettings } = useAppShell()
// Tagline y nombre de la tienda para el footer del panel
const storeTagline = computed(() => shellSettings.value?.store_tagline || 'Moda con propósito')
const storeNameFooter = computed(() => shellSettings.value?.store_name || 'Angelow')
const currentYear = new Date().getFullYear()

const { user } = useSession()
const isMobile = ref(window.innerWidth <= 768)
const sidebarCollapsed = ref(isMobile.value)

function syncViewportState() {
  const nextIsMobile = window.innerWidth <= 768
  const changed = nextIsMobile !== isMobile.value
  isMobile.value = nextIsMobile

  if (changed) {
    sidebarCollapsed.value = nextIsMobile
  }
}

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

function closeSidebar() {
  sidebarCollapsed.value = true
}

watch([isMobile, sidebarCollapsed], ([mobile, collapsed]) => {
  document.body.style.overflow = mobile && !collapsed ? 'hidden' : ''
})

onMounted(() => {
  window.addEventListener('resize', syncViewportState)
})

onUnmounted(() => {
  window.removeEventListener('resize', syncViewportState)
  document.body.style.overflow = ''
})
</script>
