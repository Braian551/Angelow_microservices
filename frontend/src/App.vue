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
import { computed, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import TopAnnouncementBar from './components/home/TopAnnouncementBar.vue'
import SiteFooter from './components/layout/SiteFooter.vue'
import SiteHeader from './components/layout/SiteHeader.vue'
import UserAlertSystem from './components/ui/UserAlertSystem.vue'
import UserSnackbarSystem from './components/ui/UserSnackbarSystem.vue'
import { useAppShell } from './composables/useAppShell'

const route = useRoute()
const { settings, topBar, cartCount, refreshShellData } = useAppShell()

const isAuthLayout = computed(() => route.meta?.layout === 'auth')
const isAdminLayout = computed(() => route.meta?.layout === 'admin' || String(route.path || '').startsWith('/admin'))
const showPublicChrome = computed(() => !isAuthLayout.value && !isAdminLayout.value)
const initialSearch = computed(() => String(route.query.search || ''))

watch(
  () => route.fullPath,
  async () => {
    if (!showPublicChrome.value) return

    await refreshShellData()
  },
  { immediate: true },
)
</script>
