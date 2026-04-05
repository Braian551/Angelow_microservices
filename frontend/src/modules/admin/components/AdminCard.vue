<template>
  <div class="admin-card">
    <div v-if="title || $slots.header" class="admin-card__header">
      <div v-if="title" class="admin-card__title">
        <i v-if="icon" :class="icon"></i>
        <h3>{{ title }}</h3>
        <p v-if="subtitle">{{ subtitle }}</p>
      </div>
      <slot name="header" />
      <div v-if="$slots.headerActions" class="admin-card__header-actions">
        <slot name="headerActions" />
      </div>
    </div>
    <div class="admin-card__body" :class="{ 'admin-card__body--flush': flush }">
      <slot />
    </div>
    <div v-if="$slots.footer" class="admin-card__footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  /** Titulo opcional de la card */
  title: { type: String, default: null },
  /** Subtitulo */
  subtitle: { type: String, default: null },
  /** Icono del titulo */
  icon: { type: String, default: null },
  /** Body sin padding (para tablas full-width) */
  flush: { type: Boolean, default: false },
})
</script>
