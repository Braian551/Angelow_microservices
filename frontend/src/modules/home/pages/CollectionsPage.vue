<template>
  <main class="section-container">
    <section class="featured-collections collections-page">
      <h1 class="section-title">Nuestras colecciones</h1>

      <p v-if="loading" class="loading-box">Cargando colecciones...</p>
      <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <div v-else class="collections-grid">
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
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { getCollections } from '../../../services/catalogApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

const loading = ref(true)
const errorMessage = ref('')
const collections = ref([])

async function loadData() {
  loading.value = true
  errorMessage.value = ''

  try {
    const collectionsRes = await getCollections()
    collections.value = collectionsRes?.data || []
  } catch {
    errorMessage.value = 'No se pudieron cargar las colecciones.'
  } finally {
    loading.value = false
  }
}

function onImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'collection')
}

onMounted(loadData)
</script>

<style scoped>
.collections-page {
  padding: 4rem 0 6rem;
}
</style>
