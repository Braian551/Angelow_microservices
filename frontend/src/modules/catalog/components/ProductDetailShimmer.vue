<template>
  <div class="product-detail-shimmer" aria-busy="true" aria-label="Cargando producto">
    <div class="back-button-container">
      <span class="product-detail-shimmer__pill product-detail-shimmer__pill--back" />
    </div>

    <section class="product-main-section">
      <div class="product-gallery">
        <div class="gallery-main">
          <span class="product-detail-shimmer__image" />
        </div>

        <div class="thumbnails-track product-detail-shimmer__thumbnails">
          <span v-for="thumb in thumbnails" :key="`thumb-${thumb}`" class="product-detail-shimmer__thumb" />
        </div>
      </div>

      <div class="product-info product-detail-shimmer__content">
        <div class="product-header">
          <span class="product-detail-shimmer__line product-detail-shimmer__line--title" />
          <span class="product-detail-shimmer__pill product-detail-shimmer__pill--chip" />

          <div class="product-detail-shimmer__rating">
            <span v-for="star in stars" :key="`star-${star}`" class="product-detail-shimmer__star" />
            <span class="product-detail-shimmer__line product-detail-shimmer__line--inline" />
          </div>
        </div>

        <div class="product-pricing">
          <span class="product-detail-shimmer__line product-detail-shimmer__line--price" />
          <span class="product-detail-shimmer__pill product-detail-shimmer__pill--discount" />
        </div>

        <div class="product-description">
          <span class="product-detail-shimmer__line product-detail-shimmer__line--section" />
          <span class="product-detail-shimmer__line product-detail-shimmer__line--wide" />
          <span class="product-detail-shimmer__line product-detail-shimmer__line--wide" />
          <span class="product-detail-shimmer__line product-detail-shimmer__line--text" />
        </div>

        <div class="product-variants product-detail-shimmer__variants">
          <div class="product-detail-shimmer__stack">
            <span class="product-detail-shimmer__line product-detail-shimmer__line--label" />
            <div class="product-detail-shimmer__option-row">
              <span v-for="color in colors" :key="`color-${color}`" class="product-detail-shimmer__option product-detail-shimmer__option--circle" />
            </div>
          </div>

          <div class="product-detail-shimmer__stack">
            <span class="product-detail-shimmer__line product-detail-shimmer__line--label" />
            <div class="product-detail-shimmer__option-row">
              <span v-for="size in sizes" :key="`size-${size}`" class="product-detail-shimmer__option" />
            </div>
          </div>

          <span class="product-detail-shimmer__line product-detail-shimmer__line--wide" />
        </div>

        <div class="product-actions product-detail-shimmer__actions">
          <span class="product-detail-shimmer__quantity" />

          <div class="product-detail-shimmer__action-row">
            <span class="product-detail-shimmer__button" />
            <span class="product-detail-shimmer__button product-detail-shimmer__button--secondary" />
            <span class="product-detail-shimmer__option product-detail-shimmer__option--circle" />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
const thumbnails = [1, 2, 3, 4]
const stars = [1, 2, 3, 4, 5]
const colors = [1, 2, 3, 4]
const sizes = [1, 2, 3]
</script>