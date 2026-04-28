<template>
  <main class="auth-page recovery-page">
    <header class="auth-topbar">
      <RouterLink to="/" class="auth-topbar-logo" aria-label="Inicio">
        <img src="/logo_principal.png" alt="Angelow" />
      </RouterLink>
    </header>

    <section class="recovery-container">
      <div class="recovery-header">
        <div class="recovery-logo">
          <img src="/logo.png" alt="Angelow" />
        </div>
        <h1>Recupera tu contraseña</h1>
        <p>Usa tu correo o teléfono registrado para recibir un código seguro en segundos.</p>
      </div>

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
              required
            />
            <div class="form-hint">Enviaremos un código de 4 dígitos al dato que ingreses.</div>
            <div v-if="errors.identifier" class="error-message">{{ errors.identifier }}</div>
          </div>

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

        <div class="form-step" :class="{ active: step === 2 }" data-step="2">
          <div class="code-meta">
            <p>{{ codeInfo }}</p>
            <span class="code-status-pill" :class="codeStatusClass">{{ codeStatusText }}</span>
          </div>

          <div class="form-group">
            <label for="recovery-code">Código de verificación</label>
            <input
              id="recovery-code"
              v-model.trim="form.code"
              type="text"
              inputmode="numeric"
              maxlength="4"
              placeholder="0000"
              :class="{ error: !!errors.code }"
              autocomplete="one-time-code"
              required
            />
            <div class="form-hint">El código vence en {{ timerLabel }}</div>
            <div v-if="errors.code" class="error-message">{{ errors.code }}</div>
          </div>

          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="loading.verifyCode" @click="goBackToIdentifier">
              <i class="fas fa-arrow-left" />
              Atrás
            </button>
            <button type="button" class="btn-primary" :disabled="loading.verifyCode" @click="submitVerifyCode">
              {{ loading.verifyCode ? 'Validando...' : 'Validar código' }}
            </button>
          </div>

          <div class="resend-wrapper">
            <span>¿No llegó el correo?</span>
            <button
              type="button"
              class="link-button"
              :disabled="resendCooldown > 0 || loading.resendCode"
              @click="submitRequestCode(true)"
            >
              {{ resendButtonText }}
            </button>
          </div>
        </div>

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
import {
  requestRecoveryCode,
  resendRecoveryCode,
  resetRecoveryPassword,
  verifyRecoveryCode,
} from '../../../services/authApi'
import '../views/ForgotPasswordView.css'

const router = useRouter()

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

let timerIntervalId = null
let resendIntervalId = null
let requestIntervalId = null

const form = reactive({
  identifier: '',
  code: '',
  password: '',
  passwordConfirmation: '',
})

const loading = reactive({
  requestCode: false,
  resendCode: false,
  verifyCode: false,
  resetPassword: false,
})

const errors = reactive({
  identifier: '',
  code: '',
  password: '',
  passwordConfirmation: '',
})

const progressWidth = computed(() => {
  if (step.value <= 1) return '0%'
  if (step.value === 2) return '50%'
  return '100%'
})

const timerLabel = computed(() => formatSeconds(codeExpiresIn.value))

const codeStatusClass = computed(() => ({
  pending: codeStatus.value === 'pending',
  valid: codeStatus.value === 'valid',
  expired: codeStatus.value === 'expired',
}))

const codeStatusText = computed(() => {
  if (codeStatus.value === 'valid') return 'Código validado'
  if (codeStatus.value === 'expired') return 'Código expirado'
  return 'Pendiente de validación'
})

const requestCodeButtonText = computed(() => {
  if (loading.requestCode) return 'Enviando...'
  if (requestCooldown.value > 0) return `Enviar código (${requestCooldown.value}s)`
  return 'Enviar código'
})

const resendButtonText = computed(() => {
  if (loading.resendCode) return 'Reenviando...'
  if (resendCooldown.value > 0) return `Reenviar código (${resendCooldown.value}s)`
  return 'Reenviar código'
})

function clearFieldErrors() {
  errors.identifier = ''
  errors.code = ''
  errors.password = ''
  errors.passwordConfirmation = ''
}

function clearMessages() {
  successMessage.value = ''
  globalError.value = ''
}

function parseApiError(error, fallbackMessage) {
  const validationErrors = error?.response?.data?.errors
  if (validationErrors && typeof validationErrors === 'object') {
    const firstError = Object.values(validationErrors).flat().find(Boolean)
    if (firstError) return String(firstError)
  }

  return error?.response?.data?.message || fallbackMessage
}

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

function formatSeconds(totalSeconds) {
  const safeValue = Math.max(0, Number(totalSeconds) || 0)
  const minutes = String(Math.floor(safeValue / 60)).padStart(2, '0')
  const seconds = String(safeValue % 60).padStart(2, '0')
  return `${minutes}:${seconds}`
}

function setIdentifierCooldownMessage(seconds) {
  errors.identifier = `Ya enviamos un código recientemente. Intenta de nuevo en ${seconds} segundos.`
}

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

function normalizeIdentifier(value) {
  const input = String(value || '').trim()
  if (!input) return ''

  const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input)
  if (isEmail) return input.toLowerCase()

  const digits = input.replace(/\D+/g, '')
  if (digits.length >= 7 && digits.length <= 15) return digits

  return ''
}

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

function validateCode() {
  if (!/^[0-9]{4}$/.test(form.code)) {
    errors.code = 'El código debe tener 4 dígitos.'
    return false
  }

  errors.code = ''
  return true
}

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

async function submitRequestCode(isResend) {
  clearFieldErrors()
  clearMessages()

  const identifier = validateIdentifier()
  if (!identifier) return

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
    const response = await action({ identifier })
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
  } finally {
    loading.requestCode = false
    loading.resendCode = false
  }
}

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

function goBackToIdentifier() {
  clearMessages()
  errors.code = ''
  step.value = 1
}

onBeforeUnmount(() => {
  stopTimers()
})
</script>
