<template>
  <TopAnnouncementBar v-if="showPublicChrome" :announcement="topBar" />
  <SiteHeader
    v-if="showPublicChrome"
    :settings="settings"
    :cart-count="cartCount"
    :initial-search="initialSearch"
  />
  <RouterView />
  <SiteFooter v-if="showPublicChrome" :settings="settings" />
  <UserAlertSystem />
  <UserSnackbarSystem />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import TopAnnouncementBar from './components/home/TopAnnouncementBar.vue'
import SiteFooter from './components/layout/SiteFooter.vue'
import SiteHeader from './components/layout/SiteHeader.vue'
import UserAlertSystem from './components/ui/UserAlertSystem.vue'
import UserSnackbarSystem from './components/ui/UserSnackbarSystem.vue'
import { SITE_SETTINGS_UPDATED_EVENT } from './constants/siteSettingsEvents'
import { useAppShell } from './composables/useAppShell'
import { getFallbackMediaUrl, resolveMediaUrl } from './utils/media'

const route = useRoute()
const { settings, topBar, cartCount, refreshShellData, refreshShellSettings } = useAppShell()

const isAuthLayout = computed(() => route.meta?.layout === 'auth')
const isAdminLayout = computed(() => route.meta?.layout === 'admin' || String(route.path || '').startsWith('/admin'))
const showPublicChrome = computed(() => !isAuthLayout.value && !isAdminLayout.value)
const initialSearch = computed(() => String(route.query.search || ''))

function applyBrandRuntimeSettings(currentSettings) {
  const storeName = String(currentSettings?.store_name || 'Angelow').trim()
  document.title = storeName ? `${storeName} - Ropa Infantil Premium` : 'Angelow - Ropa Infantil Premium'

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

watch(
  () => route.fullPath,
  async () => {
    if (!showPublicChrome.value) return

    await Promise.all([refreshShellData(), refreshShellSettings()])
  },
  { immediate: true },
)

watch(
  settings,
  (currentSettings) => {
    applyBrandRuntimeSettings(currentSettings || {})
  },
  { immediate: true, deep: true },
)

onMounted(() => {
  window.addEventListener(SITE_SETTINGS_UPDATED_EVENT, handleSiteSettingsUpdated)
})

onBeforeUnmount(() => {
  window.removeEventListener(SITE_SETTINGS_UPDATED_EVENT, handleSiteSettingsUpdated)
})
</script>
