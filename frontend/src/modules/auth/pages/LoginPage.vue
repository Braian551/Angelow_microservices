<template>
  <main class="auth-page login-page">
    <!-- Barra superior mínima para volver al inicio sin salir del flujo SPA. -->
    <header class="auth-topbar">
      <RouterLink to="/" class="auth-topbar-logo" aria-label="Inicio">
        <img src="/logo_principal.png" alt="Angelow" />
      </RouterLink>
    </header>

    <section class="login-container">
      <!-- Encabezado visual del formulario de inicio de sesión. -->
      <div class="login-header">
        <div class="login-logo">
          <img src="/logo.png" alt="Angelow" />
        </div>
        <h1>Iniciar sesión</h1>
      </div>

      <!-- Indicador de dos pasos: primero identifica la cuenta y luego solicita contraseña. -->
      <div class="progress-steps">
        <div class="step" :class="{ active: step === 1, completed: step > 1 }" data-step="1">
          <div class="step-number">1</div>
          <div class="step-title">Correo/Teléfono</div>
        </div>
        <div class="step" :class="{ active: step === 2 }" data-step="2">
          <div class="step-number">2</div>
          <div class="step-title">Contraseña</div>
        </div>
        <div class="progress-bar">
          <div class="progress" :style="{ width: step === 1 ? '0%' : '70%' }" />
        </div>
      </div>

      <form class="login-form" novalidate @submit.prevent="onFormSubmit">
        <!-- Paso 1: captura correo o teléfono antes de revelar el campo de contraseña. -->
        <div class="form-step" :class="{ active: step === 1 }" data-step="1">
          <div class="form-group">
            <label for="credential">Correo electrónico o teléfono</label>
            <input
              id="credential"
              v-model.trim="form.credential"
              type="text"
              placeholder="Ej: juan@email.com o 3001234567"
              autocomplete="username"
              :class="{ error: !!errors.credential }"
              @input="onCredentialInput"
              @blur="onCredentialBlur"
              required
            />
            <div class="form-hint">Ingresa el correo o teléfono con el que te registraste</div>
            <div v-if="errors.credential" class="error-message">{{ errors.credential }}</div>
          </div>

          <button type="button" class="btn-primary" :disabled="submitting || googleSubmitting" @click="goToPasswordStep">
            Continuar
          </button>
        </div>

        <!-- Paso 2: valida contraseña, captcha condicional y envío definitivo de credenciales. -->
        <div class="form-step" :class="{ active: step === 2 }" data-step="2">
          <div class="form-group password-group">
            <label for="login-password">Contraseña</label>
            <div class="password-input-container">
              <input
                id="login-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Ingresa tu contraseña"
                autocomplete="current-password"
                :class="{ error: !!errors.password }"
                @input="onPasswordInput"
                required
              />
              <button type="button" class="toggle-password" aria-label="Mostrar contraseña" @click="showPassword = !showPassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" />
              </button>
            </div>
            <div class="form-hint">La contraseña distingue entre mayúsculas y minúsculas</div>
            <div v-if="errors.password" class="error-message">{{ errors.password }}</div>
          </div>

          <div class="login-options">
            <label class="remember-me" for="remember-login">
              <input id="remember-login" v-model="form.remember" type="checkbox" />
              <span>Recordar mi cuenta</span>
            </label>
            <RouterLink :to="{ name: 'forgot-password' }" class="forgot-password">¿Olvidaste tu contraseña?</RouterLink>
          </div>

          <TurnstileWidget
            v-if="captchaRequired"
            ref="turnstileRef"
            :site-key="turnstileSiteKey"
            :reset-key="turnstileResetKey"
            @verified="onTurnstileVerified"
            @expired="onTurnstileExpired"
            @error="onTurnstileError"
          />
          <div v-if="errors.turnstile" class="error-message">{{ errors.turnstile }}</div>

          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="submitting || googleSubmitting" @click="goToCredentialStep">
              Atrás
            </button>
            <button type="submit" class="btn-primary" :disabled="submitting || googleSubmitting">
              {{ submitting ? 'Ingresando...' : 'Iniciar sesión' }}
            </button>
          </div>
        </div>
      </form>

      <div v-if="errors.global" class="error-message global-error">{{ errors.global }}</div>

      <!-- Acceso alternativo con proveedor externo manteniendo bloqueo de doble envío. -->
      <div class="social-login">
        <p>También puedes iniciar sesión con:</p>
        <div class="social-buttons">
          <button
            type="button"
            class="social-btn google"
            :disabled="submitting || googleSubmitting"
            @click="submitGoogle"
          >
            <i class="fab fa-google" />
            <span>{{ googleSubmitting ? 'Conectando...' : 'Google' }}</span>
          </button>
        </div>
      </div>

      <div class="register-redirect">
        ¿No tienes una cuenta?
        <RouterLink :to="{ name: 'register', query: redirectQuery }" class="text-link">Regístrate</RouterLink>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { signInWithPopup } from 'firebase/auth'
import TurnstileWidget from '../../../components/security/TurnstileWidget.vue'
import { loginUser, loginWithGoogle } from '../../../services/authApi'
import { useSession } from '../../../composables/useSession'
import { firebaseAuth, googleProvider, isFirebaseReady } from '../../../services/firebase'
import { isAdminRole, resolveRoleLandingRoute } from '../../../utils/authNavigation'
import '../views/LoginView.css'

// Dependencias de navegación y sesión usadas para redirigir después del login.
const router = useRouter()
const route = useRoute()
const { saveSession } = useSession()

// Estado de control del formulario, carga, captcha y visibilidad de contraseña.
const step = ref(1)
const submitting = ref(false)
const googleSubmitting = ref(false)
const showPassword = ref(false)
const captchaRequired = ref(false)
const turnstileToken = ref('')
const turnstileResetKey = ref(0)
const turnstileRef = ref(null)
const turnstileSiteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY || ''

// Modelo reactivo del formulario de credenciales.
const form = reactive({
  credential: '',
  password: '',
  remember: false,
})

// Mensajes de validación separados por campo para feedback en tiempo real.
const errors = reactive({
  credential: '',
  password: '',
  turnstile: '',
  global: '',
})

const tempEmailDomains = ['10minutemail.com', 'tempmail.org', 'mailinator.com']

// Evita redirecciones externas o rutas internas no navegables después del login.
function isSafeRedirectPath(value) {
  const path = String(value || '').trim()
  if (!path.startsWith('/')) return false
  if (path.startsWith('//')) return false
  if (path.startsWith('/api/')) return false
  return true
}

// Conserva el query redirect solo cuando apunta a una ruta segura de la SPA.
const redirectQuery = computed(() => {
  const redirect = String(route.query.redirect || '').trim()
  return isSafeRedirectPath(redirect) ? { redirect } : {}
})

// Resuelve la ruta final de destino para usuarios normales o administrativos.
function resolveRedirect() {
  const redirect = String(route.query.redirect || '').trim()
  return isSafeRedirectPath(redirect) ? redirect : ''
}

// Limpia errores antes de validar o cambiar de paso.
function clearErrors() {
  errors.credential = ''
  errors.password = ''
  errors.turnstile = ''
  errors.global = ''
}

// Reinicia Turnstile incrementando la llave reactiva consumida por el widget.
function resetTurnstile() {
  turnstileToken.value = ''
  turnstileResetKey.value += 1
}

// Guarda el token de Turnstile recibido desde el componente de seguridad.
function onTurnstileVerified(token) {
  turnstileToken.value = token
  errors.turnstile = ''
}

// Borra el token cuando Turnstile expira para forzar una validación fresca.
function onTurnstileExpired() {
  turnstileToken.value = ''
}

// Reporta errores de Turnstile como mensaje de campo para bloquear el envío inseguro.
function onTurnstileError() {
  turnstileToken.value = ''
  errors.turnstile = 'No pudimos cargar la verificación de seguridad. Inténtalo de nuevo.'
}

// Normaliza credenciales aceptando correo válido o teléfono de 10 a 15 dígitos.
function normalizeCredential(value) {
  const raw = String(value || '').trim()
  if (!raw) return ''

  const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(raw)
  if (isEmail) {
    const domain = raw.split('@')[1]?.toLowerCase() || ''
    if (tempEmailDomains.includes(domain)) return ''
    return raw.toLowerCase()
  }

  const digits = raw.replace(/\D+/g, '')
  if (digits.length >= 10 && digits.length <= 15) return digits

  return ''
}

// Valida la credencial aplicando normalización obligatoria al avanzar.
function validateCredential() {
  return validateCredentialField({ normalize: true })
}

// Valida el campo de credencial durante input o blur, con normalización opcional.
function validateCredentialField(options = {}) {
  const { normalize = false } = options
  const raw = String(form.credential || '').trim()
  if (!raw) {
    errors.credential = 'El campo no puede estar vacío'
    return false
  }

  const normalized = normalizeCredential(raw)
  if (!normalized) {
    errors.credential = 'Debes ingresar un correo válido o un número de teléfono (10-15 dígitos)'
    return false
  }

  errors.credential = ''
  if (normalize) {
    form.credential = normalized
  }
  return true
}

// Valida la contraseña mínima antes de llamar al servicio de autenticación.
function validatePassword() {
  const password = String(form.password || '')
  if (!password) {
    errors.password = 'La contraseña es obligatoria'
    return false
  }

  if (password.length < 6) {
    errors.password = 'La contraseña debe tener al menos 6 caracteres'
    return false
  }

  errors.password = ''
  return true
}

// Revalida la credencial en cada escritura y limpia errores globales anteriores.
function onCredentialInput() {
  errors.global = ''
  validateCredentialField()
}

// Normaliza la credencial cuando el usuario sale del campo.
function onCredentialBlur() {
  validateCredentialField({ normalize: true })
}

// Revalida contraseña en tiempo real para evitar esperar al submit.
function onPasswordInput() {
  errors.global = ''
  validatePassword()
}

// Extrae un mensaje legible de errores de validación o fallas del backend.
function readErrorMessage(error, fallback) {
  const validationErrors = error?.response?.data?.errors
  if (validationErrors && typeof validationErrors === 'object') {
    const firstError = Object.values(validationErrors).flat().find(Boolean)
    if (firstError) return String(firstError)
  }

  const rawMessage = String(error?.response?.data?.message || error?.response?.data?.error || fallback)

  if (rawMessage.includes('GET method is not supported for route api/auth/login')) {
    return 'No se pudo iniciar sesión por un error de autenticación. Intenta de nuevo.'
  }

  if (rawMessage === 'Server Error') {
    return 'No se pudo iniciar sesión. Intenta de nuevo en unos segundos.'
  }

  return rawMessage
}

// Vuelve al primer paso y descarta errores del paso de contraseña.
function goToCredentialStep() {
  clearErrors()
  step.value = 1
}

// Avanza a contraseña solo si la cuenta ingresada es válida.
function goToPasswordStep() {
  clearErrors()
  if (!validateCredential()) return
  step.value = 2
}

// Persiste sesión y redirige según rol y destino solicitado.
function applySessionAndRedirect(response) {
  const token = response?.data?.token || ''
  const authUser = response?.data?.user || null

  if (!token || !authUser) {
    throw new Error('No se recibió una sesión válida')
  }

  saveSession(token, authUser)

  const isAdmin = isAdminRole(authUser)
  const redirect = resolveRedirect()

  if (isAdmin) {
    if (redirect && redirect.startsWith('/admin')) {
      router.push(redirect)
      return
    }

    router.push({ name: 'admin-dashboard' })
    return
  }

  if (redirect) {
    router.push(redirect)
    return
  }

  router.push(resolveRoleLandingRoute(authUser))
}

// Envía credenciales al auth-service y activa captcha cuando el backend lo exige.
async function submitLogin() {
  clearErrors()
  const isCredentialValid = validateCredential()
  const isPasswordValid = validatePassword()
  if (!isCredentialValid || !isPasswordValid) return
  if (captchaRequired.value && !turnstileToken.value) {
    errors.turnstile = 'Completa la verificación de seguridad para continuar.'
    return
  }

  submitting.value = true
  try {
    const payload = {
      credential: form.credential,
      password: form.password,
      remember: form.remember,
    }

    if (captchaRequired.value) {
      payload.turnstile_token = turnstileToken.value
    }

    const response = await loginUser(payload)

    applySessionAndRedirect(response)
  } catch (error) {
    if (error?.response?.data?.captcha_required) {
      captchaRequired.value = true
    }

    const message = readErrorMessage(error, 'No se pudo iniciar sesión.')
    if (message.toLowerCase().includes('credencial') || message.toLowerCase().includes('contrase')) {
      errors.password = message
    } else {
      errors.global = message
    }

    if (captchaRequired.value) {
      resetTurnstile()
    }
  } finally {
    submitting.value = false
  }
}

// Ejecuta login con Google usando Firebase como proveedor de identidad.
async function submitGoogle() {
  clearErrors()

  if (!isFirebaseReady || !firebaseAuth) {
    errors.global = 'Firebase no está configurado. Revisa las variables VITE_FIREBASE_*.'
    return
  }

  googleSubmitting.value = true

  try {
    const result = await signInWithPopup(firebaseAuth, googleProvider)
    const idToken = await result.user.getIdToken()
    const response = await loginWithGoogle({ id_token: idToken })
    applySessionAndRedirect(response)
  } catch (error) {
    errors.global = readErrorMessage(error, 'No se pudo iniciar sesión con Google.')
  } finally {
    googleSubmitting.value = false
  }
}

// Decide si el submit debe avanzar de paso o autenticar, según el estado actual.
function onFormSubmit() {
  if (step.value === 1) {
    goToPasswordStep()
    return
  }

  submitLogin()
}
</script>
