<template>
  <section class="hero-banner">
    <div class="hero-slider">
      <div v-for="(slide, index) in sliderData" :key="index" class="hero-slide" :class="{ active: index === activeIndex }">
        <img :src="resolveMediaUrl(slide.image, '/logo_principal.png')" :alt="slide.title || 'Slide'" />
        <div class="hero-content">
          <h1>{{ slide.title || 'Angelow' }}</h1>
          <p>{{ slide.subtitle || 'Moda infantil' }}</p>
          <RouterLink :to="{ name: 'store' }" class="btn">Ver tienda</RouterLink>
        </div>
      </div>
    </div>
    <div v-if="sliderData.length > 1" class="hero-dots">
      <span
        v-for="(_, index) in sliderData"
        :key="index"
        class="dot"
        :class="{ active: index === activeIndex }"
        @click="activeIndex = index"
      />
    </div>
  </section>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { resolveMediaUrl } from '../../../utils/media'

const props = defineProps({
  slides: {
    type: Array,
    default: () => [],
  },
})

const sliderData = ref([])
const activeIndex = ref(0)
let timerId = null

watch(
  () => props.slides,
  (newSlides) => {
    sliderData.value = Array.isArray(newSlides) && newSlides.length > 0
      ? newSlides
      : [{ title: 'Angelow', subtitle: 'Moda infantil premium', image: '/logo_principal.png' }]
    activeIndex.value = 0
  },
  { immediate: true },
)

onMounted(() => {
  timerId = setInterval(() => {
    if (sliderData.value.length <= 1) return
    activeIndex.value = (activeIndex.value + 1) % sliderData.value.length
  }, 5000)
})

onBeforeUnmount(() => {
  if (timerId) clearInterval(timerId)
})
</script>
