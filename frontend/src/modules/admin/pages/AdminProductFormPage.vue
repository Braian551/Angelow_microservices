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
import { RouterLink } from 'vue-router'
import { useAdminProductForm } from '../composables/useAdminProductForm'
import AdminCard from '../components/AdminCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminShimmer from '../components/AdminShimmer.vue'
import AdminProductGeneralTab from '../components/products/AdminProductGeneralTab.vue'
import AdminProductVariantModal from '../components/products/AdminProductVariantModal.vue'
import AdminProductVariantsTab from '../components/products/AdminProductVariantsTab.vue'
import '../views/AdminProductFormPage.css'

// La página queda como orquestadora: delega estado, API y reglas al composable del formulario.
const {
  activeTab,
  activeVariant,
  availableSizesForActiveVariant,
  canSaveProduct,
  categories,
  closeRefundPolicyModal,
  collections,
  colorHex,
  colorName,
  colors,
  currencyLabel,
  errors,
  form,
  handleCopInput,
  handleMainImageUpload,
  handleSizeCopInput,
  handleSizeSkuInput,
  handleSlugInput,
  handleVariantImageUpload,
  initialLoading,
  isEditing,
  mainImagePreview,
  removeMainImage,
  refundPolicyModalOpen,
  removeSizeRow,
  removeVariant,
  removeVariantImageItem,
  requestRefundPolicyToggle,
  saveProduct,
  saving,
  selectedSizeId,
  setDefaultVariant,
  confirmRefundPolicy,
  setMainImageInputRef,
  setVariantImageInputRef,
  setVariantImagePrimary,
  sizeName,
  sizes,
  totalSizeConfigurations,
  totalStock,
  triggerMainImagePicker,
  triggerVariantImagePicker,
  validateField,
  validateSizeRow,
  variantModalOpen,
  addSizeRowToActiveVariant,
  addVariant,
  closeVariantModal,
  onProductImageError,
  openVariantModal,
} = useAdminProductForm()
</script>
