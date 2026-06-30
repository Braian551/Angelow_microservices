<template>
  <main class="auth-page recovery-page">
    <!-- Barra superior para volver al inicio sin abandonar la navegación SPA. -->
    <header class="auth-topbar">
      <RouterLink to="/" class="auth-topbar-logo" aria-label="Inicio">
        <img src="/logo_principal.png" alt="Angelow" />
      </RouterLink>
    </header>

    <section class="recovery-container">
      <!-- Encabezado que explica el propósito del flujo de recuperación. -->
      <div class="recovery-header">
        <div class="recovery-logo">
          <img src="/logo.png" alt="Angelow" />
        </div>
        <h1>Recupera tu contraseña</h1>
        <p>Usa tu correo o teléfono registrado para recibir un código seguro en segundos.</p>
      </div>

      <!-- Progreso de tres pasos: identificar cuenta, validar código y cambiar contraseña. -->
      <div class="progress-steps">
        <div class="step" :class="{ active: step === 1, completed: step > 1 }" data-step="1">
          <div class="step-number">1</div>
          <div class="step-title">Identificar cuenta</div>
        </div>
        <div class="step" :class="{ active: step === 2, completed: step > 2 }" data-step="2">
          <div class="step-number">2</div>
          <div class="step-title">Validar código</div>
        </div>
        <div class="step" :class="{ active: step === 3 }" data-step="3">
          <div class="step-number">3</div>
          <div class="step-title">Nueva contraseña</div>
        </div>
        <div class="progress-bar">
          <div class="progress" :style="{ width: progressWidth }" />
        </div>
      </div>

      <form class="recovery-form" novalidate @submit.prevent="submitResetPassword">
        <!-- Paso 1: captura correo o teléfono y solicita el código seguro. -->
        <div class="form-step" :class="{ active: step === 1 }" data-step="1">
          <div class="form-group">
            <label for="recovery-identifier">Correo electrónico o teléfono</label>
            <input
              id="recovery-identifier"
              v-model.trim="form.identifier"
              type="text"
              placeholder="Ej: maria@email.com o 3001234567"
              :class="{ error: !!errors.identifier }"
              autocomplete="username"
              @input="onIdentifierInput"
              @blur="onIdentifierBlur"
              required
            />
            <div class="form-hint">Enviaremos un código de 4 dígitos al dato que ingreses.</div>
            <div v-if="errors.identifier" class="error-message">{{ errors.identifier }}</div>
          </div>

          <TurnstileWidget
            v-if="step === 1"
            ref="turnstileRef"
            :site-key="turnstileSiteKey"
            :reset-key="turnstileResetKey"
            @verified="onTurnstileVerified"
            @expired="onTurnstileExpired"
            @error="onTurnstileError"
          />
          <div v-if="errors.turnstile" class="error-message">{{ errors.turnstile }}</div>

          <button
            type="button"
            class="btn-primary"
            :disabled="loading.requestCode || requestCooldown > 0"
            @click="submitRequestCode(false)"
          >
            <span>{{ requestCodeButtonText }}</span>
            <i class="fas fa-paper-plane" />
          </button>
        </div>

        <!-- Paso 2: valida el código recibido y permite reenvío controlado. -->
        <div class="form-step" :class="{ active: step === 2 }" data-step="2">
          <AuthCodeVerification
            v-model:code="form.code"
            input-id="recovery-code"
            :info="codeInfo"
            :status="codeStatus"
            :timer-label="timerLabel"
            :error="errors.code"
            :resend-text="resendButtonText"
            :resend-disabled="resendCooldown > 0 || loading.resendCode"
            @input="onCodeInput"
            @blur="onCodeBlur"
            @resend="submitRequestCode(true)"
          />

          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="loading.verifyCode" @click="goBackToIdentifier">
              <i class="fas fa-arrow-left" />
              Atrás
            </button>
            <button type="button" class="btn-primary" :disabled="loading.verifyCode" @click="submitVerifyCode">
              {{ loading.verifyCode ? 'Validando...' : 'Validar código' }}
            </button>
          </div>

          <TurnstileWidget
            v-if="step === 2"
            ref="resendTurnstileRef"
            :site-key="turnstileSiteKey"
            :reset-key="turnstileResetKey"
            @verified="onTurnstileVerified"
            @expired="onTurnstileExpired"
            @error="onTurnstileError"
          />
          <div v-if="errors.turnstile" class="error-message">{{ errors.turnstile }}</div>
        </div>

        <!-- Paso 3: recibe y confirma la nueva contraseña antes de cerrar el flujo. -->
        <div class="form-step" :class="{ active: step === 3 }" data-step="3">
          <div class="form-group password-group">
            <label for="recovery-password">Nueva contraseña</label>
            <div class="password-input-container">
              <input
                id="recovery-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Mínimo 8 caracteres"
                autocomplete="new-password"
                :class="{ error: !!errors.password }"
                @input="onPasswordInput"
                @blur="onPasswordBlur"
                required
              />
              <button type="button" class="toggle-password" aria-label="Mostrar contraseña" @click="showPassword = !showPassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" />
              </button>
            </div>
            <div class="form-hint">Combina letras, números y símbolos para mayor seguridad.</div>
            <div v-if="errors.password" class="error-message">{{ errors.password }}</div>
          </div>

          <div class="form-group password-group">
            <label for="recovery-password-confirmation">Confirmar contraseña</label>
            <div class="password-input-container">
              <input
                id="recovery-password-confirmation"
                v-model="form.passwordConfirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                placeholder="Repite tu contraseña"
                autocomplete="new-password"
                :class="{ error: !!errors.passwordConfirmation }"
                @input="onPasswordConfirmationInput"
                @blur="onPasswordConfirmationBlur"
                required
              />
              <button type="button" class="toggle-password" aria-label="Mostrar confirmación" @click="showPasswordConfirmation = !showPasswordConfirmation">
                <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'" />
              </button>
            </div>
            <div v-if="errors.passwordConfirmation" class="error-message">{{ errors.passwordConfirmation }}</div>
          </div>

          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="loading.resetPassword" @click="step = 2">
              <i class="fas fa-arrow-left" />
              Atrás
            </button>
            <button type="submit" class="btn-primary" :disabled="loading.resetPassword">
              {{ loading.resetPassword ? 'Actualizando...' : 'Restablecer contraseña' }}
            </button>
          </div>
        </div>
      </form>

      <p v-if="successMessage" class="success-message">{{ successMessage }}</p>
      <p v-if="globalError" class="global-error">{{ globalError }}</p>

      <div class="login-redirect">
        ¿Recordaste tu contraseña?
        <RouterLink :to="{ name: 'login' }" class="text-link">Inicia sesión</RouterLink>
      </div>
      <div class="register-redirect">
        ¿Necesitas crear una cuenta?
        <RouterLink :to="{ name: 'register' }" class="text-link">Regístrate aquí</RouterLink>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AuthCodeVerification from '../components/AuthCodeVerification.vue'
import TurnstileWidget from '../../../components/security/TurnstileWidget.vue'
import {
  requestRecoveryCode,
  resendRecoveryCode,
  resetRecoveryPassword,
  verifyRecoveryCode,
} from '../../../services/authApi'
import '../views/ForgotPasswordView.css'

// Router usado para regresar al login cuando la contraseña queda actualizada.
const router = useRouter()

// Estado principal del flujo de recuperación y visibilidad de contraseñas.
const step = ref(1)
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const successMessage = ref('')
const globalError = ref('')

const codeStatus = ref('pending')
const codeInfo = ref('Revisa tu bandeja de entrada y escribe el código que te enviamos.')
const codeExpiresIn = ref(0)
const resendCooldown = ref(0)
const requestCooldown = ref(0)
const sessionToken = ref('')
const turnstileToken = ref('')
const turnstileResetKey = ref(0)
const turnstileRef = ref(null)
const resendTurnstileRef = ref(null)
const turnstileSiteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY || ''

// Identificadores de intervalos para expiración, reenvío y cooldown inicial.
let timerIntervalId = null
let resendIntervalId = null
let requestIntervalId = null

// Modelo del formulario de recuperación en sus tres etapas.
const form = reactive({
  identifier: '',
  code: '',
  password: '',
  passwordConfirmation: '',
  turnstile: '',
})

// Estados de carga independientes para bloquear doble envío por acción.
const loading = reactive({
  requestCode: false,
  resendCode: false,
  verifyCode: false,
  resetPassword: false,
})

// Errores por campo del flujo de recuperación.
const errors = reactive({
  identifier: '',
  code: '',
  password: '',
  passwordConfirmation: '',
})

// Calcula el avance visual del flujo según el paso activo.
const progressWidth = computed(() => {
  if (step.value <= 1) return '0%'
  if (step.value === 2) return '50%'
  return '100%'
})

// Etiqueta mm:ss del código vigente.
const timerLabel = computed(() => formatSeconds(codeExpiresIn.value))

// Clase visual del estado del código para el componente de verificación.
const codeStatusClass = computed(() => ({
  pending: codeStatus.value === 'pending',
  valid: codeStatus.value === 'valid',
  expired: codeStatus.value === 'expired',
}))

// Texto legible del estado actual del código.
const codeStatusText = computed(() => {
  if (codeStatus.value === 'valid') return 'Código validado'
  if (codeStatus.value === 'expired') return 'Código expirado'
  return 'Pendiente de validación'
})

// Texto del botón inicial, incluyendo cooldown cuando aplica.
const requestCodeButtonText = computed(() => {
  if (loading.requestCode) return 'Enviando...'
  if (requestCooldown.value > 0) return `Enviar código (${requestCooldown.value}s)`
  return 'Enviar código'
})

// Texto del botón de reenvío, incluyendo cooldown cuando aplica.
const resendButtonText = computed(() => {
  if (loading.resendCode) return 'Reenviando...'
  if (resendCooldown.value > 0) return `Reenviar código (${resendCooldown.value}s)`
  return 'Reenviar código'
})

// Limpia errores de campos antes de validar una nueva acción.
function clearFieldErrors() {
  errors.identifier = ''
  errors.code = ''
  errors.password = ''
  errors.passwordConfirmation = ''
  errors.turnstile = ''
}

// Fuerza una nueva instancia lógica del widget Turnstile.
function resetTurnstile() {
  turnstileToken.value = ''
  turnstileResetKey.value += 1
}

// Guarda el token emitido por Turnstile.
function onTurnstileVerified(token) {
  turnstileToken.value = token
  errors.turnstile = ''
}

// Descarta el token cuando Turnstile expira.
function onTurnstileExpired() {
  turnstileToken.value = ''
}

// Bloquea el envío y muestra el error cuando Turnstile falla.
function onTurnstileError() {
  turnstileToken.value = ''
  errors.turnstile = 'No pudimos cargar la verificación de seguridad. Inténtalo de nuevo.'
}

// Limpia mensajes globales al reintentar una acción.
function clearMessages() {
  successMessage.value = ''
  globalError.value = ''
}

// Extrae mensajes de validación o error general desde la respuesta de API.
function parseApiError(error, fallbackMessage) {
  const validationErrors = error?.response?.data?.errors
  if (validationErrors && typeof validationErrors === 'object') {
    const firstError = Object.values(validationErrors).flat().find(Boolean)
    if (firstError) return String(firstError)
  }

  return error?.response?.data?.message || fallbackMessage
}

// Lee el cooldown enviado por el backend o incluido en el mensaje de error.
function extractCooldownSeconds(error) {
  const fromData = Number(error?.response?.data?.data?.resend_cooldown)
  if (Number.isFinite(fromData) && fromData > 0) {
    return Math.min(Math.round(fromData), 60)
  }

  const message = String(error?.response?.data?.message || '')
  const match = message.match(/(\d+)\s*segundos?/i)
  if (!match) return 0

  const parsed = Number(match[1])
  if (!Number.isFinite(parsed) || parsed <= 0) return 0
  return Math.min(Math.round(parsed), 60)
}

// Formatea segundos en mm:ss para mostrar expiración de código.
function formatSeconds(totalSeconds) {
  const safeValue = Math.max(0, Number(totalSeconds) || 0)
  const minutes = String(Math.floor(safeValue / 60)).padStart(2, '0')
  const seconds = String(safeValue % 60).padStart(2, '0')
  return `${minutes}:${seconds}`
}

// Mantiene informado al usuario mientras no puede pedir otro código.
function setIdentifierCooldownMessage(seconds) {
  errors.identifier = `Ya enviamos un código recientemente. Intenta de nuevo en ${seconds} segundos.`
}

// Inicia el cooldown para solicitudes iniciales y limpia el mensaje al terminar.
function startRequestCooldown(seconds) {
  if (requestIntervalId) {
    clearInterval(requestIntervalId)
    requestIntervalId = null
  }

  requestCooldown.value = Math.max(0, Math.min(60, Number(seconds) || 60))
  if (requestCooldown.value <= 0) return

  setIdentifierCooldownMessage(requestCooldown.value)

  requestIntervalId = setInterval(() => {
    if (requestCooldown.value <= 1) {
      requestCooldown.value = 0
      clearInterval(requestIntervalId)
      requestIntervalId = null
      if (errors.identifier.includes('Ya enviamos un código recientemente')) {
        errors.identifier = ''
      }
      return
    }

    requestCooldown.value -= 1
    setIdentifierCooldownMessage(requestCooldown.value)
  }, 1000)
}

// Normaliza correo o teléfono para que el backend reciba una identidad consistente.
function normalizeIdentifier(value) {
  const input = String(value || '').trim()
  if (!input) return ''

  const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input)
  if (isEmail) return input.toLowerCase()

  const digits = input.replace(/\D+/g, '')
  if (digits.length >= 7 && digits.length <= 15) return digits

  return ''
}

// Valida el identificador antes de solicitar o reenviar código.
function validateIdentifier() {
  const normalized = normalizeIdentifier(form.identifier)
  if (!normalized) {
    errors.identifier = 'Ingresa un correo válido o un teléfono de 7 a 15 dígitos.'
    return ''
  }

  if (!errors.identifier.includes('Ya enviamos un código recientemente')) {
    errors.identifier = ''
  }

  return normalized
}

// Revalida el identificador al escribir y elimina mensajes previos.
function onIdentifierInput() {
  clearMessages()
  validateIdentifier()
}

// Fuerza validación del identificador al salir del campo.
function onIdentifierBlur() {
  validateIdentifier()
}

// Valida que el código tenga exactamente cuatro dígitos.
function validateCode() {
  if (!/^[0-9]{4}$/.test(form.code)) {
    errors.code = 'El código debe tener 4 dígitos.'
    return false
  }

  errors.code = ''
  return true
}

// Revalida el código en tiempo real.
function onCodeInput() {
  clearMessages()
  validateCode()
}

// Fuerza validación del código al salir del campo.
function onCodeBlur() {
  validateCode()
}

// Valida longitud y coincidencia de la nueva contraseña.
function validatePasswords() {
  let valid = true

  if (!form.password || form.password.length < 8) {
    errors.password = 'La contraseña debe tener al menos 8 caracteres.'
    valid = false
  } else if (form.password.length > 64) {
    errors.password = 'La contraseña no puede superar 64 caracteres.'
    valid = false
  } else {
    errors.password = ''
  }

  if (form.password !== form.passwordConfirmation) {
    errors.passwordConfirmation = 'Las contraseñas no coinciden.'
    valid = false
  } else {
    errors.passwordConfirmation = ''
  }

  return valid
}

// Revalida contraseñas al escribir la principal.
function onPasswordInput() {
  clearMessages()
  validatePasswords()
}

// Fuerza validación de contraseña al salir del campo.
function onPasswordBlur() {
  validatePasswords()
}

// Revalida confirmación al escribirla.
function onPasswordConfirmationInput() {
  clearMessages()
  validatePasswords()
}

// Fuerza validación de confirmación al salir del campo.
function onPasswordConfirmationBlur() {
  validatePasswords()
}

// Detiene todos los temporizadores activos del flujo.
function stopTimers() {
  if (timerIntervalId) {
    clearInterval(timerIntervalId)
    timerIntervalId = null
  }

  if (resendIntervalId) {
    clearInterval(resendIntervalId)
    resendIntervalId = null
  }

  if (requestIntervalId) {
    clearInterval(requestIntervalId)
    requestIntervalId = null
  }
}

// Inicia temporizadores de expiración del código y cooldown de reenvío.
function startTimers(expiresIn, cooldown) {
  if (timerIntervalId) {
    clearInterval(timerIntervalId)
    timerIntervalId = null
  }

  if (resendIntervalId) {
    clearInterval(resendIntervalId)
    resendIntervalId = null
  }

  codeExpiresIn.value = Math.max(0, Number(expiresIn) || 900)
  resendCooldown.value = Math.max(0, Number(cooldown) || 60)
  codeStatus.value = 'pending'

  timerIntervalId = setInterval(() => {
    if (codeExpiresIn.value <= 1) {
      codeExpiresIn.value = 0
      codeStatus.value = 'expired'
      clearInterval(timerIntervalId)
      timerIntervalId = null
      return
    }

    codeExpiresIn.value -= 1
  }, 1000)

  resendIntervalId = setInterval(() => {
    if (resendCooldown.value <= 1) {
      resendCooldown.value = 0
      clearInterval(resendIntervalId)
      resendIntervalId = null
      return
    }

    resendCooldown.value -= 1
  }, 1000)
}

// Solicita o reenvía el código de recuperación con protección Turnstile.
async function submitRequestCode(isResend) {
  clearFieldErrors()
  clearMessages()

  const identifier = validateIdentifier()
  if (!identifier) return
  if (!turnstileToken.value) {
    errors.turnstile = 'Completa la verificación de seguridad para continuar.'
    return
  }

  if (!isResend && requestCooldown.value > 0) {
    setIdentifierCooldownMessage(requestCooldown.value)
    return
  }

  if (isResend) {
    loading.resendCode = true
  } else {
    loading.requestCode = true
  }

  try {
    const action = isResend ? resendRecoveryCode : requestRecoveryCode
    const response = await action({
      identifier,
      turnstile_token: turnstileToken.value,
    })
    const data = response?.data || {}

    requestCooldown.value = 0
    if (requestIntervalId) {
      clearInterval(requestIntervalId)
      requestIntervalId = null
    }

    codeInfo.value = `Enviamos un código a ${data.identifier || 'tu correo'}. Revisa tu bandeja principal y spam.`
    startTimers(data.expires_in, data.resend_cooldown)

    form.code = ''
    sessionToken.value = ''
    step.value = 2
    codeStatus.value = 'pending'
    successMessage.value = response?.message || 'Código enviado.'
    resetTurnstile()
  } catch (error) {
    const message = parseApiError(error, 'No pudimos enviar el código en este momento.')
    const cooldownSeconds = extractCooldownSeconds(error)

    if (cooldownSeconds > 0) {
      startRequestCooldown(cooldownSeconds)
    }

    if (step.value === 1) {
      if (cooldownSeconds === 0) {
        errors.identifier = message
      }
    } else {
      globalError.value = message
    }
    resetTurnstile()
  } finally {
    loading.requestCode = false
    loading.resendCode = false
  }
}

// Verifica el código y guarda el token temporal de recuperación.
async function submitVerifyCode() {
  clearMessages()
  errors.code = ''

  const identifier = validateIdentifier()
  if (!identifier) {
    step.value = 1
    return
  }

  if (!validateCode()) return

  loading.verifyCode = true
  try {
    const response = await verifyRecoveryCode({
      identifier,
      code: form.code,
    })

    sessionToken.value = String(response?.data?.session_token || '')
    if (!sessionToken.value) {
      throw new Error('No se pudo crear una sesión de recuperación válida.')
    }

    codeStatus.value = 'valid'
    step.value = 3
    successMessage.value = response?.message || 'Código validado.'
  } catch (error) {
    errors.code = parseApiError(error, 'El código ingresado no es válido.')
  } finally {
    loading.verifyCode = false
  }
}

// Envía la nueva contraseña usando el token temporal ya verificado.
async function submitResetPassword() {
  if (step.value !== 3) return

  clearMessages()
  if (!validatePasswords()) return

  if (!sessionToken.value) {
    globalError.value = 'Necesitas validar un código antes de restablecer la contraseña.'
    step.value = 1
    return
  }

  loading.resetPassword = true
  try {
    const response = await resetRecoveryPassword({
      session_token: sessionToken.value,
      password: form.password,
      password_confirmation: form.passwordConfirmation,
    })

    successMessage.value = response?.message || 'Tu contraseña fue actualizada correctamente.'
    step.value = 1
    form.code = ''
    form.password = ''
    form.passwordConfirmation = ''
    sessionToken.value = ''
    stopTimers()

    setTimeout(() => {
      router.push({ name: 'login' })
    }, 1200)
  } catch (error) {
    globalError.value = parseApiError(error, 'No pudimos actualizar tu contraseña.')
  } finally {
    loading.resetPassword = false
  }
}

// Regresa al primer paso para corregir la cuenta sin conservar error de código.
function goBackToIdentifier() {
  clearMessages()
  errors.code = ''
  step.value = 1
}

// Limpia intervalos al salir de la vista para evitar timers huérfanos.
onBeforeUnmount(() => {
  stopTimers()
})
</script>
