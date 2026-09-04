<template>
  <div class="admin-settings-page">
    <AdminPageHeader
      icon="fas fa-cog"
      title="General"
      subtitle="Configura el nombre, identidad, contacto y operaciones de la tienda."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Configuración' }, { label: 'General' }]"
    >
      <template #actions>
        <button class="btn btn-secondary btn-lg" type="button" title="Abrir manual completo (Alt+H)" @click="openUserGuideHub">
          <i class="fas fa-circle-question"></i>
          Ayuda
        </button>
        <transition name="settings-fade">
          <span v-if="isDirty && !saving" class="settings-dirty-badge">
            <i class="fas fa-circle"></i>
            Sin guardar
          </span>
        </transition>
        <button class="btn btn-primary btn-lg" type="button" :disabled="loading || saving" @click="saveSettings">
          <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ saving ? 'Guardando…' : 'Guardar cambios' }}
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="settingsStats" />

    <!-- Esqueleto de carga -->
    <div v-if="loading" class="settings-skeleton">
      <div class="settings-skeleton__tabs">
        <AdminShimmer v-for="n in 4" :key="n" type="pill" width="11rem" height="4rem" />
      </div>
      <div class="settings-brand-grid">
        <div class="admin-card" style="grid-column:1/3;">
          <div class="admin-card__body" style="display:grid;gap:1.4rem;">
            <div v-for="f in 5" :key="f" style="display:grid;gap:0.5rem;">
              <AdminShimmer type="line" width="8rem" height="1.2rem" />
              <AdminShimmer type="rect" height="3.8rem" />
            </div>
          </div>
        </div>
        <div class="admin-card">
          <div class="admin-card__body" style="display:grid;gap:1.2rem;">
            <AdminShimmer v-for="n in 3" :key="n" type="rect" height="11rem" />
          </div>
        </div>
      </div>
    </div>

    <AdminEmptyState
      v-else-if="categorySections.length === 0"
      icon="fas fa-cog"
      title="Sin configuración disponible"
      description="No se encontraron definiciones para construir el formulario general."
    />

    <div v-else class="settings-layout">
      <!-- Navegación por pestañas -->
      <div class="settings-tabs" role="tablist">
        <button
          v-for="section in categorySections"
          :key="section.key"
          role="tab"
          type="button"
          class="settings-tab"
          :class="{ 'is-active': activeSection === section.key, 'has-errors': sectionErrorCount(section) > 0 }"
          :aria-selected="activeSection === section.key"
          @click="activeSection = section.key"
        >
          <i :class="section.icon"></i>
          <span>{{ section.title }}</span>
          <span v-if="sectionErrorCount(section) > 0" class="settings-tab__errorcount">{{ sectionErrorCount(section) }}</span>
        </button>
      </div>

      <!-- Contenido animado por sección -->
      <transition name="settings-section" mode="out-in">
        <div :key="activeSection" class="settings-section-body" role="tabpanel">

          <!-- ══════ MARCA E IDENTIDAD ══════ -->
          <template v-if="activeSection === 'brand'">
            <div class="settings-brand-grid">
              <!-- Columna izquierda: campos de texto y colores -->
              <AdminCard title="Datos de la tienda" icon="fas fa-store" class="settings-brand-text-card">
                <div class="settings-fields-grid">
                  <div v-for="field in brandTextFields" :key="field.key" class="form-group">
                    <label :for="`setting-${field.key}`" class="settings-field-label">{{ field.label }}</label>
                    <template v-if="field.type === 'color'">
                      <div class="color-input-group">
                        <button
                          type="button"
                          class="settings-color-swatch"
                          :style="{ background: settings[field.key] || field.default || '#0077b6' }"
                          :title="`Color: ${settings[field.key] || field.default}`"
                          @click="() => colorPickerRefs[field.key]?.click()"
                        ></button>
                        <input :ref="(el) => (colorPickerRefs[field.key] = el)" :id="`setting-${field.key}`" v-model="settings[field.key]" type="color" class="settings-color-picker-hidden" @input="validateField(field.key)">
                        <input v-model.trim="settings[field.key]" type="text" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.default || '#000000'" @input="validateField(field.key)">
                      </div>
                    </template>
                    <template v-else>
                      <input
                        :id="`setting-${field.key}`"
                        v-model="settings[field.key]"
                        :type="inputType(field)"
                        class="form-control"
                        :class="{ 'is-invalid': errors[field.key] }"
                        :placeholder="field.hint || ''"
                        :maxlength="field.max_length ? Number(field.max_length) : undefined"
                        @input="validateField(field.key)"
                      >
                      <div v-if="field.max_length" class="settings-char-counter" :class="{ 'is-warn': charPercent(field.key, field) >= 80 }">
                        {{ String(settings[field.key] || '').length }} / {{ field.max_length }}
                      </div>
                    </template>
                    <small v-if="field.hint" class="form-hint">{{ field.hint }}</small>
                    <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                  </div>
                </div>
              </AdminCard>

              <!-- Columna derecha: galería de imágenes (logo, logo alterno, favicon) -->
              <AdminCard title="Identidad visual" icon="fas fa-images" class="settings-brand-images-card">
                <div class="settings-image-gallery">
                  <div v-for="field in brandImageFields" :key="field.key" class="settings-image-gallery__item">
                    <p class="settings-image-gallery__label">
                      <i class="fas fa-image"></i>
                      {{ field.label }}
                    </p>
                    <div v-if="!imagePreviews[field.key]" class="settings-image-upload settings-image-upload--compact" @click="openImagePicker(field.key)">
                      <i class="fas fa-cloud-upload-alt"></i>
                      <small>{{ field.hint || 'Subir imagen' }}</small>
                    </div>
                    <div v-else class="settings-image-card settings-image-card--compact">
                      <div class="settings-image-card__visual">
                        <img :src="imagePreviews[field.key]" :alt="field.label" @error="(event) => onImagePreviewError(field.key, event)">
                        <div class="settings-image-card__overlay">
                          <button type="button" class="settings-image-action settings-image-action--edit" title="Cambiar" @click="openImagePicker(field.key)">
                            <i class="fas fa-pencil-alt"></i>
                          </button>
                          <button type="button" class="settings-image-action settings-image-action--remove" title="Quitar" @click="clearImage(field.key)">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </div>
                      </div>
                      <transition name="settings-fade">
                        <div v-if="imageFiles[field.key]" class="settings-image-file-pill">
                          <i class="fas fa-check-circle"></i>
                          {{ imageFiles[field.key].name }}
                        </div>
                      </transition>
                    </div>
                    <input :ref="(element) => setImageInputRef(field.key, element)" type="file" accept="image/*" style="display:none;" @change="(event) => onImageSelected(field.key, event)">
                  </div>
                </div>
              </AdminCard>
            </div>
          </template>

          <!-- ══════ REDES SOCIALES ══════ -->
          <template v-else-if="activeSection === 'social'">
            <AdminCard title="Redes sociales" subtitle="Vincula los perfiles de la tienda para el footer y la tienda pública." icon="fas fa-share-alt">
              <div class="settings-social-list">
                <div v-for="field in socialFields" :key="field.key" class="settings-social-row">
                  <div class="settings-social-icon" :class="`settings-social-icon--${socialNetworkKey(field.key)}`">
                    <i :class="socialNetworkIcon(field.key)"></i>
                  </div>
                  <div class="settings-social-input">
                    <label :for="`setting-${field.key}`">{{ field.label }}</label>
                    <input
                      :id="`setting-${field.key}`"
                      v-model="settings[field.key]"
                      type="url"
                      class="form-control"
                      :class="{ 'is-invalid': errors[field.key] }"
                      placeholder="https://"
                      @input="validateField(field.key)"
                    >
                    <small class="form-hint">{{ field.hint }}</small>
                    <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                  </div>
                </div>
              </div>
            </AdminCard>
          </template>

          <!-- ══════ OPERACIONES ══════ -->
          <template v-else-if="activeSection === 'operations'">
            <div class="settings-operations-grid">
              <AdminCard v-for="field in operationsFields" :key="field.key" class="settings-op-card">
                <div class="settings-op-row">
                  <div class="settings-op-icon">
                    <i :class="'fas ' + (field.icon || 'fa-cog')"></i>
                  </div>
                  <div class="settings-op-content">
                    <label :for="field.type !== 'bool' ? `setting-${field.key}` : undefined" class="settings-op-label">{{ field.label }}</label>
                    <p class="settings-op-hint">{{ field.hint }}</p>
                    <template v-if="field.type === 'bool'">
                      <div class="settings-toggle-row">
                        <label class="toggle-switch">
                          <input :id="`setting-${field.key}`" v-model="settings[field.key]" type="checkbox" @change="validateField(field.key)">
                          <span class="toggle-slider"></span>
                        </label>
                        <span class="settings-toggle-text" :class="{ 'is-on': settings[field.key] }">
                          {{ settings[field.key] ? 'Activado' : 'Desactivado' }}
                        </span>
                      </div>
                    </template>
                    <template v-else>
                      <input
                        :id="`setting-${field.key}`"
                        v-model="settings[field.key]"
                        :type="inputType(field)"
                        class="form-control settings-op-input"
                        :class="{ 'is-invalid': errors[field.key] }"
                        :min="field.min"
                        :max="field.max"
                        @input="validateField(field.key)"
                      >
                    </template>
                    <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                  </div>
                </div>
              </AdminCard>
            </div>
          </template>

          <!-- ══════ SOPORTE, SISTEMA Y OTRAS ══════ -->
          <template v-else>
            <AdminCard :title="currentSectionData?.title" :icon="currentSectionData?.icon">
              <div class="settings-generic-2col">
                <div v-for="field in (currentSectionData?.fields || [])" :key="field.key" class="form-group">
                  <label :for="field.type !== 'bool' ? `setting-${field.key}` : undefined" class="settings-field-label">{{ field.label }}</label>
                  <template v-if="field.type === 'textarea'">
                    <textarea :id="`setting-${field.key}`" v-model="settings[field.key]" rows="2" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.hint || ''" :maxlength="field.max_length ? Number(field.max_length) : undefined" @input="validateField(field.key)"></textarea>
                    <div v-if="field.max_length" class="settings-char-counter" :class="{ 'is-warn': charPercent(field.key, field) >= 80 }">{{ String(settings[field.key] || '').length }} / {{ field.max_length }}</div>
                  </template>
                  <template v-else-if="field.type === 'bool'">
                    <div class="settings-toggle-row">
                      <label class="toggle-switch">
                        <input :id="`setting-${field.key}`" v-model="settings[field.key]" type="checkbox" @change="validateField(field.key)">
                        <span class="toggle-slider"></span>
                      </label>
                      <span class="settings-toggle-text" :class="{ 'is-on': settings[field.key] }">{{ settings[field.key] ? 'Activado' : 'Desactivado' }}</span>
                    </div>
                  </template>
                  <template v-else-if="field.type === 'color'">
                    <div class="color-input-group">
                      <button type="button" class="settings-color-swatch" :style="{ background: settings[field.key] || field.default || '#0077b6' }" @click="() => colorPickerRefs[field.key]?.click()"></button>
                      <input :ref="(el) => (colorPickerRefs[field.key] = el)" :id="`setting-${field.key}`" v-model="settings[field.key]" type="color" class="settings-color-picker-hidden" @input="validateField(field.key)">
                      <input v-model.trim="settings[field.key]" type="text" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.default || '#000000'" @input="validateField(field.key)">
                    </div>
                  </template>
                  <template v-else>
                    <input :id="`setting-${field.key}`" v-model="settings[field.key]" :type="inputType(field)" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.hint || ''" :maxlength="field.max_length ? Number(field.max_length) : undefined" @input="validateField(field.key)">
                    <div v-if="field.max_length" class="settings-char-counter" :class="{ 'is-warn': charPercent(field.key, field) >= 80 }">{{ String(settings[field.key] || '').length }} / {{ field.max_length }}</div>
                  </template>
                  <small v-if="field.hint && field.type !== 'bool'" class="form-hint">{{ field.hint }}</small>
                  <p v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</p>
                </div>
              </div>
            </AdminCard>
          </template>

        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
// =====================================================
// Imports de la vista y componentes preservados
// =====================================================
import '../views/AdminSettingsPage.css'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminShimmer from '../components/AdminShimmer.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import { openUserGuideHub } from '../../../features/user-guide/userGuide'
import { useAdminSettings } from '../composables/useAdminSettings'

// =====================================================
// Orquestación de la configuración administrativa
// =====================================================
const {
  activeSection,
  brandImageFields,
  brandTextFields,
  categorySections,
  charPercent,
  clearImage,
  colorPickerRefs,
  currentSectionData,
  errors,
  imageFiles,
  imagePreviews,
  inputType,
  isDirty,
  loading,
  onImagePreviewError,
  onImageSelected,
  openImagePicker,
  operationsFields,
  saveSettings,
  sectionErrorCount,
  setImageInputRef,
  settings,
  settingsStats,
  saving,
  socialFields,
  socialNetworkIcon,
  socialNetworkKey,
  validateField,
} = useAdminSettings()
</script>
