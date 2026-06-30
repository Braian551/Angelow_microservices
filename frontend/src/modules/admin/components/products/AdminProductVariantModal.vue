<template>
  <AdminModal :show="show" title="Configuración de tallas y precios" icon="fas fa-tags" subtitle="Color, tallas, precios e inventario de la variante." max-width="980px" @close="$emit('close')">
    <div class="admin-product-form-page admin-product-form-page--modal">
      <div v-if="activeVariant" class="variant-modal">
        <div class="variant-modal__intro">
          <div>
            <h3>{{ colorName(activeVariant.color_id) || 'Variante sin color' }}</h3>
            <p>Define tallas, precios comparativos, stock, SKU y código de barras.</p>

            <div v-if="activeVariant.color_id" class="color-preview-banner mt-3">
              <span class="color-circle" :style="{ backgroundColor: colorHex(activeVariant.color_id) }"></span>
              {{ colorName(activeVariant.color_id) }}
            </div>
          </div>

          <div class="variant-modal__add-size">
            <select :value="selectedSizeId" class="form-control" @change="$emit('update:selected-size-id', $event.target.value)">
              <option value="">Agregar talla...</option>
              <option v-for="size in availableSizes" :key="size.id" :value="size.id">{{ size.name }}</option>
            </select>
            <button v-show="selectedSizeId !== ''" type="button" class="btn btn-primary btn-sm" title="Agregar" @click="$emit('add-size-row')">
              <i class="fas fa-plus"></i>
            </button>
          </div>
        </div>

        <div v-if="activeVariant.sizes.length" class="variant-modal__rows">
          <article v-for="sizeRow in activeVariant.sizes" :key="sizeRow.key" class="variant-modal__row">
          <div class="variant-modal__row-top">
            <strong>{{ sizeName(sizeRow.size_id) || 'Talla pendiente' }}</strong>
            <button type="button" class="btn btn-secondary btn-sm text-danger" title="Quitar" @click="$emit('remove-size-row', activeVariant.key, sizeRow.key)">
              <i class="fas fa-trash"></i>
            </button>
          </div>

          <div class="variant-modal__grid">
            <div class="form-group">
              <label>
                Talla *
                <AdminInfoTooltip text="Talla a la que corresponde este precio e inventario." />
              </label>
              <select v-model="sizeRow.size_id" class="form-control">
                <option value="">Seleccionar...</option>
                <option v-for="size in sizes" :key="size.id" :value="size.id">{{ size.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>
                Precio *
                <AdminInfoTooltip text="Precio de venta de esta combinación color + talla. Valor en pesos." />
              </label>
              <input v-model="sizeRow.price" type="text" inputmode="numeric" class="form-control" :class="{ 'is-invalid': sizeRow.errors?.price }" placeholder="$68.799" @input="$emit('size-cop-input', sizeRow, 'price')">
              <p v-if="sizeRow.errors?.price" class="form-error">{{ sizeRow.errors.price }}</p>
            </div>
            <div class="form-group">
              <label>
                Precio comparativo
                <AdminInfoTooltip text="Precio original o tachado que muestra el descuento al cliente. Deja vacío si no hay precio anterior." />
              </label>
              <input v-model="sizeRow.compare_price" type="text" inputmode="numeric" class="form-control" :class="{ 'is-invalid': sizeRow.errors?.compare_price }" placeholder="$79.900" @input="$emit('size-cop-input', sizeRow, 'compare_price')">
              <p v-if="sizeRow.errors?.compare_price" class="form-error">{{ sizeRow.errors.compare_price }}</p>
            </div>
            <div class="form-group">
              <label>
                Stock
                <AdminInfoTooltip text="Unidades disponibles en inventario para esta variante." />
              </label>
              <input v-model="sizeRow.quantity" type="text" inputmode="numeric" class="form-control" :class="{ 'is-invalid': sizeRow.errors?.quantity }" @input="$emit('validate-size-row', sizeRow)">
              <p v-if="sizeRow.errors?.quantity" class="form-error">{{ sizeRow.errors.quantity }}</p>
            </div>
            <div class="form-group">
              <label>
                SKU
                <AdminInfoTooltip text="Código único interno de la variante. Se genera automáticamente al completar los datos o puedes editarlo." />
              </label>
              <input v-model="sizeRow.sku" class="form-control" placeholder="ANG-PIJA-GRL-DULC-ROS-XS" @input="$emit('size-sku-input', activeVariant, sizeRow)">
            </div>
            <div class="form-group">
              <label>
                Código de barras
                <AdminInfoTooltip text="Código de barras del producto para uso en escaneos físicos o etiquetado." />
              </label>
              <input v-model="sizeRow.barcode" class="form-control" placeholder="7700000000000">
            </div>
          </div>

          <div class="variant-modal__row-footer">
            <AdminToggleSwitch
              :id="`variant-size-active-${sizeRow.key}`"
              v-model="sizeRow.is_active"
              layout="inline"
              :label="sizeRow.is_active ? 'Talla activa' : 'Talla inactiva'"
            />
          </div>
          </article>
        </div>

        <div v-else class="variant-modal__empty">
          <i class="fas fa-tags"></i>
          <p>Selecciona una talla para empezar a construir la matriz de precio e inventario.</p>
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-secondary" @click="$emit('close')">Cerrar</button>
    </template>
  </AdminModal>
</template>

<script setup>
import AdminInfoTooltip from '../AdminInfoTooltip.vue'
import AdminModal from '../AdminModal.vue'
import AdminToggleSwitch from '../AdminToggleSwitch.vue'

/**
 * Modal reutilizable para configurar tallas y precios de una variante de producto.
 * Permite agregar/quitar filas de talla, definir precio, precio comparativo,
 * stock, SKU, código de barras y estado de activación por talla.
 * Reutiliza AdminModal como contenedor, AdminInfoTooltip para ayudas
 * y AdminToggleSwitch para el interruptor de talla activa/inactiva.
 * La lógica de negocio (validaciones, generación de SKU, etc.)
 * se delega a la página padre mediante eventos.
 */
defineProps({
  activeVariant: { type: Object, default: null },
  availableSizes: { type: Array, default: () => [] },
  colorHex: { type: Function, required: true },
  colorName: { type: Function, required: true },
  selectedSizeId: { type: [String, Number], default: '' },
  show: { type: Boolean, default: false },
  sizeName: { type: Function, required: true },
  sizes: { type: Array, default: () => [] },
})

defineEmits([
  'add-size-row',
  'close',
  'remove-size-row',
  'size-cop-input',
  'size-sku-input',
  'update:selected-size-id',
  'validate-size-row',
])
</script>
