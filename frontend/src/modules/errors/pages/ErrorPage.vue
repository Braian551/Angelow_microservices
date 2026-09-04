<template>
  <main class="error-page" :class="`error-page--${definition.tone}`">
    <div class="error-page__shape error-page__shape--one" aria-hidden="true"></div>
    <div class="error-page__shape error-page__shape--two" aria-hidden="true"></div>

    <section class="error-page__card" aria-labelledby="error-page-title" aria-describedby="error-page-description">
      <RouterLink to="/" class="error-page__brand" aria-label="Angelow, volver al inicio">
        <img src="/logo_principal.png" alt="Angelow" width="56" height="56" />
        <span>Angelow</span>
      </RouterLink>

      <div class="error-page__illustration" aria-hidden="true">
        <span class="error-page__code">{{ status }}</span>
        <span class="error-page__icon">
          <i class="fas" :class="definition.icon"></i>
        </span>
      </div>

      <p class="error-page__eyebrow">{{ definition.eyebrow }}</p>
      <h1 id="error-page-title" ref="heading" tabindex="-1">{{ definition.title }}</h1>
      <p id="error-page-description" class="error-page__description">{{ definition.description }}</p>

      <div class="error-page__actions">
        <button type="button" class="error-page__button error-page__button--primary" @click="handlePrimaryAction">
          {{ primaryActionLabel }}
        </button>
        <button type="button" class="error-page__button error-page__button--secondary" @click="handleSecondaryAction">
          {{ secondaryActionLabel }}
        </button>
      </div>

      <p v-if="definition.action === 'retry'" class="error-page__hint">
        Si el problema continúa, vuelve a intentarlo más tarde.
      </p>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { getErrorPageDefinition, getSafeInternalPath, normalizeErrorPageStatus } from '../../../utils/errorPage'
import '../views/ErrorPage.css'

const route = useRoute()
const router = useRouter()
const heading = ref(null)

const status = computed(() => (
  route.name === 'not-found' ? 404 : normalizeErrorPageStatus(route.params.status)
))
const definition = computed(() => getErrorPageDefinition(status.value))
const originPath = computed(() => getSafeInternalPath(route.query.from))

const primaryActionLabel = computed(() => {
  if (definition.value.action === 'login') return 'Iniciar sesión'
  if (definition.value.action === 'retry' && originPath.value) return 'Intentar de nuevo'
  if (definition.value.action === 'retry') return 'Ir al inicio'
  return 'Volver al inicio'
})

const secondaryActionLabel = computed(() => (
  definition.value.action === 'retry' ? 'Volver al inicio' : 'Regresar'
))

function handlePrimaryAction() {
  if (definition.value.action === 'login') {
    const loginRoute = { name: 'login' }
    if (originPath.value) loginRoute.query = { redirect: originPath.value }
    return router.replace(loginRoute)
  }

  if (definition.value.action === 'retry' && originPath.value) {
    return router.replace(originPath.value)
  }

  return router.replace({ name: 'home' })
}

function handleSecondaryAction() {
  if (typeof window !== 'undefined' && window.history.length > 1) {
    return router.back()
  }

  return router.replace({ name: 'home' })
}

onMounted(() => {
  heading.value?.focus()
})

watch(
  [status, definition],
  () => {
    document.title = `${status.value} - ${definition.value.title} | Angelow`
  },
  { immediate: true },
)
</script>
