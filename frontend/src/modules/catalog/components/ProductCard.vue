<template>
  <article class="product-card">
    <div v-if="product.is_featured" class="product-badge">Destacado</div>

    <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="product-image">
      <img :src="imageUrl" :alt="product.name" />
    </RouterLink>

    <div class="product-info">
      <span class="product-category">{{ product.category_name || 'Categoria' }}</span>
      <h3 class="product-title">
        <RouterLink :to="{ name: 'product', params: { slug: product.slug } }">
          {{ product.name }}
        </RouterLink>
      </h3>

      <div class="product-price">
        <span class="current-price">{{ formatPrice(product.price) }}</span>
        <span v-if="showComparePrice" class="original-price">{{ formatPrice(product.compare_price) }}</span>
      </div>

      <button class="view-product-btn" type="button" @click="emit('add-cart', product)">
        <i class="fas fa-cart-plus" /> Agregar
      </button>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { resolveMediaUrl } from '../../../utils/media'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['add-cart'])

const imageUrl = computed(() => resolveMediaUrl(props.product.primary_image, '/logo.png'))
const showComparePrice = computed(() => {
  const current = Number(props.product.price || 0)
  const compare = Number(props.product.compare_price || 0)
  return compare > 0 && compare > current
})

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}
</script>
