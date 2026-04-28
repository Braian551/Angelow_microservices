<template>
  <main class="auth-page register-page">
    <header class="auth-topbar">
      <RouterLink to="/" class="auth-topbar-logo" aria-label="Inicio">
        <img src="/logo_principal.png" alt="Angelow" />
      </RouterLink>
    </header>

    <section class="register-container">
      <div class="register-header">
        <div class="register-logo">
          <img src="/logo.png" alt="Angelow" />
        </div>
        <h1>Crea tu cuenta</h1>
      </div>

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
          <div class="step-title">Tel&eacute;fono</div>
        </div>
        <div class="step" :class="{ active: step === 4 }" data-step="4">
          <div class="step-number">4</div>
          <div class="step-title">Contrase&ntilde;a</div>
        </div>
        <div class="progress-bar">
          <div class="progress" :style="{ width: progressWidth }" />
        </div>
      </div>

      <form class="register-form" novalidate @submit.prevent="onFormSubmit">
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
          <div class="step-buttons">
            <button type="button" class="btn-outline" :disabled="submitting || googleSubmitting" @click="prevStep">
              Atrás
            </button>
            <button type="button" class="btn-primary" :disabled="submitting || googleSubmitting" @click="nextStep">
              Continuar
            </button>
          </div>
        </div>

        <div class="form-step" :class="{ active: step === 3 }" data-step="3">
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

        <div class="form-step" :class="{ active: step === 4 }" data-step="4">
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
              Acepto los Términos y condiciones y las Políticas de privacidad de Angelow
            </label>
          </div>
          <div v-if="errors.terms" class="error-message">{{ errors.terms }}</div>

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
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { signInWithPopup } from 'firebase/auth'
import { loginWithGoogle, registerUser } from '../../../services/authApi'
import { useSession } from '../../../composables/useSession'
import { firebaseAuth, googleProvider, isFirebaseReady } from '../../../services/firebase'
import '../views/RegisterView.css'

const router = useRouter()
const route = useRoute()
const { saveSession } = useSession()

const step = ref(1)
const submitting = ref(false)
const googleSubmitting = ref(false)
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  passwordConfirmation: '',
  terms: false,
})

const errors = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  passwordConfirmation: '',
  terms: '',
  global: '',
})

const touched = reactive({
  name: false,
  email: false,
  phone: false,
  password: false,
  passwordConfirmation: false,
  terms: false,
})

const progressWidth = computed(() => {
  if (step.value <= 1) return '0%'
  if (step.value === 2) return '33%'
  if (step.value === 3) return '66%'
  return '100%'
})

const passwordStrengthClass = computed(() => {
  const value = form.password || ''
  if (!value) return ''
  if (value.length < 8) return 'weak'
  if (value.length < 12) return 'medium'
  return 'strong'
})

function isSafeRedirectPath(value) {
  const path = String(value || '').trim()
  if (!path.startsWith('/')) return false
  if (path.startsWith('//')) return false
  if (path.startsWith('/api/')) return false
  return true
}

const redirectQuery = computed(() => {
  const redirect = String(route.query.redirect || '').trim()
  return isSafeRedirectPath(redirect) ? { redirect } : {}
})

function resolveRedirect() {
  const redirect = String(route.query.redirect || '').trim()
  return isSafeRedirectPath(redirect) ? redirect : ''
}

function sanitizePhone(value) {
  return String(value || '').replace(/\D+/g, '')
}

function resetFieldErrors() {
  errors.name = ''
  errors.email = ''
  errors.phone = ''
  errors.password = ''
  errors.passwordConfirmation = ''
  errors.terms = ''
}

function clearStepErrors(stepNumber) {
  if (stepNumber === 1) errors.name = ''
  if (stepNumber === 2) errors.email = ''
  if (stepNumber === 3) errors.phone = ''
  if (stepNumber === 4) {
    errors.password = ''
    errors.passwordConfirmation = ''
    errors.terms = ''
  }
}

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

  if (phoneError) {
    errors.phone = phoneError
    step.value = 3
    assigned = true
  }

  if (passwordError) {
    errors.password = passwordError
    step.value = 4
    assigned = true
  }

  if (termsError) {
    errors.terms = termsError
    step.value = 4
    assigned = true
  }

  return assigned
}

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
    return validatePhone(true)
  }

  const isPasswordValid = validatePassword(true)
  const isConfirmationValid = validatePasswordConfirmation(true)
  const isTermsValid = validateTerms(true)
  return isPasswordValid && isConfirmationValid && isTermsValid
}

function nextStep() {
  if (!validateCurrentStep()) return
  step.value = Math.min(step.value + 1, 4)
}

function prevStep() {
  errors.global = ''
  step.value = Math.max(step.value - 1, 1)
}

function validateName(force = false) {
  if (!force && !touched.name) return true

  const value = String(form.name || '').trim()
  errors.name = value.length >= 2 ? '' : 'Ingresa un nombre válido para continuar.'
  return !errors.name
}

function validateEmail(force = false) {
  if (!force && !touched.email) return true

  const value = String(form.email || '').trim().toLowerCase()
  form.email = value
  const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
  errors.email = isValidEmail ? '' : 'Ingresa un correo electrónico válido.'
  return !errors.email
}

function validatePhone(force = false) {
  if (!force && !touched.phone) return true

  const phone = sanitizePhone(form.phone)
  form.phone = phone
  errors.phone = phone && (phone.length < 10 || phone.length > 15)
    ? 'El teléfono debe tener entre 10 y 15 dígitos.'
    : ''
  return !errors.phone
}

function validatePassword(force = false) {
  if (!force && !touched.password) return true

  const value = String(form.password || '')
  errors.password = value.length >= 6 && value.length <= 20
    ? ''
    : 'La contraseña debe tener entre 6 y 20 caracteres.'
  return !errors.password
}

function validatePasswordConfirmation(force = false) {
  if (!force && !touched.passwordConfirmation) return true

  errors.passwordConfirmation = form.password === form.passwordConfirmation
    ? ''
    : 'Las contraseñas no coinciden.'
  return !errors.passwordConfirmation
}

function validateTerms(force = false) {
  if (!force && !touched.terms) return true

  errors.terms = form.terms ? '' : 'Debes aceptar los términos y condiciones.'
  return !errors.terms
}

function onNameInput() {
  touched.name = true
  errors.global = ''
  validateName()
}

function onNameBlur() {
  touched.name = true
  validateName(true)
}

function onEmailInput() {
  touched.email = true
  errors.global = ''
  validateEmail()
}

function onEmailBlur() {
  touched.email = true
  validateEmail(true)
}

function onPhoneInput() {
  touched.phone = true
  errors.global = ''
  validatePhone()
}

function onPhoneBlur() {
  touched.phone = true
  validatePhone(true)
}

function onPasswordInput() {
  touched.password = true
  errors.global = ''
  validatePassword()
  if (touched.passwordConfirmation) {
    validatePasswordConfirmation(true)
  }
}

function onPasswordBlur() {
  touched.password = true
  validatePassword(true)
  if (touched.passwordConfirmation) {
    validatePasswordConfirmation(true)
  }
}

function onPasswordConfirmationInput() {
  touched.passwordConfirmation = true
  errors.global = ''
  validatePasswordConfirmation()
}

function onPasswordConfirmationBlur() {
  touched.passwordConfirmation = true
  validatePasswordConfirmation(true)
}

function onTermsChange() {
  touched.terms = true
  validateTerms(true)
}

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

async function submitRegister() {
  resetFieldErrors()
  errors.global = ''

  if (!validateCurrentStep()) return

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
    })

    applySessionAndRedirect(response)
  } catch (error) {
    const message = readErrorMessage(error, 'No se pudo completar el registro.')
    if (message) errors.global = message
  } finally {
    submitting.value = false
  }
}

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

function onFormSubmit() {
  if (step.value < 4) {
    nextStep()
    return
  }

  submitRegister()
}
</script>
