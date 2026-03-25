<template>
  <section class="featured-categories">
    <h2 class="section-title">Explora nuestras categorias</h2>
    <div class="categories-grid">
      <RouterLink
        v-for="category in categories"
        :key="category.id"
        :to="{ name: 'store', query: { category: category.id } }"
        class="category-card"
      >
        <img :src="resolveMediaUrl(category.image, '/logo.png')" :alt="category.name" />
        <h3>{{ category.name }}</h3>
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { resolveMediaUrl } from '../../../utils/media'

defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})
</script>
