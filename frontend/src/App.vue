<template>
  <!-- Shell raíz: muestra chrome público solo fuera de auth y admin. -->
  <TopAnnouncementBar v-if="showPublicChrome" :announcement="topBar" />
  <SiteHeader
    v-if="showPublicChrome"
    :settings="settings"
    :shell-loading="shellLoading"
    :cart-count="cartCount"
    :notification-count="notificationCount"
    :initial-search="initialSearch"
  />
  <RouterView />
  <SiteFooter v-if="showPublicChrome" :settings="settings" />
  <button v-if="isAuthLayout" type="button" class="user-guide-auth-launcher" @click="openUserGuideHub">
    <i class="fas fa-circle-question" aria-hidden="true"></i>
    Ayuda
  </button>
  <UserGuideHub />
  <UserAlertSystem />
  <UserSnackbarSystem />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import TopAnnouncementBar from './components/home/TopAnnouncementBar.vue'
import SiteFooter from './components/layout/SiteFooter.vue'
import SiteHeader from './components/layout/SiteHeader.vue'
import UserAlertSystem from './components/ui/UserAlertSystem.vue'
import UserSnackbarSystem from './components/ui/UserSnackbarSystem.vue'
import { SITE_SETTINGS_UPDATED_EVENT } from './constants/siteSettingsEvents'
import { useAppShell } from './composables/useAppShell'
import { useSession } from './composables/useSession'
import UserGuideHub from './features/user-guide/UserGuideHub.vue'
import { openUserGuideHub } from './features/user-guide/userGuide'
import { getProfile } from './services/authApi'
import { isAdminRole } from './utils/authNavigation'
import { navigateToErrorPage } from './utils/errorPage'
import { getFallbackMediaUrl, resolveMediaUrl } from './utils/media'

// Ruta actual y datos compartidos del shell público.
const route = useRoute()
const router = useRouter()
const { clearSession, saveSession, token, user } = useSession()
const { settings, topBar, cartCount, notificationCount, shellLoading, refreshShellData, refreshShellSettings } = useAppShell()

// Layouts derivados de la ruta para decidir si se ocultan header, footer y barra superior.
const isAuthLayout = computed(() => route.meta?.layout === 'auth')
const isAdminLayout = computed(() => route.meta?.layout === 'admin' || String(route.path || '').startsWith('/admin'))
const isErrorLayout = computed(() => route.meta?.layout === 'error')
const showPublicChrome = computed(() => !isAuthLayout.value && !isAdminLayout.value && !isErrorLayout.value)
const initialSearch = computed(() => String(route.query.search || ''))

// Aplica nombre, favicon y colores de marca como configuración runtime del documento.
function applyBrandRuntimeSettings(currentSettings) {
  const storeName = String(currentSettings?.store_name || 'Angelow').trim()
  const tagline = String(currentSettings?.store_tagline || '').trim()
  document.title = tagline ? `${storeName} - ${tagline}` : storeName

  const faviconBase = resolveMediaUrl(currentSettings?.brand_favicon, 'brand') || getFallbackMediaUrl('brand')
  const faviconVersion = currentSettings?.__refreshToken ? `?v=${currentSettings.__refreshToken}` : ''
  const faviconHref = `${faviconBase}${faviconVersion}`
  const faviconEl = document.querySelector("link[rel='icon']")
  if (faviconEl) {
    faviconEl.setAttribute('href', faviconHref)
  }

  const root = document.documentElement
  if (currentSettings?.primary_color) {
    root.style.setProperty('--brand-primary', String(currentSettings.primary_color))
  }
  if (currentSettings?.secondary_color) {
    root.style.setProperty('--brand-secondary', String(currentSettings.secondary_color))
  }
}

// Recibe actualizaciones de configuración emitidas por admin/settings y refresca el shell público.
function handleSiteSettingsUpdated(event) {
  const incomingSettings = event?.detail?.settings
  const refreshToken = Number(event?.detail?.refreshedAt || Date.now())

  if (incomingSettings && typeof incomingSettings === 'object') {
    settings.value = {
      ...settings.value,
      ...incomingSettings,
      __refreshToken: refreshToken,
    }
    return
  }

  refreshShellSettings()
}

function handleAuthExpired() {
  clearSession()

  if (route.name !== 'login') {
    router.replace({
      name: 'login',
      query: { redirect: route.fullPath },
    })
  }
}

function handleServerError(event) {
  void navigateToErrorPage(router, event?.detail?.status || 503, { from: route.fullPath }).catch(() => {})
}

function redirectAccordingToRole(currentUser) {
  if (!currentUser || !route.path) return

  if (isAdminRole(currentUser) && route.path.startsWith('/mi-cuenta')) {
    router.replace({ name: 'admin-dashboard' })
    return
  }

  if (!isAdminRole(currentUser) && route.path.startsWith('/admin')) {
    router.replace({ name: 'account-dashboard' })
  }
}

async function refreshSessionProfile() {
  if (!token.value) return

  try {
    const response = await getProfile()
    const currentUser = response?.data
    if (currentUser && typeof currentUser === 'object') {
      saveSession(token.value, currentUser)
      redirectAccordingToRole(currentUser)
    }
  } catch (error) {
    if (error?.response?.status === 401) {
      clearSession()
    }
  }
}

watch(
  () => [route.path, user.value?.role],
  () => redirectAccordingToRole(user.value),
  { immediate: true },
)

// Al navegar en zona pública refresca conteos, anuncios y configuración visible.
watch(
  () => route.fullPath,
  async () => {
    if (!showPublicChrome.value) return

    await Promise.all([refreshShellData(), refreshShellSettings()])
  },
  { immediate: true },
)

// Cada cambio de settings actualiza el documento y variables CSS de marca.
watch(
  settings,
  (currentSettings) => {
    applyBrandRuntimeSettings(currentSettings || {})
  },
  { immediate: true, deep: true },
)

// Escucha eventos globales de configuración mientras la SPA está montada.
onMounted(() => {
  window.addEventListener(SITE_SETTINGS_UPDATED_EVENT, handleSiteSettingsUpdated)
  window.addEventListener('angelow:auth-expired', handleAuthExpired)
  window.addEventListener('angelow:server-error', handleServerError)
  void refreshSessionProfile()
})

// Retira el listener global para evitar duplicados en HMR o desmontajes.
onBeforeUnmount(() => {
  window.removeEventListener(SITE_SETTINGS_UPDATED_EVENT, handleSiteSettingsUpdated)
  window.removeEventListener('angelow:auth-expired', handleAuthExpired)
  window.removeEventListener('angelow:server-error', handleServerError)
})
</script>
