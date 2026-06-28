<template>
  <aside class="product-form-side">
    <div class="product-form-section image-panel">
      <div class="section-header">
        <div class="section-header__icon"><i class="fas fa-camera"></i></div>
        <div>
          <h3>Imagen principal</h3>
          <p>Sube o conserva una ruta existente.</p>
        </div>
        <button type="button" class="btn btn-secondary btn-sm section-header__action" title="Seleccionar imagen" @click="$emit('pick-main-image')">
          <i class="fas fa-upload"></i>
        </button>
      </div>

      <input :ref="setMainImageInput" type="file" accept="image/*" class="visually-hidden" @change="$emit('main-image-upload', $event)">

      <div class="image-panel__preview" :class="{ empty: !mainImagePreview }">
        <img v-if="mainImagePreview" :src="mainImagePreview" alt="Imagen principal" @error="$emit('product-image-error', $event, form.main_image_path)">
        <div v-else>
          <i class="fas fa-image"></i>
          <p>Sin imagen principal</p>
        </div>
      </div>

      <div class="image-panel__actions">
        <button type="button" class="btn btn-secondary btn-sm text-danger" title="Limpiar" @click="$emit('remove-main-image')">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>

    <div class="product-form-section status-panel">
      <div class="section-header">
        <div class="section-header__icon"><i class="fas fa-eye"></i></div>
        <div>
          <h3>Visibilidad</h3>
          <p>Estado y destaque del producto.</p>
        </div>
      </div>
      <AdminToggleSwitch
        id="product-active"
        v-model="form.is_active"
        class="status-option"
        title="Producto activo"
        description="Visible para catálogo y procesos internos."
      />

      <AdminToggleSwitch
        id="product-featured"
        v-model="form.is_featured"
        class="status-option"
        title="Producto destacado"
        description="Permite resaltarlo en vitrinas o listados especiales."
      />
    </div>
  </aside>
</template>

<script setup>
import AdminToggleSwitch from '../AdminToggleSwitch.vue'

/**
 * Panel lateral del formulario de producto que contiene:
 * 1. Sección de imagen principal con previsualización, carga y eliminación.
 * 2. Sección de visibilidad con toggles de producto activo y destacado.
 * Reutiliza AdminToggleSwitch para los interruptores de estado.
 * El input de archivo real se encapsula y se expone vía evento
 * para que la página padre conserve la referencia operativa.
 */
defineProps({
  form: { type: Object, required: true },
  mainImagePreview: { type: String, default: '' },
})

const emit = defineEmits([
  'main-image-upload',
  'pick-main-image',
  'product-image-error',
  'remove-main-image',
  'set-main-image-input',
])

/**
 * Captura la referencia del input de archivo de imagen principal
 * y la reenvía a la página padre para que pueda activar
 * la selección de archivo programáticamente.
 * Reutiliza el patrón de exposición de refs del AdminProductVariantsTab.
 */
function setMainImageInput(element) {
  emit('set-main-image-input', element)
}
</script>
