<template>
  <main class="register-page">
    <section class="register-card">
      <div class="register-header">
        <img src="/logo_principal.png" alt="Angelow" class="main-logo" />
        <h1>Crear cuenta</h1>
        <p class="register-subtitle">Registrate para comprar en Angelow</p>
      </div>

      <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <form @submit.prevent="submitRegister" class="step-content">
        <label>
          Nombre
          <input v-model="form.name" required />
        </label>
        <label>
          Correo
          <input v-model="form.email" type="email" required />
        </label>
        <label>
          Telefono (opcional)
          <input v-model="form.phone" />
        </label>
        <label>
          Contrasena
          <input v-model="form.password" type="password" required />
        </label>
        <label>
          Confirmar contrasena
          <input v-model="form.password_confirmation" type="password" required />
        </label>
        <button class="btn" type="submit" :disabled="submitting">Registrarme</button>
      </form>

      <p class="auth-link">
        Ya tienes cuenta?
        <RouterLink :to="{ name: 'login' }" class="auth-link__action">Inicia sesion</RouterLink>
      </p>
    </section>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { registerUser } from '../../../services/authApi'
import { useSession } from '../../../composables/useSession'
import '../views/RegisterView.css'

const router = useRouter()
const { saveSession } = useSession()

const submitting = ref(false)
const errorMessage = ref('')
const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

async function submitRegister() {
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Las contrasenas no coinciden.'
    return
  }

  submitting.value = true
  errorMessage.value = ''
  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone || null,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      terms: true,
    }

    const response = await registerUser(payload)
    const token = response?.data?.token || ''
    const authUser = response?.data?.user || null
    if (!token || !authUser) {
      throw new Error('No se pudo crear la cuenta')
    }

    saveSession(token, authUser)
    router.push({ name: 'home' })
  } catch {
    errorMessage.value = 'No se pudo completar el registro.'
  } finally {
    submitting.value = false
  }
}
</script>
