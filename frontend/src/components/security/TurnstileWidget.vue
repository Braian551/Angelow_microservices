<template>
  <!-- Contenedor reutilizable del captcha Turnstile y su mensaje de estado. -->
  <div class="turnstile-widget">
    <div ref="containerRef" class="turnstile-widget__box" />
    <p v-if="statusMessage" class="turnstile-widget__message" :class="{ error: hasError }">
      {{ statusMessage }}
    </p>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

// Props requeridas para renderizar el widget y permitir reinicios desde el padre.
const props = defineProps({
  siteKey: {
    type: String,
    required: true,
  },
  resetKey: {
    type: [String, Number],
    default: 0,
  },
})

const emit = defineEmits(['verified', 'expired', 'error'])

// Identificadores del script compartido de Cloudflare Turnstile.
const TURNSTILE_SCRIPT_ID = 'cloudflare-turnstile-script'
const TURNSTILE_SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'

// Estado local del contenedor, widget y mensajes de carga/error.
const containerRef = ref(null)
const widgetId = ref(null)
const loading = ref(true)
const hasError = ref(false)

// Mensaje visible según configuración, carga o error del widget.
const statusMessage = computed(() => {
  if (!props.siteKey) return 'La verificación de seguridad no está configurada.'
  if (hasError.value) return 'No pudimos cargar la verificación. Inténtalo de nuevo.'
  if (loading.value) return 'Cargando verificación de seguridad...'
  return ''
})

// Carga una sola vez el script público de Cloudflare para todos los formularios.
function loadTurnstileScript() {
  if (window.turnstile) {
    return Promise.resolve()
  }

  const existingScript = document.getElementById(TURNSTILE_SCRIPT_ID)
  if (existingScript) {
    return new Promise((resolve, reject) => {
      existingScript.addEventListener('load', resolve, { once: true })
      existingScript.addEventListener('error', reject, { once: true })
    })
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.id = TURNSTILE_SCRIPT_ID
    script.src = TURNSTILE_SCRIPT_SRC
    script.async = true
    script.defer = true
    script.addEventListener('load', resolve, { once: true })
    script.addEventListener('error', reject, { once: true })
    document.head.appendChild(script)
  })
}

// Renderiza el widget explícito y emite el token de un solo uso al formulario padre.
async function renderWidget() {
  if (!props.siteKey || !containerRef.value) {
    hasError.value = true
    emit('error')
    return
  }

  loading.value = true
  hasError.value = false

  try {
    await loadTurnstileScript()
    await nextTick()

    if (widgetId.value !== null && window.turnstile) {
      window.turnstile.remove(widgetId.value)
      widgetId.value = null
    }

    widgetId.value = window.turnstile.render(containerRef.value, {
      sitekey: props.siteKey,
      theme: 'light',
      callback(token) {
        loading.value = false
        hasError.value = false
        emit('verified', token)
      },
      'expired-callback'() {
        emit('expired')
      },
      'error-callback'() {
        hasError.value = true
        loading.value = false
        emit('error')
      },
    })

    loading.value = false
  } catch (error) {
    hasError.value = true
    loading.value = false
    emit('error')
  }
}

// Permite al padre forzar un token nuevo después de errores o submits fallidos.
function reset() {
  emit('expired')
  if (widgetId.value !== null && window.turnstile) {
    window.turnstile.reset(widgetId.value)
    return
  }

  renderWidget()
}

// El padre incrementa resetKey para forzar un token nuevo después de errores.
watch(() => props.resetKey, () => {
  reset()
})

// Renderiza el captcha cuando el componente entra al DOM.
onMounted(() => {
  renderWidget()
})

// Elimina la instancia de Turnstile para evitar widgets duplicados.
onBeforeUnmount(() => {
  if (widgetId.value !== null && window.turnstile) {
    window.turnstile.remove(widgetId.value)
  }
})

// Expone reset para que el formulario padre pueda reiniciar el captcha manualmente.
defineExpose({ reset })
</script>

<style scoped>
/* Wrapper del widget con ancho completo dentro de formularios auth. */
.turnstile-widget {
  margin: 1.4rem 0;
  width: 100%;
}

/* Caja donde Cloudflare inyecta el iframe del desafío. */
.turnstile-widget__box {
  min-height: 65px;
  display: flex;
  justify-content: center;
}

/* Mensaje de carga o error debajo del captcha. */
.turnstile-widget__message {
  margin: 0.6rem 0 0;
  color: #64748b;
  font-size: 1.2rem;
  text-align: center;
}

.turnstile-widget__message.error {
  color: #dc2626;
}

/* Móvil muy estrecho: escala el iframe para evitar desbordes horizontales. */
@media (max-width: 380px) {
  .turnstile-widget__box {
    transform: scale(0.9);
    transform-origin: center top;
    min-height: 58px;
  }
}
</style>
