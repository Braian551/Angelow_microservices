<!--
  AdminProductFormPage.vue
  Componente de página para crear y editar productos en el panel de administración.
  Orquesta los formularios de información general, variantes, precios e inventario.
  Se adapta dinámicamente entre modo "nuevo producto" y "editar producto" según la ruta.
-->
<template>
  <div class="admin-product-form-page">
    <AdminPageHeader
      :icon="isEditing ? 'fas fa-edit' : 'fas fa-plus-circle'"
      :title="isEditing ? 'Editar producto' : 'Nuevo producto'"
      :subtitle="isEditing ? 'Actualiza la ficha, variantes y precios del producto.' : 'Crea un producto completo con información, variantes e inventario.'"
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Productos', to: '/admin/productos' }, { label: isEditing ? 'Editar' : 'Nuevo' }]"
    >
      <template #actions>
        <RouterLink to="/admin/productos" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver
        </RouterLink>
      </template>
    </AdminPageHeader>

    <AdminCard>
      <div v-if="initialLoading" class="product-form-loading">
        <AdminShimmer :rows="1" variant="banner" />
        <AdminShimmer :rows="5" />
      </div>

      <form v-else class="product-form" @submit.prevent="saveProduct">
        <div class="admin-tabs product-form-tabs">
          <button type="button" class="admin-tab" :class="{ active: activeTab === 'general' }" @click="activeTab = 'general'">
            <i class="fas fa-file-alt"></i>
            Información general
          </button>
          <button type="button" class="admin-tab" :class="{ active: activeTab === 'variants' }" @click="activeTab = 'variants'">
            <i class="fas fa-swatchbook"></i>
            Variantes y precios
            <span v-if="form.variants.length" class="tab-badge">{{ form.variants.length }}</span>
          </button>
        </div>

        <AdminProductGeneralTab
          v-show="activeTab === 'general'"
          :form="form"
          :errors="errors"
          :categories="categories"
          :collections="collections"
          :main-image-preview="mainImagePreview"
          @validate-field="validateField"
          @slug-input="handleSlugInput"
          @cop-input="handleCopInput"
          @pick-main-image="triggerMainImagePicker"
          @main-image-upload="handleMainImageUpload"
          @remove-main-image="removeMainImage"
          @product-image-error="onProductImageError"
          @set-main-image-input="setMainImageInputRef"
          @refund-policy-toggle="requestRefundPolicyToggle"
        />

        <AdminProductVariantsTab
          v-show="activeTab === 'variants'"
          :form="form"
          :errors="errors"
          :colors="colors"
          :total-size-configurations="totalSizeConfigurations"
          :total-stock="totalStock"
          :color-name="colorName"
          :color-hex="colorHex"
          :size-name="sizeName"
          :currency-label="currencyLabel"
          @add-variant="addVariant"
          @set-default-variant="setDefaultVariant"
          @open-variant-modal="openVariantModal"
          @remove-variant="removeVariant"
          @set-variant-image-input="setVariantImageInputRef"
          @pick-variant-images="triggerVariantImagePicker"
          @variant-image-upload="handleVariantImageUpload"
          @set-variant-image-primary="setVariantImagePrimary"
          @remove-variant-image="removeVariantImageItem"
          @product-image-error="onProductImageError"
        />

        <div class="product-form-footer">
          <RouterLink to="/admin/productos" class="btn btn-secondary">Cancelar</RouterLink>
          <button type="button" class="btn btn-secondary" @click="activeTab = activeTab === 'general' ? 'variants' : 'general'">
            <i class="fas fa-exchange-alt"></i> Cambiar vista
          </button>
          <button type="submit" class="btn btn-primary" :disabled="saving || !canSaveProduct">
            <i class="fas fa-save"></i> {{ saving ? 'Guardando...' : (isEditing ? 'Actualizar producto' : 'Guardar producto') }}
          </button>
        </div>
      </form>
    </AdminCard>

    <AdminProductVariantModal
      :show="variantModalOpen"
      :active-variant="activeVariant"
      :selected-size-id="selectedSizeId"
      :available-sizes="availableSizesForActiveVariant"
      :sizes="sizes"
      :color-name="colorName"
      :color-hex="colorHex"
      :size-name="sizeName"
      @close="closeVariantModal"
      @update:selected-size-id="selectedSizeId = $event"
      @add-size-row="addSizeRowToActiveVariant"
      @remove-size-row="removeSizeRow"
      @size-cop-input="handleSizeCopInput"
      @validate-size-row="validateSizeRow"
      @size-sku-input="handleSizeSkuInput"
    />

    <AdminModal
      :show="refundPolicyModalOpen"
      title="Configurar reembolso"
      icon="fas fa-rotate-left"
      subtitle="Define el plazo válido para solicitudes del cliente."
      max-width="520px"
      @close="closeRefundPolicyModal"
    >
      <div class="admin-product-form-page">
        <div class="form-group">
          <label for="refund-days">Días válidos para reembolso *</label>
          <input
            id="refund-days"
            v-model="form.refund_days"
            type="number"
            min="1"
            max="365"
            class="form-control"
            :class="{ 'is-invalid': errors.refund_days }"
            placeholder="Ej. 7"
            @input="validateField('refund_days')"
          >
          <p v-if="errors.refund_days" class="form-error">{{ errors.refund_days }}</p>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-secondary" @click="closeRefundPolicyModal">Volver</button>
        <button type="button" class="btn btn-primary" @click="confirmRefundPolicy">
          <i class="fas fa-check"></i> Activar reembolso
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
/*
  Script del componente AdminProductFormPage.
  Gestiona las importaciones, el estado reactivo del formulario de productos,
  la lógica de pestañas, variantes, imágenes y validación a través del composable useAdminProductForm.
*/
// Importaciones de enrutamiento
import { RouterLink } from 'vue-router'
// Composable principal que encapsula toda la lógica del formulario de producto
import { useAdminProductForm } from '../composables/useAdminProductForm'
// Componentes base del layout de administración
import AdminCard from '../components/AdminCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminShimmer from '../components/AdminShimmer.vue'
// Componentes específicos del formulario de producto (pestañas y modales)
import AdminProductGeneralTab from '../components/products/AdminProductGeneralTab.vue'
import AdminProductVariantModal from '../components/products/AdminProductVariantModal.vue'
import AdminProductVariantsTab from '../components/products/AdminProductVariantsTab.vue'
// Estilos del formulario de producto
import '../views/AdminProductFormPage.css'

// La página queda como orquestadora: delega estado, API y reglas al composable del formulario.
const {
  activeTab,                    // Pestaña activa actualmente (general o variants)
  activeVariant,                // Variante seleccionada actualmente para editar
  availableSizesForActiveVariant, // Tallas disponibles para la variante activa
  canSaveProduct,               // Bandera que indica si el formulario es válido para guardar
  categories,                   // Lista de categorías disponibles para asignar al producto
  closeRefundPolicyModal,       // Cierra el modal de configuración de política de reembolso
  collections,                  // Lista de colecciones disponibles para asignar al producto
  colorHex,                     // Código hexadecimal del color seleccionado
  colorName,                    // Nombre del color seleccionado para la variante
  colors,                       // Lista de colores disponibles para crear variantes
  currencyLabel,                // Etiqueta de la moneda utilizada para los precios
  errors,                       // Objeto con los mensajes de error de validación del formulario
  form,                         // Objeto reactivo que contiene todos los datos del formulario del producto
  handleCopInput,               // Maneja el cambio de precio de costo de origen del producto
  handleMainImageUpload,        // Procesa la subida de la imagen principal del producto
  handleSizeCopInput,           // Maneja el cambio de precio de costo para una talla específica
  handleSizeSkuInput,           // Maneja el cambio de SKU para una talla específica
  handleSlugInput,              // Maneja la generación del slug a partir del nombre del producto
  handleVariantImageUpload,     // Procesa la subida de imágenes para una variante del producto
  initialLoading,               // Indica si los datos iniciales del formulario están cargando
  isEditing,                    // Indica si el formulario está en modo edición (true) o creación (false)
  mainImagePreview,             // URL de vista previa de la imagen principal del producto
  removeMainImage,              // Elimina la imagen principal seleccionada del producto
  refundPolicyModalOpen,        // Controla la visibilidad del modal de política de reembolso
  removeSizeRow,                // Elimina una fila de talla de la variante activa
  removeVariant,                // Elimina una variante completa del producto
  removeVariantImageItem,       // Elimina una imagen específica de una variante
  requestRefundPolicyToggle,    // Solicita abrir el modal para configurar la política de reembolso
  saveProduct,                  // Función que envía los datos del producto al backend para crear o actualizar
  saving,                       // Indica si se está guardando el formulario actualmente
  selectedSizeId,               // ID de la talla seleccionada en el modal de variante
  setDefaultVariant,            // Marca una variante como la variante predeterminada del producto
  confirmRefundPolicy,          // Confirma y guarda la configuración de la política de reembolso
  setMainImageInputRef,         // Establece la referencia del input de imagen principal
  setVariantImageInputRef,      // Establece la referencia del input de imagen de variante
  setVariantImagePrimary,       // Marca una imagen de variante como imagen principal de la variante
  sizeName,                     // Nombre de la talla seleccionada
  sizes,                        // Lista de tallas disponibles para crear variantes
  totalSizeConfigurations,      // Total de configuraciones de talla en todas las variantes
  totalStock,                   // Stock total sumado de todas las variantes y tallas
  triggerMainImagePicker,       // Abre el selector de archivos para la imagen principal
  triggerVariantImagePicker,    // Abre el selector de archivos para las imágenes de variante
  validateField,                // Valida un campo específico del formulario y actualiza errores
  validateSizeRow,              // Valida una fila de talla dentro de la variante activa
  variantModalOpen,             // Controla la visibilidad del modal de edición de variante
  addSizeRowToActiveVariant,    // Agrega una nueva fila de talla a la variante activa
  addVariant,                   // Agrega una nueva variante al producto
  closeVariantModal,            // Cierra el modal de edición de variante
  onProductImageError,          // Maneja errores de carga de imágenes del producto
  openVariantModal,             // Abre el modal de edición de variante con la variante indicada
} = useAdminProductForm()
</script>
