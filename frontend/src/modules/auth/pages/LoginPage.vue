<template>
  <main class="login-page">
    <section class="login-card">
      <div class="login-header">
        <img src="/logo_principal.png" alt="Angelow" class="main-logo" />
        <h1>Iniciar sesion</h1>
        <p class="login-subtitle">Accede a tu cuenta</p>
      </div>

      <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <form @submit.prevent="submitLogin">
        <label>
          Correo o telefono
          <input v-model="form.credential" required />
        </label>
        <label>
          Contrasena
          <input v-model="form.password" type="password" required />
        </label>
        <button class="btn" type="submit" :disabled="submitting">Ingresar</button>
      </form>

      <p class="auth-link">
        No tienes cuenta?
        <RouterLink :to="{ name: 'register' }" class="auth-link__action">Registrate</RouterLink>
      </p>
    </section>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { loginUser } from '../../../services/authApi'
import { useSession } from '../../../composables/useSession'
import '../views/LoginView.css'

const router = useRouter()
const { saveSession } = useSession()

const submitting = ref(false)
const errorMessage = ref('')
const form = ref({
  credential: '',
  password: '',
})

async function submitLogin() {
  submitting.value = true
  errorMessage.value = ''
  try {
    const response = await loginUser(form.value)
    const token = response?.data?.token || ''
    const authUser = response?.data?.user || null
    if (!token || !authUser) {
      throw new Error('Credenciales invalidas')
    }

    saveSession(token, authUser)
    router.push({ name: 'home' })
  } catch {
    errorMessage.value = 'No se pudo iniciar sesion.'
  } finally {
    submitting.value = false
  }
}
</script>
