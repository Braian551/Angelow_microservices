<template>
  <div class="auth-code-verification">
    <div class="code-meta">
      <p>{{ info }}</p>
      <span class="code-status-pill" :class="statusClass">{{ statusText }}</span>
    </div>

    <div class="form-group">
      <label :for="inputId">{{ label }}</label>
      <input
        :id="inputId"
        :value="code"
        type="text"
        inputmode="numeric"
        maxlength="4"
        placeholder="0000"
        autocomplete="one-time-code"
        :class="{ error: !!error }"
        required
        @input="onInput"
        @blur="$emit('blur')"
      />
      <div class="form-hint">{{ hint }}</div>
      <div v-if="error" class="error-message">{{ error }}</div>
    </div>

    <div class="resend-wrapper">
      <span>{{ resendPrompt }}</span>
      <button type="button" class="link-button" :disabled="resendDisabled" @click="$emit('resend')">
        {{ resendText }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  code: { type: String, default: '' },
  error: { type: String, default: '' },
  info: { type: String, required: true },
  timerLabel: { type: String, default: '00:00' },
  status: { type: String, default: 'pending' },
  resendText: { type: String, default: 'Reenviar código' },
  resendPrompt: { type: String, default: '¿No llegó el correo?' },
  resendDisabled: { type: Boolean, default: false },
  inputId: { type: String, default: 'auth-code' },
  label: { type: String, default: 'Código de verificación' },
})

const emit = defineEmits(['update:code', 'input', 'blur', 'resend'])

const statusClass = computed(() => ({
  pending: props.status === 'pending',
  valid: props.status === 'valid',
  expired: props.status === 'expired',
}))

const statusText = computed(() => {
  if (props.status === 'valid') return 'Código validado'
  if (props.status === 'expired') return 'Código expirado'
  return 'Pendiente de validación'
})

const hint = computed(() => `El código vence en ${props.timerLabel}`)

function onInput(event) {
  // Reutiliza la normalización numérica en cada flujo que consume el componente.
  const value = String(event.target.value || '').replace(/\D+/g, '').slice(0, 4)
  emit('update:code', value)
  emit('input', value)
}
</script>
