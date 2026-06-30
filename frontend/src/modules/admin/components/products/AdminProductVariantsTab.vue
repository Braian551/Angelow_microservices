<template>
  <section class="product-form-panel">
    <div class="variants-toolbar">
      <div>
        <h3>Variantes configuradas</h3>
        <p>Replica el flujo de Angelow: color, imagen y tallas con precio, stock, SKU y código de barras.</p>
      </div>
      <button type="button" class="btn btn-primary" @click="$emit('add-variant')">
        <i class="fas fa-plus"></i> Agregar variante
      </button>
    </div>

    <p v-if="errors.variants" class="form-error product-form-panel-error">{{ errors.variants }}</p>

    <div class="variant-stats-grid">
      <article class="variant-stat-card">
        <span>Variantes</span>
        <strong>{{ form.variants.length }}</strong>
      </article>
      <article class="variant-stat-card">
        <span>Tallas activas</span>
        <strong>{{ totalSizeConfigurations }}</strong>
      </article>
      <article class="variant-stat-card">
        <span>Stock total</span>
        <strong>{{ totalStock }}</strong>
      </article>
    </div>

    <div class="variant-list">
      <article v-for="(variant, index) in form.variants" :key="variant.key" class="variant-card">
        <div class="variant-card__header">
          <div>
            <p class="variant-card__eyebrow">Variante {{ index + 1 }}</p>
            <h3>{{ colorName(variant.color_id) || 'Color pendiente' }}</h3>
          </div>

          <div class="variant-card__header-actions">
            <label class="variant-default-pill">
              <input :checked="variant.is_default" type="radio" name="default-variant" @change="$emit('set-default-variant', variant.key)">
              <span>Principal</span>
            </label>

            <button type="button" class="btn btn-primary btn-sm" title="Tallas y precios" @click="$emit('open-variant-modal', variant.key)">
              <i class="fas fa-tags"></i>
            </button>

            <button type="button" class="btn btn-secondary btn-sm text-danger" title="Eliminar" :disabled="form.variants.length === 1" @click="$emit('remove-variant', variant.key)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>

        <div class="variant-card__body">
          <div v-if="variant.color_id" class="color-preview-banner">
            <span class="color-circle" :style="{ backgroundColor: colorHex(variant.color_id) }"></span>
            {{ colorName(variant.color_id) }}
          </div>

          <div class="form-group">
            <label :for="`variant-color-${variant.key}`">
              Color *
              <AdminInfoTooltip text="Color de esta variante. Cada variante corresponde a un color distinto del producto." />
            </label>
            <select :id="`variant-color-${variant.key}`" v-model="variant.color_id" class="form-control">
              <option value="">Seleccionar color...</option>
              <option v-for="color in colors" :key="color.id" :value="color.id">{{ color.name }}</option>
            </select>
          </div>

          <div class="variant-card__media">
            <div class="variant-image-grid">
              <div
                v-for="(img, imgIndex) in variant.images"
                :key="imgIndex"
                class="variant-image-thumb"
                :class="{ 'is-primary': img.is_primary }"
              >
                <img :src="img.preview" :alt="`Imagen ${imgIndex + 1}`" @error="$emit('product-image-error', $event, img.path)">
                <div class="variant-image-thumb__overlay">
                  <button type="button" class="vit-action vit-action--star" :title="img.is_primary ? 'Principal' : 'Marcar como principal'" @click="$emit('set-variant-image-primary', variant.key, imgIndex)">
                    <i class="fas fa-star"></i>
                  </button>
                  <button type="button" class="vit-action vit-action--remove" title="Quitar imagen" @click="$emit('remove-variant-image', variant.key, imgIndex)">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <span v-if="img.is_primary" class="vit-primary-badge">Principal</span>
              </div>

              <button type="button" class="variant-image-add-btn" title="Agregar imágenes" @click="$emit('pick-variant-images', variant.key)">
                <i class="fas fa-plus"></i>
                <span v-if="!variant.images.length">Agregar imágenes</span>
              </button>
            </div>
            <input :ref="(element) => setVariantImageInput(variant.key, element)" type="file" accept="image/*" multiple class="visually-hidden" @change="$emit('variant-image-upload', variant.key, $event)">
          </div>

          <div class="variant-size-summary">
            <div class="variant-size-summary__header">
              <strong>Tallas configuradas</strong>
              <span>{{ variant.sizes.length }} registro(s)</span>
            </div>

            <div v-if="variant.sizes.length" class="variant-size-pills">
              <span v-for="size in variant.sizes" :key="size.key" class="variant-size-pill">
                {{ sizeName(size.size_id) || 'Sin talla' }}
                <small>{{ currencyLabel(size.price) }} / {{ size.quantity }} und</small>
              </span>
            </div>

            <p v-else class="variant-size-summary__empty">Abre el modal para cargar precios, stock y códigos.</p>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import AdminInfoTooltip from '../AdminInfoTooltip.vue'

/**
 * Pestaña de variantes del formulario de producto.
 * Muestra el listado de variantes configuradas (color + imágenes + tallas)
 * con estadísticas resumidas (total variantes, tallas activas, stock total).
 * Cada variante permite: seleccionar color, gestionar imágenes con prioridad,
 * abrir modal de tallas/precios, marcar como variante principal y eliminar.
 * Reutiliza AdminInfoTooltip para las ayudas contextuales de cada campo.
 * La lógica de negocio se delega a la página padre mediante eventos.
 */
defineProps({
  colorHex: { type: Function, required: true },
  colorName: { type: Function, required: true },
  colors: { type: Array, default: () => [] },
  currencyLabel: { type: Function, required: true },
  errors: { type: Object, required: true },
  form: { type: Object, required: true },
  sizeName: { type: Function, required: true },
  totalSizeConfigurations: { type: Number, default: 0 },
  totalStock: { type: Number, default: 0 },
})

const emit = defineEmits([
  'add-variant',
  'open-variant-modal',
  'pick-variant-images',
  'product-image-error',
  'remove-variant',
  'remove-variant-image',
  'set-default-variant',
  'set-variant-image-input',
  'set-variant-image-primary',
  'variant-image-upload',
])

/**
 * Reenvía la referencia del input de archivo de imagen de cada variante
 * a la página padre, permitiendo la selección múltiple de imágenes
 * por variante sin perder las referencias de los inputs dinámicos.
 */
function setVariantImageInput(key, element) {
  emit('set-variant-image-input', key, element)
}
</script>
