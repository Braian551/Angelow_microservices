<template>
  <section class="featured-collections">
    <h2 class="section-title">Nuestras colecciones</h2>
    <div class="collections-grid">
      <RouterLink
        v-for="collection in collections"
        :key="collection.id"
        :to="{ name: 'store', query: { collection: collection.id } }"
        class="collection-card"
      >
        <img
          :src="resolveMediaUrl(collection.image, 'collection')"
          :alt="collection.name"
          @error="onImageError($event, collection.image)"
        />
        <div class="collection-overlay">
          <h3>{{ collection.name }}</h3>
          <p>{{ collection.description || 'Nuevos lanzamientos' }}</p>
        </div>
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

defineProps({
  collections: {
    type: Array,
    default: () => [],
  },
})

function onImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'collection')
}
</script>
