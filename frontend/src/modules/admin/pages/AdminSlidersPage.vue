<template>
  <div class="admin-sliders-page">
    <AdminPageHeader
      icon="fas fa-images"
      title="Sliders"
      subtitle="Administra el carrusel principal con el mismo flujo del panel y mensajes claros en cada acción."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Sliders' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo slider
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="sliderStats" />

    <AdminCard title="Bandeja de sliders" icon="fas fa-images" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="4" :columns="['line', 'thumb', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="sliders.length === 0"
        icon="fas fa-images"
        title="Sin sliders"
        description="Agrega el primer slide del carrusel para mostrar promociones, colecciones o accesos directos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table sliders-table">
          <thead>
            <tr>
              <th>Orden</th>
              <th>Vista previa</th>
              <th>Contenido</th>
              <th>Enlace</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="slider in sliders"
              :key="slider.id"
              draggable="true"
              class="slider-row"
              :class="{
                'slider-row--dragging': dragState.draggingId === slider.id,
                'slider-row--over': dragState.overId === slider.id && dragState.draggingId !== slider.id,
              }"
              @dragstart="onRowDragStart(slider, $event)"
              @dragenter.prevent="onRowDragEnter(slider)"
              @dragover.prevent="onRowDragOver(slider)"
              @drop.prevent="onRowDrop(slider)"
              @dragend="onRowDragEnd"
            >
              <td class="slider-td-order">
                <div class="slider-order-cell">
                  <button class="slider-drag-handle" type="button" title="Arrastra para mover" aria-label="Arrastra para mover">
                    <i class="fas fa-grip-vertical"></i>
                  </button>
                  <span class="slider-order-num">{{ slider.sort_order }}</span>
                </div>
              </td>
              <td>
                <div class="admin-slider-thumb">
                  <img :src="resolveMediaUrl(slider.image, 'slider')" :alt="slider.title" @error="handleMediaError($event, slider.image, 'slider')">
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ slider.title }}</strong>
                  <span>{{ slider.subtitle || 'Sin subtítulo configurado' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ slider.link || '/tienda' }}</strong>
                  <span>{{ slider.link ? 'Destino configurado' : 'Redirige al catálogo general' }}</span>
                </div>
              </td>
              <td>
                <button class="status-badge status-badge--button" :class="slider.active ? 'active' : 'rejected'" type="button" @click="toggleSliderStatus(slider)">
                  {{ slider.active ? 'Activo' : 'Inactivo' }}
                </button>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" title="Editar slider" @click="openEditModal(slider)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar slider" @click="confirmDeleteSlider(slider)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showModal" :title="editingSliderId ? 'Editar slider' : 'Nuevo slider'" max-width="920px" @close="closeModal">
      <div class="admin-sliders-page admin-sliders-page--modal">
        <div class="admin-editor-grid slider-editor-grid">
          <div>
            <div class="form-group">
              <label for="slider-title">
                Título *
                <AdminInfoTooltip text="Texto principal del slide visible sobre la imagen. Ejemplos: «Nueva colección», «60% OFF»." />
              </label>
              <input id="slider-title" v-model.trim="form.title" type="text" class="form-control" :class="{ 'is-invalid': formErrors.title }" @input="validateField('title')">
              <p v-if="formErrors.title" class="form-error">{{ formErrors.title }}</p>
            </div>

            <div class="form-group">
              <label for="slider-subtitle">
                Subtítulo
                <AdminInfoTooltip text="Texto complementario que aparece bajo el título. Puede ampliar el mensaje principal." />
              </label>
              <input id="slider-subtitle" v-model.trim="form.subtitle" type="text" class="form-control" @input="validateField('subtitle')">
            </div>

            <div class="form-group">
              <label for="slider-link">
                Destino del slider
                <AdminInfoTooltip text="Página de la tienda a la que lleva el slider al hacer clic. Elige «Otro enlace personalizado» si necesitas una URL específica." />
              </label>
              <select id="slider-link" v-model="selectedLinkOption" class="form-control" @change="onLinkOptionChange(selectedLinkOption)">
                <optgroup v-for="group in linkOptionGroups" :key="group.label" :label="group.label">
                  <option v-for="page in group.options" :key="page.value" :value="page.value">{{ page.label }}</option>
                </optgroup>
              </select>
              <small class="slider-link-help">Usa un destino rápido o escribe una ruta propia para campañas, productos o páginas específicas.</small>
              <input
                v-if="selectedLinkOption === CUSTOM_STORE_LINK_VALUE"
                v-model.trim="form.link"
                type="text"
                class="form-control mt-1"
                :class="{ 'is-invalid': formErrors.link }"
                placeholder="Ej. /producto/vestido-verano, /tienda?collection=12 o https://..."
                @input="validateField('link')"
              >
              <small v-if="selectedLinkOption === CUSTOM_STORE_LINK_VALUE" class="slider-link-help slider-link-help--muted">Puedes enlazar a un producto por slug, a una colección concreta o a una URL externa.</small>
              <p v-if="formErrors.link" class="form-error">{{ formErrors.link }}</p>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="slider-order">
                  Orden *
                  <AdminInfoTooltip text="Posición en el carrusel. Los slides con número menor aparecen primero." />
                </label>
                <input id="slider-order" v-model.number="form.sort_order" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.sort_order }" @input="validateField('sort_order')">
                <p v-if="formErrors.sort_order" class="form-error">{{ formErrors.sort_order }}</p>
              </div>
              <div class="form-group" style="flex: 1; display: flex; align-items: flex-end;">
                <AdminToggleSwitch
                  id="slider-active"
                  v-model="form.active"
                  layout="inline"
                  label="Slider activo en el carrusel"
                />
              </div>
            </div>
          </div>

          <div>
            <div class="form-group">
              <label>
                Imagen *
                <AdminInfoTooltip text="Imagen principal del slide. Se recomienda formato horizontal amplio (mínimo 1920×600 px). JPG, PNG o WEBP. Máximo 4 MB." />
              </label>
              <div class="admin-upload-box" @click="openImagePicker">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>{{ imagePreviewUrl ? 'Cambiar imagen del slider' : 'Selecciona la imagen principal del slide' }}</p>
                <small>JPG, PNG o WEBP. Máximo 4 MB.</small>
              </div>
              <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
              <p v-if="formErrors.image" class="form-error">{{ formErrors.image }}</p>
              <div v-if="imagePreviewUrl" class="slider-preview-image">
                <img :src="imagePreviewUrl" alt="Vista previa del slider">
                <button class="btn btn-secondary btn-sm" type="button" title="Quitar imagen" @click="clearSelectedImage">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>

          </div>
        </div>

        <div class="slider-preview-card">
          <p class="slider-preview-card__label">
            <i class="fas fa-eye"></i>
            Vista previa del carrusel
          </p>
          <div class="slider-preview-card__surface slider-preview-card__surface--home">
            <HomeHeroSlider :key="sliderPreviewKey" :slides="sliderPreviewSlides" :loading="false" :preview-mode="true" />
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveSlider">
          <i class="fas fa-save"></i>
          {{ editingSliderId ? 'Guardar cambios' : 'Crear slider' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
// =====================================================
// Imports de la vista y utilidades visuales
// =====================================================
import '../views/AdminSlidersPage.css'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import HomeHeroSlider from '../../home/components/HomeHeroSlider.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminSliders } from '../composables/useAdminSliders'

// =====================================================
// Orquestación de la lógica administrativa
// =====================================================
const {
  CUSTOM_STORE_LINK_VALUE,
  clearSelectedImage,
  closeModal,
  confirmDeleteSlider,
  dragState,
  editingSliderId,
  form,
  formErrors,
  imageInputRef,
  imagePreviewUrl,
  linkOptionGroups,
  loading,
  onImageSelected,
  onLinkOptionChange,
  onRowDragEnd,
  onRowDragEnter,
  onRowDragOver,
  onRowDragStart,
  onRowDrop,
  openCreateModal,
  openEditModal,
  openImagePicker,
  saveSlider,
  selectedLinkOption,
  showModal,
  sliderPreviewKey,
  sliderPreviewSlides,
  sliderStats,
  sliders,
  toggleSliderStatus,
  validateField,
} = useAdminSliders()
</script>
