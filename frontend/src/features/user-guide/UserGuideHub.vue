<template>
  <Teleport to="body">
    <div v-if="isOpen" class="user-guide-hub" @click.self="close">
      <section
        ref="dialogRef"
        class="user-guide-hub__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-guide-title"
        @keydown.esc="close"
      >
        <header class="user-guide-hub__header">
          <div>
            <span class="user-guide-hub__eyebrow">Manual de usuario interactivo</span>
            <h2 id="user-guide-title">¿Qué quieres aprender?</h2>
            <p>{{ audienceDescription }}</p>
          </div>
          <button ref="closeButtonRef" type="button" class="user-guide-hub__close" aria-label="Cerrar ayuda" @click="close">
            <i class="fas fa-xmark" aria-hidden="true"></i>
          </button>
        </header>

        <div class="user-guide-hub__tools">
          <label class="user-guide-hub__search">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only">Buscar una guía</span>
            <input v-model.trim="search" type="search" placeholder="Buscar una vista o funcionalidad">
          </label>
          <button
            v-if="currentGuide"
            type="button"
            class="user-guide-hub__current"
            @click="launchGuide(currentGuide)"
          >
            <i class="fas fa-play" aria-hidden="true"></i>
            Guiar esta vista: {{ currentGuide.title }}
          </button>
        </div>

        <div class="user-guide-hub__content">
          <section v-for="group in groupedGuides" :key="group.key" class="user-guide-hub__group">
            <h3>{{ group.label }}</h3>
            <div class="user-guide-hub__grid">
              <button
                v-for="item in group.items"
                :key="item.name"
                type="button"
                class="user-guide-card"
                :class="{ 'is-current': item.name === currentRouteName }"
                @click="launchGuide(item)"
              >
                <span class="user-guide-card__icon"><i :class="item.icon" aria-hidden="true"></i></span>
                <span class="user-guide-card__body">
                  <strong>{{ item.title }}</strong>
                  <small>{{ item.description }}</small>
                </span>
                <i class="fas fa-chevron-right user-guide-card__arrow" aria-hidden="true"></i>
              </button>
            </div>
          </section>

          <div v-if="groupedGuides.length === 0" class="user-guide-hub__empty">
            <i class="fas fa-circle-info" aria-hidden="true"></i>
            No encontramos una guía con ese término.
          </div>
        </div>

        <footer class="user-guide-hub__footer">
          <p v-if="audience === 'guest'">
            Las guías del dashboard aparecerán después de iniciar sesión como cliente.
          </p>
          <p v-else>
            Solo se muestran las guías permitidas para tu tipo de cuenta.
          </p>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSession } from '../../composables/useSession'
import { isAdminRole } from '../../utils/authNavigation'
import {
  USER_GUIDE_GROUPS,
  findUserGuide,
  getUserGuidesForAudience,
} from './guideCatalog'
import {
  OPEN_USER_GUIDE_EVENT,
  startUserGuide,
} from './userGuide'
import './userGuide.css'

const route = useRoute()
const router = useRouter()
const { isLoggedIn, user } = useSession()
const isOpen = ref(false)
const search = ref('')
const dialogRef = ref(null)
const closeButtonRef = ref(null)
let previouslyFocusedElement = null

const currentRouteName = computed(() => String(route.name || ''))
const audience = computed(() => {
  if (isLoggedIn.value && isAdminRole(user.value)) return 'admin'
  if (isLoggedIn.value) return 'client'
  return 'guest'
})
const audienceDescription = computed(() => {
  if (audience.value === 'admin') return 'Selecciona una sección del panel para recorrer sus controles y funciones.'
  if (audience.value === 'client') return 'Selecciona una vista de la tienda, tu cuenta o el proceso de compra.'
  return 'Empieza por iniciar sesión o crear una cuenta; las funciones privadas permanecen ocultas.'
})
const availableGuides = computed(() => getUserGuidesForAudience(audience.value, currentRouteName.value))
const currentGuide = computed(() => {
  const guide = findUserGuide(currentRouteName.value)
  return guide?.audience.includes(audience.value) ? guide : null
})
const groupedGuides = computed(() => {
  const normalizedSearch = search.value.toLocaleLowerCase('es')
  const groups = new Map()

  for (const item of availableGuides.value) {
    const searchable = `${item.title} ${item.description}`.toLocaleLowerCase('es')
    if (normalizedSearch && !searchable.includes(normalizedSearch)) continue

    if (!groups.has(item.group)) {
      groups.set(item.group, {
        key: item.group,
        label: USER_GUIDE_GROUPS[item.group] || item.group,
        items: [],
      })
    }
    groups.get(item.group).items.push(item)
  }

  return Array.from(groups.values())
})

function open() {
  previouslyFocusedElement = document.activeElement
  search.value = ''
  isOpen.value = true
  nextTick(() => closeButtonRef.value?.focus())
}

function close() {
  isOpen.value = false
  nextTick(() => previouslyFocusedElement?.focus?.())
}

function handleKeyboardShortcut(event) {
  if (!event.altKey || String(event.key || '').toLocaleLowerCase('es') !== 'h') return

  event.preventDefault()
  open()
}

async function launchGuide(item) {
  const isCurrentRoute = item.name === currentRouteName.value
  close()

  if (!isCurrentRoute) {
    await router.push({ name: item.name })
  }

  await nextTick()
  window.setTimeout(() => {
    void startUserGuide(item, audience.value)
  }, 350)
}

onMounted(() => {
  window.addEventListener(OPEN_USER_GUIDE_EVENT, open)
  window.addEventListener('keydown', handleKeyboardShortcut)
})

onBeforeUnmount(() => {
  window.removeEventListener(OPEN_USER_GUIDE_EVENT, open)
  window.removeEventListener('keydown', handleKeyboardShortcut)
})
</script>
