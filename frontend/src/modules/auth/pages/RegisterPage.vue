<template>
  <main class="auth-page register-page">
    <!-- Barra superior mínima para regresar al inicio durante el alta de cuenta. -->
    <header class="auth-topbar">
      <RouterLink to="/" class="auth-topbar-logo" aria-label="Inicio">
        <img src="/logo_principal.png" alt="Angelow" />
      </RouterLink>
    </header>

    <section class="register-container">
      <!-- Encabezado del formulario de registro y marca visual de la pantalla. -->
      <div class="register-header">
        <div class="register-logo">
          <img src="/logo.png" alt="Angelow" />
        </div>
        <h1>Crea tu cuenta</h1>
      </div>

      <!-- Progreso de cinco pasos para dividir datos personales, verificación y contraseña. -->
      <div class="progress-steps">
        <div class="step" :class="{ active: step === 1, completed: step > 1 }" data-step="1">
          <div class="step-number">1</div>
          <div class="step-title">Nombre</div>
        </div>
        <div class="step" :class="{ active: step === 2, completed: step > 2 }" data-step="2">
          <div class="step-number">2</div>
          <div class="step-title">Correo</div>
        </div>
        <div class="step" :class="{ active: step === 3, completed: step > 3 }" data-step="3">
          <div class="step-number">3</div>
          <div class="step-title">Código</div>
        </div>
        <div class="step" :class="{ active: step === 4, completed: step > 4 }" data-step="4">
          <div class="step-number">4</div>
          <div class="step-title">Tel&eacute;fono</div>
        </div>
        <div class="step" :class="{ active: step === 5 }" data-step="5">
          <div class="step-number">5</div>
          <div class="step-title">Contrase&ntilde;a</div>
        </div>
        <div class="progress-bar">
          <div class="progress" :style="{ width: progressWidth }" />
        </div>
      </div>

      <form class="register-form" novalidate @submit.prevent="onFormSubmit">
        <!-- Paso 1: nombre público del cliente. -->
        <div class="form-step" :class="{ active: step === 1 }" data-step="1">
          <div class="form-group">
            <label for="register-name">Nombre completo</label>
            <input
              id="register-name"
              v-model.trim="form.name"
              type="text"
              placeholder="Ej: Juan Pérez"
              autocomplete="name"
              :class="{ error: !!errors.name }"
              @input="onNameInput"
              @blur="onNameBlur"
              required
            />
            <div class="form-hint">Así aparecerás en Angelow</div>
            <div v-if="errors.name" class="error-message">{{ errors.name }}</div>
          </div>
          <button type="button" class="btn-primary" :disabled="submitting || googleSubmitting" @click="nextStep">
            Continuar
          </button>
        </div>

        <!-- Paso 2: correo y verificación Turnstile antes de enviar código. -->
        <div class="form-step" :class="{ active: step === 2 }" data-step="2">
          <div class="form-group">
            <label for="register-email">Correo electr&oacute;nico</label>
            <input
              id="register-email"
              v-model.trim="form.email"
              type="email"
              placeholder="Ej: juan@email.com"
              autocomplete="email"
              :class="{ error: !!errors.email }"
              @input="onEmailInput"
              @blur="onEmailBlur"
              required
            />
            <div class="form-hint">Usaremos este correo para contactarte</div>
            <div v-if="errors.email" class="error-message">{{ errors.email }}</div>
          </div>
          <TurnstileWidget
            v-if="step === 2"
            ref="turnstileRef"
            :site-key="turnstileSiteKey"
            :reset-key="turnstileResetKey"
            @verified="onTurnstileVerified"
            @expired="onTurnstileExpired"
            @error="onTurnstileError"
          />
          <div v-if="errors.turnstile" class="error-message">{{ errors.turnstile }}</div>
          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="submitting || googleSubmitting" @click="prevStep">
              Atrás
            </button>
            <button type="button" class="btn-primary" :disabled="submitting || googleSubmitting || emailCodeLoading" @click="submitEmailCode(false)">
              {{ emailCodeLoading ? 'Enviando...' : 'Enviar c&oacute;digo' }}
            </button>
          </div>
        </div>

        <!-- Paso 3: validación del código recibido por correo. -->
        <div class="form-step" :class="{ active: step === 3 }" data-step="3">
          <AuthCodeVerification
            v-model:code="form.emailCode"
            input-id="register-email-code"
            :info="emailCodeInfo"
            :status="emailCodeStatus"
            :timer-label="emailTimerLabel"
            :error="errors.emailCode"
            :resend-text="emailResendButtonText"
            :resend-disabled="emailResendCooldown > 0 || emailCodeLoading"
            @input="onEmailCodeInput"
            @blur="onEmailCodeBlur"
            @resend="submitEmailCode(true)"
          />
          <TurnstileWidget
            v-if="step === 3"
            ref="resendTurnstileRef"
            :site-key="turnstileSiteKey"
            :reset-key="turnstileResetKey"
            @verified="onTurnstileVerified"
            @expired="onTurnstileExpired"
            @error="onTurnstileError"
          />
          <div v-if="errors.turnstile" class="error-message">{{ errors.turnstile }}</div>
          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="submitting || googleSubmitting || emailCodeLoading" @click="prevStep">
              Atr&aacute;s
            </button>
            <button type="button" class="btn-primary" :disabled="submitting || googleSubmitting || emailVerifyLoading" @click="submitVerifyEmailCode">
              {{ emailVerifyLoading ? 'Validando...' : 'Validar c&oacute;digo' }}
            </button>
          </div>
        </div>

        <!-- Paso 4: teléfono opcional reutilizable para login y contacto. -->
        <div class="form-step" :class="{ active: step === 4 }" data-step="4">
          <div class="form-group">
            <label for="register-phone">Tel&eacute;fono (opcional)</label>
            <input
              id="register-phone"
              v-model.trim="form.phone"
              type="tel"
              placeholder="Ej: 3001234567"
              autocomplete="tel"
              :class="{ error: !!errors.phone }"
              @input="onPhoneInput"
              @blur="onPhoneBlur"
            />
            <div class="form-hint">Podrás usarlo para iniciar sesión</div>
            <div v-if="errors.phone" class="error-message">{{ errors.phone }}</div>
          </div>
          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="submitting || googleSubmitting" @click="prevStep">
              Atrás
            </button>
            <button type="button" class="btn-primary" :disabled="submitting || googleSubmitting" @click="nextStep">
              Continuar
            </button>
          </div>
        </div>

        <!-- Paso 5: contraseña, aceptación de términos y creación definitiva de cuenta. -->
        <div class="form-step" :class="{ active: step === 5 }" data-step="5">
          <div class="form-group password-group">
            <label for="register-password">Contrase&ntilde;a</label>
            <div class="password-input-container">
              <input
                id="register-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Crea tu contraseña"
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
            <div class="form-hint">Debe tener entre 6 y 20 caracteres</div>
            <div id="password-strength-bar" :class="passwordStrengthClass" />
            <div v-if="errors.password" class="error-message">{{ errors.password }}</div>
          </div>

          <div class="form-group password-group">
            <label for="register-password-confirm">Confirmar contrase&ntilde;a</label>
            <div class="password-input-container">
              <input
                id="register-password-confirm"
                v-model="form.passwordConfirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                placeholder="Repite tu contraseña"
                autocomplete="new-password"
                :class="{ error: !!errors.passwordConfirmation }"
                @input="onPasswordConfirmationInput"
                @blur="onPasswordConfirmationBlur"
                required
              />
              <button
                type="button"
                class="toggle-password"
                aria-label="Mostrar confirmación de contraseña"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
              >
                <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'" />
              </button>
            </div>
            <div class="form-hint">Asegúrate de que coincida con la contraseña</div>
            <div v-if="errors.passwordConfirmation" class="error-message">{{ errors.passwordConfirmation }}</div>
          </div>

          <div class="terms-container" :class="{ error: !!errors.terms }">
            <input id="register-terms" v-model="form.terms" type="checkbox" required @change="onTermsChange" />
            <label for="register-terms">
              Acepto los
              <RouterLink :to="{ name: 'terms-and-conditions' }" class="legal-link" @click.stop>
                Términos y condiciones
              </RouterLink>
              y las Políticas de privacidad de Angelow
            </label>
          </div>
          <div v-if="errors.terms" class="error-message">{{ errors.terms }}</div>

          <TurnstileWidget
            ref="turnstileRef"
            :site-key="turnstileSiteKey"
            :reset-key="turnstileResetKey"
            @verified="onTurnstileVerified"
            @expired="onTurnstileExpired"
            @error="onTurnstileError"
          />
          <div v-if="errors.turnstile" class="error-message">{{ errors.turnstile }}</div>

          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="submitting || googleSubmitting" @click="prevStep">
              Atrás
            </button>
            <button type="submit" class="btn-primary" :disabled="submitting || googleSubmitting">
              {{ submitting ? 'Creando...' : 'Crear cuenta' }}
            </button>
          </div>
        </div>
      </form>

      <div v-if="errors.global" class="error-message global-error">{{ errors.global }}</div>

      <!-- Registro alternativo con Google, bloqueado mientras haya otro envío en curso. -->
      <div class="social-login">
        <p>Tambi&eacute;n puedes registrarte con:</p>
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

      <div class="login-redirect">
        &iquest;Ya tienes una cuenta?
        <RouterLink :to="{ name: 'login', query: redirectQuery }" class="text-link">Inicia sesi&oacute;n</RouterLink>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { signInWithPopup } from 'firebase/auth'
import AuthCodeVerification from '../components/AuthCodeVerification.vue'
import TurnstileWidget from '../../../components/security/TurnstileWidget.vue'
import { loginWithGoogle, registerUser, requestRegistrationCode, resendRegistrationCode, verifyRegistrationCode } from '../../../services/authApi'
import { useSession } from '../../../composables/useSession'
import { firebaseAuth, googleProvider, isFirebaseReady } from '../../../services/firebase'
import '../views/RegisterView.css'

// Dependencias de sesión y navegación para continuar después del registro.
const router = useRouter()
const route = useRoute()
const { saveSession } = useSession()

// Estado principal del flujo de pasos, cargas, captcha y verificación de correo.
const step = ref(1)
const submitting = ref(false)
const googleSubmitting = ref(false)
const emailCodeLoading = ref(false)
const emailVerifyLoading = ref(false)
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const turnstileToken = ref('')
const turnstileResetKey = ref(0)
const turnstileRef = ref(null)
const resendTurnstileRef = ref(null)
const turnstileSiteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY || ''
const emailCodeInfo = ref('Revisa tu bandeja de entrada y escribe el código que te enviamos.')
const emailCodeStatus = ref('pending')
const emailCodeExpiresIn = ref(0)
const emailResendCooldown = ref(0)
const registrationToken = ref('')

// Referencias de intervalos para limpiar temporizadores al salir de la vista.
let emailTimerIntervalId = null
let emailResendIntervalId = null

// Modelo reactivo con todos los campos del registro escalonado.
const form = reactive({
  name: '',
  email: '',
  emailCode: '',
  phone: '',
  password: '',
  passwordConfirmation: '',
  terms: false,
})

// Errores por campo para validación en tiempo real y mensajes del backend.
const errors = reactive({
  name: '',
  email: '',
  emailCode: '',
  phone: '',
  password: '',
  passwordConfirmation: '',
  terms: '',
  turnstile: '',
  global: '',
})

// Banderas de interacción que evitan mostrar errores antes de que el usuario toque un campo.
const touched = reactive({
  name: false,
  email: false,
  emailCode: false,
  phone: false,
  password: false,
  passwordConfirmation: false,
  terms: false,
})

// Calcula el ancho visual de la barra según el paso actual del registro.
const progressWidth = computed(() => {
  if (step.value <= 1) return '0%'
  if (step.value === 2) return '25%'
  if (step.value === 3) return '50%'
  if (step.value === 4) return '75%'
  return '100%'
})

// Formatea el tiempo restante del código de correo.
const emailTimerLabel = computed(() => formatSeconds(emailCodeExpiresIn.value))

// Ajusta el texto del reenvío según carga y cooldown activo.
const emailResendButtonText = computed(() => {
  if (emailCodeLoading.value) return 'Reenviando...'
  if (emailResendCooldown.value > 0) return `Reenviar código (${emailResendCooldown.value}s)`
  return 'Reenviar código'
})

// Clasifica la fortaleza de contraseña con una regla visual simple.
const passwordStrengthClass = computed(() => {
  const value = form.password || ''
  if (!value) return ''
  if (value.length < 8) return 'weak'
  if (value.length < 12) return 'medium'
  return 'strong'
})

// Evita redirecciones externas o rutas API después de crear la cuenta.
function isSafeRedirectPath(value) {
  const path = String(value || '').trim()
  if (!path.startsWith('/')) return false
  if (path.startsWith('//')) return false
  if (path.startsWith('/api/')) return false
  return true
}

// Propaga el redirect seguro hacia enlaces entre login y registro.
const redirectQuery = computed(() => {
  const redirect = String(route.query.redirect || '').trim()
  return isSafeRedirectPath(redirect) ? { redirect } : {}
})

// Devuelve el destino seguro solicitado antes de entrar al registro.
function resolveRedirect() {
  const redirect = String(route.query.redirect || '').trim()
  return isSafeRedirectPath(redirect) ? redirect : ''
}

// Limpia el teléfono para persistir solo dígitos válidos.
function sanitizePhone(value) {
  return String(value || '').replace(/\D+/g, '')
}

// Limpia todos los errores de campo antes de un envío final.
function resetFieldErrors() {
  errors.name = ''
  errors.email = ''
  errors.emailCode = ''
  errors.phone = ''
  errors.password = ''
  errors.passwordConfirmation = ''
  errors.terms = ''
  errors.turnstile = ''
}

// Limpia únicamente los errores del paso actual para no borrar contexto de otros pasos.
function clearStepErrors(stepNumber) {
  if (stepNumber === 1) errors.name = ''
  if (stepNumber === 2) errors.email = ''
  if (stepNumber === 3) {
    errors.emailCode = ''
    errors.turnstile = ''
  }
  if (stepNumber === 4) errors.phone = ''
  if (stepNumber === 5) {
    errors.password = ''
    errors.passwordConfirmation = ''
    errors.terms = ''
    errors.turnstile = ''
  }
}

// Reinicia Turnstile y descarta el token actual.
function resetTurnstile() {
  turnstileToken.value = ''
  turnstileResetKey.value += 1
}

// Convierte segundos a etiqueta mm:ss para los temporizadores de código.
function formatSeconds(totalSeconds) {
  const safeValue = Math.max(0, Number(totalSeconds) || 0)
  const minutes = String(Math.floor(safeValue / 60)).padStart(2, '0')
  const seconds = String(safeValue % 60).padStart(2, '0')
  return `${minutes}:${seconds}`
}

// Detiene los intervalos activos para evitar fugas al cambiar de pantalla o reiniciar código.
function stopEmailTimers() {
  if (emailTimerIntervalId) {
    clearInterval(emailTimerIntervalId)
    emailTimerIntervalId = null
  }

  if (emailResendIntervalId) {
    clearInterval(emailResendIntervalId)
    emailResendIntervalId = null
  }
}

// Inicia temporizador de expiración y cooldown de reenvío para el código de correo.
function startEmailTimers(expiresIn, cooldown) {
  stopEmailTimers()
  emailCodeExpiresIn.value = Math.max(0, Number(expiresIn) || 900)
  emailResendCooldown.value = Math.max(0, Number(cooldown) || 60)
  emailCodeStatus.value = 'pending'

  emailTimerIntervalId = setInterval(() => {
    if (emailCodeExpiresIn.value <= 1) {
      emailCodeExpiresIn.value = 0
      emailCodeStatus.value = 'expired'
      clearInterval(emailTimerIntervalId)
      emailTimerIntervalId = null
      return
    }

    emailCodeExpiresIn.value -= 1
  }, 1000)

  emailResendIntervalId = setInterval(() => {
    if (emailResendCooldown.value <= 1) {
      emailResendCooldown.value = 0
      clearInterval(emailResendIntervalId)
      emailResendIntervalId = null
      return
    }

    emailResendCooldown.value -= 1
  }, 1000)
}

// Guarda el token validado por Turnstile para el siguiente envío seguro.
function onTurnstileVerified(token) {
  turnstileToken.value = token
  errors.turnstile = ''
}

// Borra el token cuando Turnstile expira y obliga a obtener uno nuevo.
function onTurnstileExpired() {
  turnstileToken.value = ''
}

// Muestra un error de seguridad cuando Turnstile falla.
function onTurnstileError() {
  turnstileToken.value = ''
  errors.turnstile = 'No pudimos cargar la verificación de seguridad. Inténtalo de nuevo.'
}

// Mapea errores de validación del backend hacia el paso y campo correspondiente.
function applyValidationErrors(validationErrors) {
  if (!validationErrors || typeof validationErrors !== 'object') {
    return false
  }

  let assigned = false
  const first = (key) => {
    const value = validationErrors[key]
    if (Array.isArray(value) && value.length > 0) return String(value[0])
    return ''
  }

  const nameError = first('name')
  const emailError = first('email')
  const registrationTokenError = first('registration_token')
  const phoneError = first('phone')
  const passwordError = first('password')
  const termsError = first('terms')

  if (nameError) {
    errors.name = nameError
    step.value = 1
    assigned = true
  }

  if (emailError) {
    errors.email = emailError
    step.value = 2
    assigned = true
  }

  if (registrationTokenError) {
    errors.emailCode = registrationTokenError
    step.value = 3
    assigned = true
  }

  if (phoneError) {
    errors.phone = phoneError
    step.value = 4
    assigned = true
  }

  if (passwordError) {
    errors.password = passwordError
    step.value = 5
    assigned = true
  }

  if (termsError) {
    errors.terms = termsError
    step.value = 5
    assigned = true
  }

  return assigned
}

// Lee mensajes de error priorizando validaciones por campo devueltas por la API.
function readErrorMessage(error, fallback) {
  const validationErrors = error?.response?.data?.errors
  if (applyValidationErrors(validationErrors)) {
    return ''
  }

  return (
    error?.response?.data?.message
    || error?.response?.data?.error
    || fallback
  )
}

// Ejecuta la validación que corresponde al paso activo.
function validateCurrentStep() {
  errors.global = ''
  clearStepErrors(step.value)

  if (step.value === 1) {
    return validateName(true)
  }

  if (step.value === 2) {
    return validateEmail(true)
  }

  if (step.value === 3) {
    return validateEmailCode(true)
  }

  if (step.value === 4) {
    return validatePhone(true)
  }

  const isPasswordValid = validatePassword(true)
  const isConfirmationValid = validatePasswordConfirmation(true)
  const isTermsValid = validateTerms(true)
  return isPasswordValid && isConfirmationValid && isTermsValid
}

// Avanza un paso solo cuando la validación local permite continuar.
function nextStep() {
  if (!validateCurrentStep()) return
  step.value = Math.min(step.value + 1, 5)
}

// Retrocede un paso manteniendo el formulario capturado.
function prevStep() {
  errors.global = ''
  step.value = Math.max(step.value - 1, 1)
}

// Valida nombre mínimo después de interacción o cuando el avance fuerza la revisión.
function validateName(force = false) {
  if (!force && !touched.name) return true

  const value = String(form.name || '').trim()
  errors.name = value.length >= 2 ? '' : 'Ingresa un nombre válido para continuar.'
  return !errors.name
}

// Normaliza y valida correo electrónico antes de solicitar código.
function validateEmail(force = false) {
  if (!force && !touched.email) return true

  const value = String(form.email || '').trim().toLowerCase()
  form.email = value
  const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
  errors.email = isValidEmail ? '' : 'Ingresa un correo electrónico válido.'
  return !errors.email
}

// Restringe el código de correo a cuatro dígitos.
function validateEmailCode(force = false) {
  if (!force && !touched.emailCode) return true

  form.emailCode = String(form.emailCode || '').replace(/\D+/g, '').slice(0, 4)
  errors.emailCode = /^[0-9]{4}$/.test(form.emailCode)
    ? ''
    : 'El código debe tener 4 dígitos.'
  return !errors.emailCode
}

// Limpia y valida teléfono opcional dentro del rango aceptado.
function validatePhone(force = false) {
  if (!force && !touched.phone) return true

  const phone = sanitizePhone(form.phone)
  form.phone = phone
  errors.phone = phone && (phone.length < 10 || phone.length > 15)
    ? 'El teléfono debe tener entre 10 y 15 dígitos.'
    : ''
  return !errors.phone
}

// Valida longitud de contraseña de acuerdo con el contrato del formulario.
function validatePassword(force = false) {
  if (!force && !touched.password) return true

  const value = String(form.password || '')
  errors.password = value.length >= 6 && value.length <= 20
    ? ''
    : 'La contraseña debe tener entre 6 y 20 caracteres.'
  return !errors.password
}

// Confirma que la repetición coincida con la contraseña principal.
function validatePasswordConfirmation(force = false) {
  if (!force && !touched.passwordConfirmation) return true

  errors.passwordConfirmation = form.password === form.passwordConfirmation
    ? ''
    : 'Las contraseñas no coinciden.'
  return !errors.passwordConfirmation
}

// Verifica aceptación de términos antes de permitir el registro.
function validateTerms(force = false) {
  if (!force && !touched.terms) return true

  errors.terms = form.terms ? '' : 'Debes aceptar los términos y condiciones.'
  return !errors.terms
}

// Marca el nombre como tocado y revalida en tiempo real.
function onNameInput() {
  touched.name = true
  errors.global = ''
  validateName()
}

// Fuerza validación del nombre al salir del campo.
function onNameBlur() {
  touched.name = true
  validateName(true)
}

// Reinicia la verificación de correo cuando cambia el email.
function onEmailInput() {
  touched.email = true
  errors.global = ''
  registrationToken.value = ''
  form.emailCode = ''
  emailCodeStatus.value = 'pending'
  validateEmail()
}

// Normaliza y valida el correo al salir del campo.
function onEmailBlur() {
  touched.email = true
  validateEmail(true)
}

// Valida el código mientras el usuario escribe.
function onEmailCodeInput() {
  touched.emailCode = true
  errors.global = ''
  validateEmailCode()
}

// Fuerza la validación completa del código al salir del campo.
function onEmailCodeBlur() {
  touched.emailCode = true
  validateEmailCode(true)
}

// Valida teléfono en tiempo real después de interacción.
function onPhoneInput() {
  touched.phone = true
  errors.global = ''
  validatePhone()
}

// Fuerza validación del teléfono al salir del campo.
function onPhoneBlur() {
  touched.phone = true
  validatePhone(true)
}

// Revalida contraseña y confirmación cuando la contraseña cambia.
function onPasswordInput() {
  touched.password = true
  errors.global = ''
  validatePassword()
  if (touched.passwordConfirmation) {
    validatePasswordConfirmation(true)
  }
}

// Fuerza validación de contraseña al salir del campo.
function onPasswordBlur() {
  touched.password = true
  validatePassword(true)
  if (touched.passwordConfirmation) {
    validatePasswordConfirmation(true)
  }
}

// Revalida confirmación de contraseña mientras se escribe.
function onPasswordConfirmationInput() {
  touched.passwordConfirmation = true
  errors.global = ''
  validatePasswordConfirmation()
}

// Fuerza validación de confirmación al salir del campo.
function onPasswordConfirmationBlur() {
  touched.passwordConfirmation = true
  validatePasswordConfirmation(true)
}

// Marca términos como revisados y valida el checkbox.
function onTermsChange() {
  touched.terms = true
  validateTerms(true)
}

// Guarda la sesión creada y redirige al destino seguro o al inicio.
function applySessionAndRedirect(response) {
  const token = response?.data?.token || ''
  const authUser = response?.data?.user || null

  if (!token || !authUser) {
    throw new Error('No se recibió una sesión válida')
  }

  saveSession(token, authUser)

  const redirect = resolveRedirect()
  if (redirect) {
    router.push(redirect)
    return
  }

  router.push({ name: 'home' })
}

// Extrae mensajes de error del flujo de código de correo.
function parseCodeError(error, fallback) {
  const validationErrors = error?.response?.data?.errors
  if (validationErrors && typeof validationErrors === 'object') {
    const firstError = Object.values(validationErrors).flat().find(Boolean)
    if (firstError) return String(firstError)
  }

  return error?.response?.data?.message || fallback
}

// Solicita o reenvía el código de registro usando Turnstile como protección.
async function submitEmailCode(isResend) {
  errors.email = ''
  errors.emailCode = ''
  errors.turnstile = ''
  errors.global = ''

  if (!validateEmail(true)) return
  if (!turnstileToken.value) {
    errors.turnstile = 'Completa la verificación de seguridad para continuar.'
    return
  }

  emailCodeLoading.value = true
  try {
    const action = isResend ? resendRegistrationCode : requestRegistrationCode
    const response = await action({
      email: form.email,
      turnstile_token: turnstileToken.value,
    })
    const data = response?.data || {}

    form.emailCode = ''
    registrationToken.value = ''
    emailCodeInfo.value = `Enviamos un código a ${data.identifier || 'tu correo'}. Revisa tu bandeja principal y spam.`
    startEmailTimers(data.expires_in, data.resend_cooldown)
    step.value = 3
    resetTurnstile()
  } catch (error) {
    const message = parseCodeError(error, 'No pudimos enviar el código en este momento.')
    if (step.value === 2) {
      errors.email = message
    } else {
      errors.emailCode = message
    }
    resetTurnstile()
  } finally {
    emailCodeLoading.value = false
  }
}

// Verifica el código y obtiene el token temporal necesario para crear la cuenta.
async function submitVerifyEmailCode() {
  errors.emailCode = ''
  errors.global = ''

  if (!validateEmail(true) || !validateEmailCode(true)) return

  emailVerifyLoading.value = true
  try {
    const response = await verifyRegistrationCode({
      email: form.email,
      code: form.emailCode,
    })
    registrationToken.value = String(response?.data?.registration_token || '')
    if (!registrationToken.value) {
      throw new Error('No se pudo validar el correo electrónico.')
    }

    emailCodeStatus.value = 'valid'
    step.value = 4
  } catch (error) {
    errors.emailCode = parseCodeError(error, 'El código ingresado no es válido.')
  } finally {
    emailVerifyLoading.value = false
  }
}

// Envía el registro final con token de correo y token de seguridad vigentes.
async function submitRegister() {
  resetFieldErrors()
  errors.global = ''

  if (!validateCurrentStep()) return
  if (!registrationToken.value) {
    errors.emailCode = 'Verifica tu correo electrónico antes de crear la cuenta.'
    step.value = 3
    return
  }
  if (!turnstileToken.value) {
    errors.turnstile = 'Completa la verificación de seguridad para continuar.'
    return
  }

  submitting.value = true

  try {
    const phone = sanitizePhone(form.phone)
    const response = await registerUser({
      name: form.name,
      email: form.email,
      phone: phone || null,
      password: form.password,
      password_confirmation: form.passwordConfirmation,
      terms: form.terms,
      turnstile_token: turnstileToken.value,
      registration_token: registrationToken.value,
    })

    applySessionAndRedirect(response)
  } catch (error) {
    const message = readErrorMessage(error, 'No se pudo completar el registro.')
    if (message) errors.global = message
    resetTurnstile()
  } finally {
    submitting.value = false
  }
}

// Registra o autentica con Google reutilizando el flujo común de sesión.
async function submitGoogle() {
  resetFieldErrors()
  errors.global = ''

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
    const message = readErrorMessage(error, 'No se pudo iniciar sesión con Google.')
    if (message) errors.global = message
  } finally {
    googleSubmitting.value = false
  }
}

// Orquesta el submit según el paso activo del registro.
function onFormSubmit() {
  if (step.value === 2) {
    submitEmailCode(false)
    return
  }

  if (step.value === 3) {
    submitVerifyEmailCode()
    return
  }

  if (step.value < 5) {
    nextStep()
    return
  }

  submitRegister()
}

// Limpia temporizadores pendientes al desmontar la vista.
onBeforeUnmount(() => {
  stopEmailTimers()
})
</script>
