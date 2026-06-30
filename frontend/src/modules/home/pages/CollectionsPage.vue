<template>
  <main class="section-container">
    <!-- Listado público de colecciones con estados de carga, error y tarjetas navegables. -->
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

// Estado local de carga y respuesta del catálogo de colecciones.
const loading = ref(true)
const errorMessage = ref('')
const collections = ref([])

// Carga las colecciones desde catalog-service para construir accesos a tienda filtrada.
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

// Aplica fallback compartido si una imagen de colección no está disponible.
function onImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'collection')
}

// Ejecuta la carga inicial cuando la página entra al DOM.
onMounted(loadData)
</script>

<style scoped>
/* Ajuste vertical propio de la página completa de colecciones. */
.collections-page {
  padding: 4rem 0 6rem;
}
</style>
