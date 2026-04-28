<template>
  <section class="hero-banner">
    <div v-if="loading && sliderData.length === 0" class="hero-slider-loading" aria-hidden="true">
      <div class="hero-slider-loading__media"></div>
      <div class="hero-slider-loading__content">
        <span class="hero-slider-loading__line hero-slider-loading__line--tag"></span>
        <span class="hero-slider-loading__line hero-slider-loading__line--title"></span>
        <span class="hero-slider-loading__line hero-slider-loading__line--subtitle"></span>
        <span class="hero-slider-loading__line hero-slider-loading__line--cta"></span>
      </div>
    </div>

    <div v-else class="hero-slider">
      <div
        v-for="(slide, index) in sliderData"
        :key="index"
        class="hero-slide"
        :class="{ 'is-active': index === activeIndex }"
      >
        <div class="hero-slide-media">
          <img
            :src="resolveMediaUrl(slide.image, 'slider')"
            :alt="slide.title || 'Slide'"
            :class="{ 'is-loaded': isSlideImageLoaded(index) }"
            @load="onSlideImageLoad(index)"
            @error="onImageError($event, slide.image, index)"
          />
          <div v-if="!isSlideImageLoaded(index)" class="hero-slide-shimmer"></div>
        </div>
        <!-- Degradado funcional para legibilidad del texto sobre imagen -->
        <div class="hero-gradient-overlay"></div>

        <div class="hero-content hero-content-clean" :class="{ 'is-visible': index === activeIndex && contentVisible }">
          <span v-if="slide.tag" class="hero-tag">{{ slide.tag }}</span>
          <h1 class="hero-title">{{ slide.title || 'Angelow' }}</h1>
          <p class="hero-subtitle">{{ slide.subtitle || 'Moda infantil' }}</p>
          <a :href="resolveSlideLink(slide)" class="hero-cta">
            {{ slide.button_text || 'Ver más' }}
            <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Barra de progreso autoplay -->
    <div v-if="sliderData.length > 1" class="hero-progress" :key="activeIndex"></div>

    <!-- Puntos de navegación -->
    <div v-if="sliderData.length > 1" class="hero-dots">
      <button
        v-for="(_, index) in sliderData"
        :key="index"
        type="button"
        class="hero-dot"
        :class="{ 'is-active': index === activeIndex }"
        :aria-label="`Ir al slide ${index + 1}`"
        @click="goToSlide(index)"
      />
    </div>

    <!-- Contador de slides -->
    <div v-if="sliderData.length > 1" class="hero-counter">
      <span class="hero-counter__current">{{ String(activeIndex + 1).padStart(2, '0') }}</span>
      <span class="hero-counter__sep">/</span>
      <span class="hero-counter__total">{{ String(sliderData.length).padStart(2, '0') }}</span>
    </div>

    <!-- Botones anterior / siguiente -->
    <button v-if="sliderData.length > 1" class="hero-nav hero-nav--prev" type="button" aria-label="Anterior" @click="prevSlide">
      <i class="fas fa-chevron-left"></i>
    </button>
    <button v-if="sliderData.length > 1" class="hero-nav hero-nav--next" type="button" aria-label="Siguiente" @click="nextSlide">
      <i class="fas fa-chevron-right"></i>
    </button>
  </section>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import '../../../components/home/HeroSlider.css'

const AUTOPLAY_MS = 5000

const props = defineProps({
  slides: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const sliderData = ref([])
const activeIndex = ref(0)
const contentVisible = ref(false)
const slideImageLoadedMap = ref({})
let timerId = null

watch(
  [() => props.slides, () => props.loading],
  ([newSlides, isLoading]) => {
    const hasSlides = Array.isArray(newSlides) && newSlides.length > 0
    sliderData.value = hasSlides
      ? newSlides
      : (isLoading ? [] : [{ title: 'Angelow', subtitle: 'Moda infantil premium', image: '/logo_principal.png' }])
    activeIndex.value = 0
    slideImageLoadedMap.value = {}
  },
  { immediate: true },
)

// Reinicia la animación del contenido en cada cambio de slide
watch(activeIndex, async () => {
  contentVisible.value = false
  await nextTick()
  contentVisible.value = true
}, { immediate: true })

function nextSlide() {
  if (sliderData.value.length <= 1) return
  activeIndex.value = (activeIndex.value + 1) % sliderData.value.length
}

function prevSlide() {
  if (sliderData.value.length <= 1) return
  activeIndex.value = (activeIndex.value - 1 + sliderData.value.length) % sliderData.value.length
}

function goToSlide(index) {
  if (index === activeIndex.value) return
  resetTimer()
  activeIndex.value = index
}

function resolveSlideLink(slide) {
  const raw = String(slide?.link || '').trim()
  if (!raw) return '/tienda'
  if (raw.includes('/tienda/tienda.php')) {
    return raw.replace('/tienda/tienda.php', '/tienda')
  }
  return raw
}

function isSlideImageLoaded(index) {
  return Boolean(slideImageLoadedMap.value[index])
}

function markSlideImageLoaded(index) {
  slideImageLoadedMap.value = {
    ...slideImageLoadedMap.value,
    [index]: true,
  }
}

function onSlideImageLoad(index) {
  markSlideImageLoaded(index)
}

function onImageError(event, originalPath, index) {
  handleMediaError(event, originalPath, 'slider')
  markSlideImageLoaded(index)
}

function resetTimer() {
  if (timerId) clearInterval(timerId)
  timerId = setInterval(nextSlide, AUTOPLAY_MS)
}

onMounted(() => {
  timerId = setInterval(nextSlide, AUTOPLAY_MS)
})

onBeforeUnmount(() => {
  if (timerId) clearInterval(timerId)
})
</script>
