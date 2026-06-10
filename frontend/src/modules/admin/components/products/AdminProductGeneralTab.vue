<template>
  <section class="product-form-panel">
    <div class="product-form-grid">
      <div class="product-form-main">
        <div class="product-form-section">
          <div class="section-header">
            <div class="section-header__icon"><i class="fas fa-tag"></i></div>
            <div>
              <h3>Identificación</h3>
              <p>Nombre público y URL del producto.</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="product-name">
                Nombre del producto *
                <AdminInfoTooltip text="Nombre público del producto visible en la tienda y el catálogo. Sé descriptivo y claro." />
              </label>
              <input id="product-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="$emit('validate-field', 'name')">
              <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
            </div>
            <div class="form-group">
              <label for="product-slug">
                Slug
                <AdminInfoTooltip text="Identificador único del producto en la URL. Se genera automáticamente desde el nombre o puedes editarlo manualmente." />
              </label>
              <input id="product-slug" v-model="form.slug" class="form-control" placeholder="se-genera-automaticamente" @input="$emit('slug-input')">
            </div>
          </div>
        </div>

        <div class="product-form-section">
          <div class="section-header">
            <div class="section-header__icon"><i class="fas fa-layer-group"></i></div>
            <div>
              <h3>Clasificación</h3>
              <p>Marca, género, categoría y colección.</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="product-brand">
                Marca
                <AdminInfoTooltip text="Marca o fabricante del producto. Aparece en la ficha del catálogo." />
              </label>
              <input id="product-brand" v-model="form.brand" class="form-control" placeholder="Angelow">
            </div>
            <div class="form-group">
              <label for="product-gender">
                Género
                <AdminInfoTooltip text="Público objetivo del producto. Permite filtrar y clasificar en el catálogo." />
              </label>
              <select id="product-gender" v-model="form.gender" class="form-control">
                <option value="unisex">Unisex</option>
                <option value="mujer">Mujer</option>
                <option value="hombre">Hombre</option>
                <option value="nina">Niña</option>
                <option value="nino">Niño</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="product-category">
                Categoría *
                <AdminInfoTooltip text="Categoría principal a la que pertenece el producto. Es obligatoria para guardarlo." />
              </label>
              <select id="product-category" v-model="form.category_id" class="form-control" :class="{ 'is-invalid': errors.category_id }" @change="$emit('validate-field', 'category_id')">
                <option value="">Seleccionar...</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
              <p v-if="errors.category_id" class="form-error">{{ errors.category_id }}</p>
            </div>
            <div class="form-group">
              <label for="product-collection-id">
                Colección
                <AdminInfoTooltip text="Colección o temporada a la que pertenece el producto. Opcional." />
              </label>
              <select id="product-collection-id" v-model="form.collection_id" class="form-control">
                <option value="">Sin colección</option>
                <option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="product-form-section">
          <div class="section-header">
            <div class="section-header__icon"><i class="fas fa-dollar-sign"></i></div>
            <div>
              <h3>Precios y detalles</h3>
              <p>Precio base, comparativo, material y etiqueta de colección.</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="product-price">
                Precio base *
                <AdminInfoTooltip text="Precio de venta principal del producto en pesos. Las variantes pueden tener su propio precio." />
              </label>
              <input id="product-price" v-model="form.price" type="text" inputmode="numeric" class="form-control" :class="{ 'is-invalid': errors.price }" placeholder="$68.799" @input="$emit('cop-input', 'price')">
              <p v-if="errors.price" class="form-error">{{ errors.price }}</p>
            </div>
            <div class="form-group">
              <label for="product-compare-price">
                Precio comparativo
                <AdminInfoTooltip text="Precio original o tachado que muestra el descuento al cliente. Deja vacío si no aplica." />
              </label>
              <input id="product-compare-price" v-model="form.compare_price" type="text" inputmode="numeric" class="form-control" :class="{ 'is-invalid': errors.compare_price }" placeholder="$79.900" @input="$emit('cop-input', 'compare_price')">
              <p v-if="errors.compare_price" class="form-error">{{ errors.compare_price }}</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="product-material">
                Material
                <AdminInfoTooltip text="Composición textil o material principal del producto. Visible en la ficha del catálogo." />
              </label>
              <input id="product-material" v-model="form.material" class="form-control" placeholder="Algodón, denim, cuero...">
            </div>
            <div class="form-group">
              <label for="product-collection-name">
                Etiqueta de colección
                <AdminInfoTooltip text="Texto libre de etiqueta para el producto. Ejemplo: «Drop verano 2026». Complementa el nombre de la colección." />
              </label>
              <input id="product-collection-name" v-model="form.collection" class="form-control" placeholder="Drop verano 2026">
            </div>
          </div>
        </div>

        <div class="product-form-section">
          <div class="section-header">
            <div class="section-header__icon"><i class="fas fa-align-left"></i></div>
            <div>
              <h3>Descripción y cuidado</h3>
              <p>Texto de catálogo e instrucciones para el cliente.</p>
            </div>
          </div>
          <div class="form-group">
            <label for="product-description">
              Descripción
              <AdminInfoTooltip text="Texto de venta visible al cliente en la ficha del producto. Describe materiales, silueta, fit y atributos clave." />
            </label>
            <textarea id="product-description" v-model="form.description" class="form-control" rows="5" placeholder="Cuenta materiales, silueta, fit y atributos clave."></textarea>
          </div>
          <div class="form-group">
            <label for="product-care">
              Instrucciones de cuidado
              <AdminInfoTooltip text="Indicaciones de lavado, secado y mantenimiento para el cliente. Visibles en la ficha del producto." />
            </label>
            <textarea id="product-care" v-model="form.care_instructions" class="form-control" rows="4" placeholder="Lavado, secado y recomendaciones de mantenimiento."></textarea>
          </div>
        </div>
      </div>

      <AdminProductImagePanel
        :form="form"
        :main-image-preview="mainImagePreview"
        @pick-main-image="$emit('pick-main-image')"
        @main-image-upload="$emit('main-image-upload', $event)"
        @remove-main-image="$emit('remove-main-image')"
        @product-image-error="(event, path) => $emit('product-image-error', event, path)"
        @set-main-image-input="$emit('set-main-image-input', $event)"
      />
    </div>
  </section>
</template>

<script setup>
import AdminInfoTooltip from '../AdminInfoTooltip.vue'
import AdminProductImagePanel from './AdminProductImagePanel.vue'

defineProps({
  categories: { type: Array, default: () => [] },
  collections: { type: Array, default: () => [] },
  errors: { type: Object, required: true },
  form: { type: Object, required: true },
  mainImagePreview: { type: String, default: '' },
})

defineEmits([
  'cop-input',
  'main-image-upload',
  'pick-main-image',
  'product-image-error',
  'remove-main-image',
  'set-main-image-input',
  'slug-input',
  'validate-field',
])
</script>
