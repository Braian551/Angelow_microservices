<template>
  <section class="hero-banner">
    <div class="hero-slider">
      <div
        v-for="(slide, index) in sliderData"
        :key="index"
        class="hero-slide"
        :class="{ active: index === activeIndex }"
      >
        <img
          :src="resolveMediaUrl(slide.image, 'slider')"
          :alt="slide.title || 'Slide'"
          @error="onImageError($event, slide.image)"
        />
        <div class="hero-content">
          <h1>{{ slide.title || 'Angelow' }}</h1>
          <p>{{ slide.subtitle || 'Moda infantil' }}</p>
          <a :href="resolveSlideLink(slide)" class="btn">
            {{ slide.button_text || 'Ver más' }}
          </a>
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
    <button v-if="sliderData.length > 1" class="hero-prev" type="button" aria-label="Anterior" @click="prevSlide">
      ❮
    </button>
    <button v-if="sliderData.length > 1" class="hero-next" type="button" aria-label="Siguiente" @click="nextSlide">
      ❯
    </button>
  </section>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

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

function nextSlide() {
  if (sliderData.value.length <= 1) return
  activeIndex.value = (activeIndex.value + 1) % sliderData.value.length
}

function prevSlide() {
  if (sliderData.value.length <= 1) return
  activeIndex.value = (activeIndex.value - 1 + sliderData.value.length) % sliderData.value.length
}

function resolveSlideLink(slide) {
  const raw = String(slide?.link || '').trim()
  if (!raw) return '/tienda'
  if (raw.includes('/tienda/tienda.php')) {
    return raw.replace('/tienda/tienda.php', '/tienda')
  }
  return raw
}

function onImageError(event, originalPath) {
  handleMediaError(event, originalPath, 'slider')
}

onMounted(() => {
  timerId = setInterval(() => {
    nextSlide()
  }, 5000)
})

onBeforeUnmount(() => {
  if (timerId) clearInterval(timerId)
})
</script>
