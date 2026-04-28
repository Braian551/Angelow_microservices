<template>
  <main class="product-detail-page">
    <div class="section-container">
      <p v-if="loading" class="loading-box">Cargando producto...</p>
      <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <template v-else>
        <div class="back-button-container">
          <button type="button" class="back-button" @click="goBack">
            <i class="fas fa-arrow-left" />
            Volver
          </button>
        </div>

        <section class="product-main-section">
          <div class="product-gallery">
            <div class="gallery-main">
              <img
                :src="mainImage.src"
                :alt="mainImage.alt"
                @error="onMainImageError"
                @click="openZoom(mainImage)"
              />
              <button type="button" class="zoom-btn" aria-label="Ampliar imagen" @click="openZoom(mainImage)">
                <i class="fas fa-search-plus" />
              </button>
            </div>

            <div v-if="galleryImages.length > 1" class="thumbnails-track">
              <button
                v-for="(image, index) in galleryImages"
                :key="`${image.src}-${index}`"
                type="button"
                class="thumb-item"
                :class="{ active: currentImageIndex === index }"
                @click="currentImageIndex = index"
              >
                <img :src="image.src" :alt="image.alt" @error="onThumbImageError" />
              </button>
            </div>
          </div>

          <div class="product-info">
            <div class="product-header">
              <h1 class="product-title">{{ normalizedProductName }}</h1>

              <div v-if="product.collection_name" class="product-collection">
                <span>Colección:</span>
                <RouterLink :to="collectionRoute">
                  {{ normalizeUtf8Text(product.collection_name) }}
                </RouterLink>
              </div>

              <div class="product-rating">
                <div class="stars">
                  <i v-for="(starClass, index) in starClasses" :key="`star-${index}`" :class="starClass" />
                </div>
                <span class="review-count">
                  {{ reviewCount > 0 ? `${reviewCount} opiniones` : 'Sin opiniones' }}
                </span>
              </div>
            </div>

            <div class="product-pricing">
              <span class="current-price">{{ formatPrice(activePrice) }}</span>
              <span v-if="hasComparePrice" class="original-price">{{ formatPrice(activeComparePrice) }}</span>
              <span v-if="hasComparePrice" class="discount-badge">{{ discountPercentage }}% OFF</span>
            </div>

            <div class="product-description">
              <h3>Descripción</h3>
              <p>{{ normalizedDescription }}</p>

              <div v-for="spec in productSpecs" :key="spec.label" class="product-spec">
                <span>{{ spec.label }}</span>
                <span>{{ spec.value }}</span>
              </div>
            </div>

            <div v-if="colorOptions.length > 0" class="product-variants">
              <div class="variant-selector color-selector">
                <h4>
                  Color:
                  <span>{{ selectedColorName }}</span>
                </h4>
                <div class="color-options">
                  <button
                    v-for="color in colorOptions"
                    :key="color.color_variant_id"
                    type="button"
                    class="color-option"
                    :class="{ selected: Number(selectedColorId) === Number(color.color_variant_id) }"
                    :title="normalizeUtf8Text(color.color_name || 'Color')"
                    @click="selectColor(color.color_variant_id)"
                  >
                    <span
                      class="color-swatch"
                      :style="color.color_hex ? { backgroundColor: color.color_hex } : {}"
                    >
                      {{ color.color_hex ? '' : initialColorLetter(color.color_name) }}
                    </span>
                  </button>
                </div>
              </div>

              <div class="variant-selector size-selector">
                <h4>
                  Talla:
                  <span>{{ selectedSizeName }}</span>
                </h4>
                <div class="size-options">
                  <button
                    v-for="size in sizeOptions"
                    :key="size.variant_id"
                    type="button"
                    class="size-option"
                    :class="{ selected: Number(selectedSizeVariantId) === Number(size.variant_id) }"
                    @click="selectSize(size.variant_id)"
                  >
                    {{ normalizeUtf8Text(size.size_name) }}
                  </button>
                </div>
                <button type="button" class="size-guide-link" @click="openSpecsGuide">
                  Guía de tallas
                  <i class="fas fa-arrow-right" />
                </button>
              </div>

              <div class="variant-info">
                <div class="stock-info" :class="stockClass">
                  <i class="fas" :class="stockIcon" />
                  <span>{{ stockMessage }}</span>
                </div>
              </div>
            </div>

            <div class="product-actions">
              <div class="quantity-selector">
                <button type="button" class="qty-btn" @click="changeQuantity(-1)">-</button>
                <input
                  id="product-quantity"
                  v-model.number="quantity"
                  type="number"
                  min="1"
                  :max="quantityMax"
                  @change="normalizeQuantity"
                />
                <button type="button" class="qty-btn" @click="changeQuantity(1)">+</button>
              </div>

              <div class="action-buttons">
                <button type="button" class="btn-primary" :disabled="isAddDisabled" @click="addItemToCart">
                  <i class="fas fa-shopping-cart" />
                  Agregar al carrito
                </button>
                <button type="button" class="btn-secondary" :disabled="isAddDisabled" @click="buyNow">
                  Comprar ahora
                </button>
                <button
                  type="button"
                  class="wishlist-btn"
                  :class="{ active: isFavorite }"
                  :disabled="wishlistBusy"
                  aria-label="Añadir a favoritos"
                  @click="toggleFavorite"
                >
                  <i :class="isFavorite ? 'fas fa-heart' : 'far fa-heart'" />
                </button>
              </div>

              <p v-if="infoMessage" class="status-message">{{ infoMessage }}</p>
            </div>

            <div class="shipping-info">
              <div class="info-item">
                <i class="fas fa-undo" />
                <div>
                  <span>Devoluciones gratuitas</span>
                  <span class="info-link">Conoce nuestra política</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section ref="tabsSectionRef" class="detail-tabs-section">
          <div class="detail-tabs-header">
            <button
              type="button"
              class="detail-tab-btn"
              :class="{ active: activeTab === 'description' }"
              @click="setActiveTab('description')"
            >
              <i class="fas fa-file-alt" />
              Descripción
            </button>
            <button
              type="button"
              class="detail-tab-btn"
              :class="{ active: activeTab === 'specs' }"
              @click="setActiveTab('specs')"
            >
              <i class="fas fa-list-ul" />
              Especificaciones
            </button>
            <button
              type="button"
              class="detail-tab-btn"
              :class="{ active: activeTab === 'reviews' }"
              @click="setActiveTab('reviews')"
            >
              <i class="fas fa-star" />
              Opiniones ({{ reviewCount }})
            </button>
            <button
              type="button"
              class="detail-tab-btn"
              :class="{ active: activeTab === 'questions' }"
              @click="setActiveTab('questions')"
            >
              <i class="fas fa-question-circle" />
              Preguntas ({{ questionsCount }})
            </button>
          </div>

          <div class="detail-tabs-content">
            <div v-show="activeTab === 'description'" class="detail-tab-pane">
              <h3>Detalles del producto</h3>
              <p>{{ normalizedDescription }}</p>

              <div v-if="tabDescriptionImages.length" class="detail-description-images">
                <img
                  v-for="(image, index) in tabDescriptionImages"
                  :key="`desc-image-${index}`"
                  :src="image.src"
                  :alt="image.alt"
                  @error="onImageError($event, image.rawPath)"
                  @click="openZoom(image)"
                />
              </div>
            </div>

            <div v-show="activeTab === 'specs'" class="detail-tab-pane">
              <div class="detail-specs-list">
                <div v-for="spec in tabSpecs" :key="spec.label" class="detail-spec-item">
                  <span class="detail-spec-label">{{ spec.label }}:</span>
                  <span class="detail-spec-value">{{ spec.value }}</span>
                </div>
              </div>
            </div>

            <div v-show="activeTab === 'reviews'" class="detail-tab-pane">
              <div class="questions-header reviews-header">
                <div class="reviews-header-copy">
                  <h3>Opiniones y calificaciones</h3>
                  <p>Las reseñas ayudan a otros compradores a decidirse por {{ normalizedProductName }}.</p>
                </div>
                <button
                  v-if="canShowWriteReviewButton"
                  type="button"
                  class="tab-action-btn question-btn"
                  @click="handleWriteReview"
                >
                  <i class="fas fa-pen" />
                  Escribir una opinión
                </button>
              </div>

              <div class="detail-reviews-summary">
                <div class="detail-average-rating">
                  <strong>{{ averageRating.toFixed(1) }}</strong>
                  <div class="stars">
                    <i v-for="(starClass, index) in starClasses" :key="`avg-star-${index}`" :class="starClass" />
                  </div>
                  <span>{{ reviewCount }} opiniones</span>
                </div>

                <div class="detail-rating-bars">
                  <div v-for="row in ratingRows" :key="`rating-row-${row.star}`" class="detail-rating-row">
                    <span>{{ row.star }} <i class="fas fa-star" /></span>
                    <div class="detail-bar-track">
                      <div class="detail-bar-fill" :style="{ width: `${row.percent}%` }" />
                    </div>
                    <span>{{ row.percent }}%</span>
                  </div>
                </div>
              </div>

              <div v-if="!canReview" class="review-note">
                <i class="fas fa-info-circle" />
                <template v-if="isLoggedIn">
                  Solo los clientes que han comprado este producto pueden dejar una opinión.
                </template>
                <template v-else>
                  <RouterLink :to="loginRedirectRoute">Inicia sesión</RouterLink>
                  para dejar una opinión (solo para clientes que han comprado este producto).
                </template>
              </div>

              <div v-if="reviewItems.length === 0" class="detail-empty-state">
                <i class="fas fa-comment-alt" />
                <p>Este producto aún no tiene opiniones. Sé el primero en opinar.</p>
              </div>

              <div v-else class="reviews-list">
                <article v-for="review in reviewItems" :key="`review-${review.id}`" class="review-card">
                  <div class="review-meta">
                    <div class="user-avatar">
                      <img
                        :src="review.avatar"
                        :alt="review.userName"
                        @error="onImageError($event, review.avatarRawPath, 'avatar')"
                      />
                    </div>
                    <div class="user-info">
                      <strong>{{ review.userName }}</strong>
                      <span v-if="review.isVerified" class="badge verified">Compra verificada</span>
                      <span class="time">{{ review.dateTime }}</span>
                    </div>
                  </div>

                  <div class="review-body">
                    <div class="review-head">
                      <h4 class="review-title">{{ review.title }}</h4>
                      <div class="user-rating review-stars">
                        <i
                          v-for="(starClass, index) in getRatingStars(review.rating)"
                          :key="`review-star-${review.id}-${index}`"
                          :class="starClass"
                        />
                      </div>
                    </div>
                    <p class="review-comment">{{ review.comment }}</p>
                    <div class="review-actions">
                      <button
                        type="button"
                        class="btn small helpful-btn"
                        :class="{ active: review.userHasVoted }"
                        :aria-pressed="review.userHasVoted ? 'true' : 'false'"
                      >
                        Útil ({{ review.helpfulCount }})
                      </button>
                    </div>
                  </div>
                </article>
              </div>
            </div>

            <div v-show="activeTab === 'questions'" class="detail-tab-pane">
              <div class="questions-header">
                <h3>Preguntas y respuestas</h3>
                <button
                  v-if="canAskQuestion"
                  id="ask-question-btn"
                  type="button"
                  class="tab-action-btn question-btn"
                  @click="handleAskQuestion"
                >
                  <i class="fas fa-question-circle" />
                  Hacer una pregunta
                </button>
                <button v-else type="button" class="tab-action-btn question-btn" disabled>
                  Ya preguntaste
                </button>
              </div>

              <div class="questions-list" :data-qa-questions-count="questionItems.length">
                <div v-if="questionItems.length === 0" class="no-questions">
                  <i class="fas fa-question-circle" />
                  <p>No hay preguntas sobre este producto. Sé el primero en preguntar.</p>
                </div>

                <article v-for="question in questionItems" v-else :key="`question-${question.id}`" class="question-item">
                  <div class="question-meta">
                    <div class="user-avatar">
                      <img
                        :src="question.avatar"
                        :alt="question.userName"
                        @error="onImageError($event, question.avatarRawPath, 'avatar')"
                      />
                    </div>
                    <div class="user-info">
                      <strong>{{ question.userName }}</strong>
                      <span class="time">{{ question.dateTime }}</span>
                    </div>
                  </div>
                  <div class="question-text">
                    <p>{{ question.text }}</p>
                  </div>

                  <div v-if="question.answers.length" class="answers-list">
                    <div v-for="(answer, index) in question.answers" :key="`answer-${question.id}-${index}`" class="answer-item">
                      <div class="answer-meta">
                        <div class="user-avatar small">
                          <img
                            :src="answer.avatar"
                            :alt="answer.userName"
                            @error="onImageError($event, answer.avatarRawPath, 'avatar')"
                          />
                        </div>
                        <strong>{{ answer.userName }}</strong>
                        <span v-if="answer.isSeller" class="badge seller">Vendedor</span>
                        <span class="time">{{ answer.dateTime }}</span>
                      </div>
                      <p>{{ answer.text }}</p>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </div>
        </section>
      </template>
    </div>

    <div class="detail-image-modal" :class="{ active: zoomModalOpen }" @click.self="closeZoom">
      <button type="button" class="detail-modal-close" aria-label="Cerrar" @click="closeZoom">&times;</button>
      <img :src="zoomImage.src" :alt="zoomImage.alt" @error="onZoomImageError" />
    </div>
  </main>

</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { getProductBySlug } from '../../../services/catalogApi'
import { addToCart } from '../../../services/cartApi'
import { useAppShell } from '../../../composables/useAppShell'
import { toggleWishlist } from '../../../services/wishlistApi'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useSession } from '../../../composables/useSession'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { normalizeUtf8Text } from '../../../utils/text'
import '../views/ProductDetailView.css'

const route = useRoute()
const router = useRouter()
const { sessionId, user, isLoggedIn } = useSession()
const { refreshCartCount } = useAppShell()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const errorMessage = ref('')
const infoMessage = ref('')
const product = ref({})
const variants = ref({})
const additionalImages = ref([])
const reviews = ref({ stats: { total_reviews: 0, average_rating: 0 }, reviews: [] })
const questions = ref([])
const selectedColorId = ref(null)
const selectedSizeVariantId = ref(null)
const quantity = ref(1)
const currentImageIndex = ref(0)
const wishlistBusy = ref(false)
const isFavorite = ref(false)
const activeTab = ref('description')
const tabsSectionRef = ref(null)
const zoomModalOpen = ref(false)
const zoomImage = ref({ src: '', alt: '', rawPath: '' })

const colorOptions = computed(() => Object.values(variants.value || {}))

const activeColor = computed(() => {
  if (!colorOptions.value.length) return null

  return (
    colorOptions.value.find(
      (color) => Number(color.color_variant_id) === Number(selectedColorId.value),
    ) || colorOptions.value[0]
  )
})

const sizeOptions = computed(() => Object.values(activeColor.value?.sizes || {}))

const activeSize = computed(() => {
  if (!sizeOptions.value.length) return null

  return (
    sizeOptions.value.find(
      (size) => Number(size.variant_id) === Number(selectedSizeVariantId.value),
    ) || sizeOptions.value[0]
  )
})

const normalizedProductName = computed(() => normalizeUtf8Text(product.value.name || 'Producto'))
const selectedColorName = computed(() => normalizeUtf8Text(activeColor.value?.color_name || 'No disponible'))
const selectedSizeName = computed(() => normalizeUtf8Text(activeSize.value?.size_name || 'No disponible'))

const activePrice = computed(() => Number(activeSize.value?.price || product.value.price || 0))

const activeComparePrice = computed(() => {
  const current = activePrice.value
  const variantCompare = Number(activeSize.value?.compare_price || 0)
  const productCompare = Number(product.value.compare_price || 0)

  if (variantCompare > current) return variantCompare
  if (productCompare > current) return productCompare
  return 0
})

const hasComparePrice = computed(() => activeComparePrice.value > activePrice.value)

const discountPercentage = computed(() => {
  if (!hasComparePrice.value) return 0

  const compare = activeComparePrice.value
  if (compare <= 0) return 0

  return Math.max(1, Math.round(((compare - activePrice.value) / compare) * 100))
})

const reviewStats = computed(() => reviews.value?.stats || {})
const reviewCount = computed(() => Number(reviewStats.value?.total_reviews || 0))

const averageRating = computed(() => {
  const value = Number(reviewStats.value?.average_rating || 0)
  if (Number.isNaN(value) || value < 0) return 0
  return Math.min(5, value)
})

const questionsCount = computed(() => questionItems.value.length)

const normalizedDescription = computed(() => {
  const description = normalizeUtf8Text(product.value.description || '')
  return description || 'Sin descripción disponible.'
})

const genderLabel = computed(() => {
  const normalized = String(normalizeUtf8Text(product.value.gender || '')).toLowerCase()

  if (normalized.includes('niña') || normalized.includes('nina')) return 'Niña'
  if (normalized.includes('niño') || normalized.includes('nino')) return 'Niño'
  if (normalized.includes('bebé') || normalized.includes('bebe')) return 'Bebé'
  if (normalized.includes('unisex')) return 'Unisex'

  return 'No especificado'
})

const productSpecs = computed(() => {
  const specs = []

  if (product.value.material) {
    specs.push({
      label: 'Material',
      value: normalizeUtf8Text(product.value.material),
    })
  }

  if (product.value.care_instructions) {
    specs.push({
      label: 'Cuidados',
      value: normalizeUtf8Text(product.value.care_instructions),
    })
  }

  if (genderLabel.value !== 'No especificado') {
    specs.push({
      label: 'Género',
      value: genderLabel.value,
    })
  }

  return specs
})

const tabSpecs = computed(() => {
  const specs = [
    { label: 'Material', value: normalizeUtf8Text(product.value.material || 'No especificado') },
    { label: 'Cuidados', value: normalizeUtf8Text(product.value.care_instructions || 'No especificado') },
    { label: 'Género', value: genderLabel.value },
    { label: 'Categoría', value: normalizeUtf8Text(product.value.category_name || 'No especificada') },
  ]

  if (product.value.collection_name) {
    specs.push({
      label: 'Colección',
      value: normalizeUtf8Text(product.value.collection_name),
    })
  }

  return specs
})

const activeImages = computed(() => {
  const colorImages = (activeColor.value?.images || []).map((image, index) => ({
    src: resolveMediaUrl(image.image_path, 'product'),
    rawPath: image.image_path,
    alt: normalizeUtf8Text(image.alt_text || `${normalizedProductName.value} - Imagen ${index + 1}`),
  }))

  const extraImages = (additionalImages.value || []).map((image, index) => ({
    src: resolveMediaUrl(image.image_path, 'product'),
    rawPath: image.image_path,
    alt: normalizeUtf8Text(image.alt_text || `${normalizedProductName.value} - Vista ${index + 1}`),
  }))

  const primary = {
    src: resolveMediaUrl(product.value.primary_image, 'product'),
    rawPath: product.value.primary_image,
    alt: normalizedProductName.value,
  }

  const merged = [primary, ...colorImages, ...extraImages]
  const seen = new Set()

  return merged.filter((image) => {
    const key = String(image.rawPath || image.src || '')
      .trim()
      .replace(/\\/g, '/')
      .replace(/^\/+/, '')
      .toLowerCase()

    if (!image.src || !key || seen.has(key)) return false
    seen.add(key)
    return true
  })
})

const tabDescriptionImages = computed(() => {
  const seen = new Set()

  return (additionalImages.value || [])
    .map((image, index) => ({
      src: resolveMediaUrl(image.image_path, 'product'),
      rawPath: image.image_path,
      alt: normalizeUtf8Text(image.alt_text || `${normalizedProductName.value} - Vista ${index + 1}`),
    }))
    .filter((image) => {
      const key = String(image.rawPath || image.src || '')
        .trim()
        .replace(/\\/g, '/')
        .replace(/^\/+/, '')
        .toLowerCase()

      if (!image.src || !key || seen.has(key)) return false
      seen.add(key)
      return true
    })
})

const galleryImages = computed(() => (activeImages.value.length
  ? activeImages.value
  : [{ src: resolveMediaUrl('', 'product'), rawPath: '', alt: 'Producto' }]))

const mainImage = computed(() => {
  const safeIndex = Math.min(
    Math.max(Number(currentImageIndex.value || 0), 0),
    Math.max(galleryImages.value.length - 1, 0),
  )

  return galleryImages.value[safeIndex]
})

const reviewItems = computed(() => {
  const items = Array.isArray(reviews.value?.reviews) ? reviews.value.reviews : []

  return items.map((review, index) => ({
    id: review.id ?? `${index}`,
    userId: review.user_id ? String(review.user_id) : '',
    userName: normalizeUtf8Text(review.user_name || 'Usuario'),
    avatar: resolveMediaUrl(review.user_image, 'avatar'),
    avatarRawPath: review.user_image || '',
    title: normalizeUtf8Text(review.title || 'Opinion'),
    comment: normalizeUtf8Text(review.comment || 'Sin comentarios.'),
    rating: Number(review.rating || 0),
    dateTime: formatReviewDate(review.created_at),
    isVerified: Boolean(review.is_verified),
    helpfulCount: Number(review.helpful_count || 0),
    userHasVoted: Number(review.user_has_voted || 0) === 1,
  }))
})

const questionItems = computed(() => {
  const items = Array.isArray(questions.value) ? questions.value : []

  return items.map((question, index) => ({
    id: question.id ?? `${index}`,
    userId: question.user_id ? String(question.user_id) : '',
    userName: normalizeUtf8Text(question.user_name || 'Usuario'),
    avatar: resolveMediaUrl(question.user_image, 'avatar'),
    avatarRawPath: question.user_image || '',
    text: normalizeUtf8Text(question.question || ''),
    dateTime: formatQuestionDate(question.created_at),
    answers: Array.isArray(question.answers)
      ? question.answers.map((answer) => ({
        userId: answer.user_id ? String(answer.user_id) : '',
        userName: normalizeUtf8Text(answer.user_name || 'Usuario'),
        avatar: resolveMediaUrl(answer.user_image, 'avatar'),
        avatarRawPath: answer.user_image || '',
        text: normalizeUtf8Text(answer.answer || ''),
        dateTime: formatQuestionDate(answer.created_at),
        isSeller: Boolean(answer.is_seller),
      }))
      : [],
  }))
})

const userHasReview = computed(() => Boolean(reviews.value?.user_has_review))
const canReview = computed(() => {
  if (!isLoggedIn.value) return false
  if (typeof reviews.value?.can_review === 'boolean') {
    return reviews.value.can_review
  }
  return !userHasReview.value
})
const canShowWriteReviewButton = computed(() => canReview.value && !userHasReview.value)

const userHasQuestion = computed(() => {
  if (!isLoggedIn.value || !user.value?.id) return false
  const currentUserId = String(user.value.id)
  return questionItems.value.some((question) => question.userId === currentUserId)
})

const canAskQuestion = computed(() => !userHasQuestion.value)

const loginRedirectRoute = computed(() => ({
  name: 'login',
  query: { redirect: router.currentRoute.value.fullPath },
}))

const ratingRows = computed(() => {
  const total = reviewCount.value

  const rows = [
    { star: 5, key: 'five_star' },
    { star: 4, key: 'four_star' },
    { star: 3, key: 'three_star' },
    { star: 2, key: 'two_star' },
    { star: 1, key: 'one_star' },
  ]

  return rows.map((row) => {
    const count = Number(reviewStats.value?.[row.key] || 0)
    const percentFromApi = Number(reviewStats.value?.[`${row.key}_percent`] || 0)
    const percent = total > 0 ? Math.round((count / total) * 100) : percentFromApi

    return {
      star: row.star,
      count,
      percent: Math.max(0, Math.min(100, percent)),
    }
  })
})

const starClasses = computed(() => getRatingStars(averageRating.value))

const stockQuantity = computed(() => Math.max(0, Number(activeSize.value?.quantity || 0)))

const quantityMax = computed(() => {
  if (stockQuantity.value <= 0) return 1
  return Math.max(1, Math.floor(stockQuantity.value))
})

const isAddDisabled = computed(() => !activeSize.value || stockQuantity.value <= 0)

const stockClass = computed(() => {
  if (stockQuantity.value <= 0) return 'out-of-stock'
  if (stockQuantity.value <= 5) return 'low-stock'
  return 'in-stock'
})

const stockIcon = computed(() => {
  if (stockQuantity.value <= 0) return 'fa-times-circle'
  if (stockQuantity.value <= 5) return 'fa-exclamation-circle'
  return 'fa-check-circle'
})

const stockMessage = computed(() => {
  if (stockQuantity.value <= 0) return 'Agotado'
  if (stockQuantity.value <= 5) return `Últimas ${stockQuantity.value} unidades`
  return `Disponible (${stockQuantity.value} unidades)`
})

const collectionRoute = computed(() => ({
  name: 'store',
  query: product.value.collection_id ? { collection: String(product.value.collection_id) } : {},
}))

watch(colorOptions, (items) => {
  if (!items.length) return

  if (!selectedColorId.value) {
    selectedColorId.value = items[0].color_variant_id
  }
}, { immediate: true })

watch(activeColor, () => {
  const firstSize = sizeOptions.value[0]
  selectedSizeVariantId.value = firstSize?.variant_id || null
  currentImageIndex.value = 0
  quantity.value = 1
}, { immediate: true })

watch(activeImages, () => {
  currentImageIndex.value = 0
})

watch(zoomModalOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

watch(
  () => route.params.slug,
  () => {
    loadData()
  },
)

function getRatingStars(ratingValue) {
  const value = Number(ratingValue || 0)
  const fullStars = Math.floor(value)
  const hasHalfStar = value - fullStars >= 0.5
  const stars = []

  for (let index = 0; index < 5; index += 1) {
    if (index < fullStars) {
      stars.push('fas fa-star')
    } else if (index === fullStars && hasHalfStar) {
      stars.push('fas fa-star-half-alt')
    } else {
      stars.push('far fa-star')
    }
  }

  return stars
}

function setActiveTab(tabId) {
  activeTab.value = tabId
}

function openSpecsGuide() {
  activeTab.value = 'specs'
  tabsSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function formatReviewDate(value) {
  if (!value) return ''

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  return date.toLocaleString('es-CO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  })
}

function formatQuestionDate(value) {
  if (!value) return ''

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  return date.toLocaleString('es-CO')
}

function handleWriteReview() {
  if (!isLoggedIn.value || !user.value?.id) {
    router.push(loginRedirectRoute.value)
    return
  }

  infoMessage.value = 'Proximamente podras publicar opiniones desde esta vista.'
}

function handleAskQuestion() {
  if (!isLoggedIn.value || !user.value?.id) {
    router.push(loginRedirectRoute.value)
    return
  }

  infoMessage.value = 'Proximamente podras crear preguntas desde esta vista.'
}

function goBack() {
  if (window.history.length > 1) {
    router.back()
    return
  }

  router.push({ name: 'store' })
}

function openZoom(image) {
  zoomImage.value = {
    src: image?.src || mainImage.value.src,
    alt: image?.alt || mainImage.value.alt,
    rawPath: image?.rawPath || mainImage.value.rawPath,
  }
  zoomModalOpen.value = true
}

function closeZoom() {
  zoomModalOpen.value = false
}

function onGlobalKeydown(event) {
  if (event.key === 'Escape' && zoomModalOpen.value) {
    closeZoom()
  }
}

function selectColor(colorVariantId) {
  selectedColorId.value = colorVariantId
}

function selectSize(variantId) {
  selectedSizeVariantId.value = variantId
  quantity.value = 1
}

function initialColorLetter(name) {
  const clean = String(normalizeUtf8Text(name || 'C')).trim()
  return clean ? clean.slice(0, 1).toUpperCase() : 'C'
}

function normalizeQuantity() {
  const parsed = Number(quantity.value || 1)

  if (Number.isNaN(parsed) || parsed < 1) {
    quantity.value = 1
    return
  }

  if (parsed > quantityMax.value) {
    quantity.value = quantityMax.value
    showQuantityLimitMessage()
    return
  }

  quantity.value = Math.min(parsed, quantityMax.value)
}

function changeQuantity(step) {
  if (Number(step || 0) > 0 && Number(quantity.value || 1) >= quantityMax.value) {
    showQuantityLimitMessage()
    return
  }

  const nextValue = Number(quantity.value || 1) + Number(step || 0)
  quantity.value = Math.max(1, Math.min(nextValue, quantityMax.value))
}

function onMainImageError(event) {
  handleMediaError(event, mainImage.value.rawPath || product.value.primary_image, 'product')
}

function onThumbImageError(event) {
  handleMediaError(event, product.value.primary_image, 'product')
}

function onZoomImageError(event) {
  handleMediaError(event, zoomImage.value.rawPath || product.value.primary_image, 'product')
}

function onImageError(event, originalPath, fallbackType = 'product') {
  handleMediaError(event, originalPath, fallbackType)
}

function formatPrice(value) {
  return `$${Number(value || 0).toLocaleString('es-CO')}`
}

function showQuantityLimitMessage() {
  const available = Math.max(0, Number(stockQuantity.value || 0))
  if (available <= 0) {
    infoMessage.value = 'La talla seleccionada no tiene unidades disponibles.'
    showSnackbar({
      type: 'warning',
      title: 'Sin stock',
      message: infoMessage.value,
    })
    return
  }

  const unitsLabel = available === 1 ? 'unidad' : 'unidades'
  infoMessage.value = `Solo puedes agregar hasta ${available} ${unitsLabel} en este momento.`
  showSnackbar({
    type: 'warning',
    title: 'Límite de cantidad',
    message: infoMessage.value,
  })
}

function extractErrorMessage(error, fallback) {
  const apiError = String(error?.response?.data?.error || '').trim()
  if (apiError) return apiError

  const apiMessage = String(error?.response?.data?.message || '').trim()
  if (apiMessage) return apiMessage

  return fallback
}

async function loadData() {
  loading.value = true
  errorMessage.value = ''
  infoMessage.value = ''

  try {
    const productRes = await getProductBySlug(route.params.slug, {
      user_id: user.value?.id || undefined,
      user_email: user.value?.email || undefined,
    })

    const payload = productRes?.data || {}

    product.value = payload.product || {}
    variants.value = payload.variants || {}
    additionalImages.value = payload.images || []
    reviews.value = payload.reviews || { stats: { total_reviews: 0, average_rating: 0 }, reviews: [] }
    questions.value = payload.questions || []
    isFavorite.value = Boolean(Number(product.value?.is_favorite || 0))
    selectedColorId.value = null
    selectedSizeVariantId.value = null
    quantity.value = 1
    currentImageIndex.value = 0
    activeTab.value = 'description'
  } catch {
    errorMessage.value = 'No se pudo cargar el producto.'
  } finally {
    loading.value = false
  }
}

async function addItemToCart() {
  infoMessage.value = ''

  if (!activeSize.value?.variant_id) {
    infoMessage.value = 'Selecciona una talla válida.'
    showSnackbar({
      type: 'warning',
      title: 'Selecciona una talla',
      message: 'Debes elegir una talla para agregar el producto al carrito.',
    })
    return false
  }

  if (stockQuantity.value <= 0) {
    infoMessage.value = 'La talla seleccionada no tiene stock disponible.'
    showSnackbar({
      type: 'warning',
      title: 'Sin stock',
      message: 'La talla seleccionada no tiene unidades disponibles.',
    })
    return false
  }

  normalizeQuantity()

  try {
    await addToCart({
      product_id: Number(product.value.id),
      color_variant_id: activeColor.value?.color_variant_id
        ? Number(activeColor.value.color_variant_id)
        : null,
      size_variant_id: Number(activeSize.value.variant_id),
      quantity: Number(quantity.value || 1),
      user_id: user.value?.id || null,
      session_id: user.value?.id ? null : sessionId.value,
    })

    await refreshCartCount()
    infoMessage.value = 'Producto agregado al carrito.'
    showSnackbar({
      type: 'success',
      title: 'Compra ahora lista',
      message: 'El producto se agregó al carrito correctamente.',
    })
    return true
  } catch (error) {
    const message = extractErrorMessage(error, 'No se pudo agregar al carrito.')
    infoMessage.value = message
    showSnackbar({
      type: 'error',
      title: 'No se pudo agregar',
      message,
    })
    return false
  }
}

// Flujo de compra rápida: agrega el producto y redirige al checkout.
async function buyNow() {
  const added = await addItemToCart()
  if (!added) return

  if (!isLoggedIn.value || !user.value?.id) {
    router.push({
      name: 'login',
      query: { redirect: '/checkout/envio' },
    })
    return
  }

  router.push({ name: 'shipping' })
}

async function toggleFavorite() {
  if (wishlistBusy.value || !product.value?.id) return

  if (!isLoggedIn.value || !user.value?.id) {
    router.push({
      name: 'login',
      query: { redirect: router.currentRoute.value.fullPath },
    })
    return
  }

  wishlistBusy.value = true

  try {
    const response = await toggleWishlist({
      user_id: String(user.value.id),
      user_email: String(user.value?.email || '').trim() || undefined,
      product_id: Number(product.value.id),
    })

    isFavorite.value = response?.action === 'added'
    infoMessage.value = isFavorite.value
      ? 'Producto añadido a favoritos.'
      : 'Producto eliminado de favoritos.'
    showSnackbar({
      type: 'success',
      title: 'Favoritos actualizado',
      message: infoMessage.value,
    })
  } catch {
    infoMessage.value = 'No se pudo actualizar favoritos.'
    showSnackbar({
      type: 'error',
      title: 'Error en favoritos',
      message: 'No se pudo actualizar tu lista de favoritos.',
    })
  } finally {
    wishlistBusy.value = false
  }
}

onMounted(() => {
  loadData()
  window.addEventListener('keydown', onGlobalKeydown)
})

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onGlobalKeydown)
})
</script>


