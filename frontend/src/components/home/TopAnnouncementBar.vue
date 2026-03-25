<template>
  <div v-if="announcement?.message" class="announcement-bar">
    <p>
      <i v-if="announcement.icon" class="fas" :class="announcement.icon" />
      {{ announcement.message }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  announcement: {
    type: Object,
    default: null,
  },
})
</script>
