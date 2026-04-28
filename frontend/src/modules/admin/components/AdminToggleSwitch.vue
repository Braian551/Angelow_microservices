<template>
  <div :class="wrapperClass">
    <div v-if="layout === 'card'" class="admin-toggle-switch__copy">
      <strong>{{ title }}</strong>
      <p v-if="description">{{ description }}</p>
    </div>

    <label :for="resolvedId" class="admin-toggle-switch__control" :class="{ 'admin-toggle-switch__control--disabled': disabled }">
      <input
        :id="resolvedId"
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        @change="onChange"
      >
      <span class="admin-toggle-switch__slider" aria-hidden="true"></span>
      <span v-if="layout === 'inline'" class="admin-toggle-switch__inline-label">{{ label }}</span>
    </label>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  id: {
    type: String,
    default: '',
  },
  layout: {
    type: String,
    default: 'card',
    validator: (value) => ['card', 'inline'].includes(value),
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const fallbackId = `admin-toggle-${Math.random().toString(36).slice(2, 10)}`

const resolvedId = computed(() => props.id || fallbackId)

const wrapperClass = computed(() => [
  'admin-toggle-switch',
  props.layout === 'inline' ? 'admin-toggle-switch--inline' : 'admin-toggle-switch--card',
])

function onChange(event) {
  const nextValue = Boolean(event.target?.checked)
  emit('update:modelValue', nextValue)
  emit('change', nextValue)
}
</script>

<style scoped>
.admin-toggle-switch {
  width: 100%;
}

.admin-toggle-switch--card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.2rem 1.4rem;
  border: 1px solid var(--admin-border);
  border-radius: var(--admin-card-radius);
}

.admin-toggle-switch--inline {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.admin-toggle-switch__copy {
  flex: 1;
  min-width: 0;
}

.admin-toggle-switch__copy strong {
  color: var(--admin-text-dark);
  font-size: 1.65rem;
  line-height: 1.25;
}

.admin-toggle-switch__copy p {
  margin: 0.3rem 0 0;
  color: var(--admin-text-light);
  font-size: 1.2rem;
  line-height: 1.5;
}

.admin-toggle-switch__control {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.85rem;
  cursor: pointer;
  user-select: none;
  flex-shrink: 0;
}

.admin-toggle-switch__control input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}

.admin-toggle-switch__slider {
  position: relative;
  width: 4.4rem;
  height: 2.4rem;
  border-radius: 2.4rem;
  background: var(--admin-border);
  transition: background-color 0.2s ease;
}

.admin-toggle-switch__slider::before {
  content: '';
  position: absolute;
  left: 0.3rem;
  bottom: 0.3rem;
  width: 1.8rem;
  height: 1.8rem;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.28);
  transition: transform 0.2s ease;
}

.admin-toggle-switch__control input:checked + .admin-toggle-switch__slider {
  background: var(--admin-primary);
}

.admin-toggle-switch__control input:checked + .admin-toggle-switch__slider::before {
  transform: translateX(2rem);
}

.admin-toggle-switch__control input:focus-visible + .admin-toggle-switch__slider {
  box-shadow: 0 0 0 3px rgba(15, 122, 191, 0.2);
}

.admin-toggle-switch__inline-label {
  color: var(--admin-text-dark);
  font-size: 1.35rem;
  font-weight: 600;
  line-height: 1.35;
}

.admin-toggle-switch__control--disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .admin-toggle-switch--card {
    align-items: center;
  }

  .admin-toggle-switch__copy strong {
    font-size: 1.5rem;
  }
}
</style>
