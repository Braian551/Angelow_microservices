<template>
  <section class="featured-categories">
    <h2 class="section-title">Explora nuestras categorías</h2>
    <div class="categories-grid">
      <RouterLink
        v-for="category in categories"
        :key="category.id"
        :to="{ name: 'store', query: { category: category.id } }"
        class="category-card"
      >
        <img
          :src="resolveMediaUrl(category.image, 'category')"
          :alt="category.name"
          @error="onImageError($event, category.image)"
        />
        <h3>{{ category.name }}</h3>
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})

function onImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'category')
}
</script>
