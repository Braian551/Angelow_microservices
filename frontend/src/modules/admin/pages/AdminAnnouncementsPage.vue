<template>
  <div class="admin-announcements-page">
    <!-- Encabezado administrativo con exportación y creación de anuncios. -->
    <AdminPageHeader
      icon="fas fa-bullhorn"
      title="Anuncios"
      subtitle="Gestiona anuncios con búsqueda, revisión, vista previa y control del estado de publicación."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Anuncios' }]"
    >
      <template #actions>
        <AdminExportActions
          tone="header"
          :disabled="filteredAnnouncements.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportAnnouncements('excel')"
          @pdf="exportAnnouncements('pdf')"
        />
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo anuncio
        </button>
      </template>
    </AdminPageHeader>

    <!-- Métricas rápidas del módulo: totales, estados y capacidad restante. -->
    <AdminStatsGrid :loading="loading" :count="4" :stats="announcementStats" />

    <!-- Filtros de búsqueda, estado y tipo que alimentan la lista paginada. -->
    <AdminFilterCard
      icon="fas fa-filter"
      title="Búsqueda y estado"
      placeholder="Buscar por título, mensaje o botón..."
      :model-value="filters.search"
      @update:model-value="filters.search = $event"
      @search="filters.search = $event"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--3">
          <div class="admin-filters__group">
            <label for="announcement-state"><i class="fas fa-signal"></i> Estado</label>
            <select id="announcement-state" v-model="filters.state">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="scheduled">Programados</option>
              <option value="expired">Vencidos</option>
              <option value="inactive">Inactivos</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="announcement-type"><i class="fas fa-shapes"></i> Tipo</label>
            <select id="announcement-type" v-model="filters.type">
              <option value="all">Todos</option>
              <option value="top_bar">Barra superior</option>
              <option value="promo_banner">Banner promocional</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <span class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            {{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}
          </span>
          <button type="button" class="admin-filters__clear" @click="clearFilters">
            <i class="fas fa-times-circle"></i>
            Limpiar filtros
          </button>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Resumen de resultados y aviso del límite operativo de anuncios. -->
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} anuncios`">
      <template #actions>
        <span class="results-note" :class="{ 'results-note--warning': !canCreateAnnouncement }">
          {{ canCreateAnnouncement ? 'Aún puedes crear anuncios adicionales.' : 'Límite alcanzado: 2 anuncios.' }}
        </span>
      </template>
    </AdminResultsBar>

    <!-- Bandeja principal con shimmer, estado vacío y tabla de anuncios. -->
    <AdminCard title="Bandeja de anuncios" icon="fas fa-bullhorn" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="4" :columns="['thumb', 'line', 'line', 'line', 'pill', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredAnnouncements.length === 0"
        icon="fas fa-bullhorn"
        title="Sin anuncios"
        description="No hay anuncios que coincidan con los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table announcements-table">
          <thead>
            <tr>
              <th>Vista</th>
              <th>Detalles</th>
              <th>Tipo</th>
              <th>Prioridad</th>
              <th>Fechas</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- Cada fila resume vista previa, metadatos, estado y acciones del anuncio. -->
            <tr v-for="announcement in pagination.paginatedItems" :key="announcement.id">
              <td>
                <button type="button" class="announcement-thumb" @click="openDetailModal(announcement)">
                  <img
                    v-if="announcement.image"
                    :src="resolveMediaUrl(announcement.image, 'banner')"
                    :alt="announcement.title"
                    @error="handleMediaError($event, announcement.image, 'banner')"
                  >
                  <i v-else :class="['fas', announcement.icon || 'fa-bullhorn']"></i>
                </button>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ announcement.title }}</strong>
                  <span>{{ truncateText(announcement.message || announcement.content, 78) }}</span>
                </div>
              </td>
              <td>
                <span class="status-badge info">{{ typeLabel(announcement.type) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="priorityClass(announcement.priority)">
                  {{ priorityLabel(announcement.priority) }}
                </span>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ announcement.start_date ? formatDateTime(announcement.start_date) : 'Inmediato' }}</strong>
                  <span>{{ announcement.end_date ? `Finaliza ${formatDateTime(announcement.end_date)}` : 'Sin fecha de cierre' }}</span>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="announcementStatusClass(announcement)">
                  {{ announcementStatusLabel(announcement) }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(announcement)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Editar anuncio" @click="openEditModal(announcement)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar anuncio" @click="confirmDeleteAnnouncement(announcement)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

    <!-- Modal de detalle con vista previa realista y configuración resumida. -->
    <AdminModal :show="showDetailModal" :title="selectedAnnouncement ? selectedAnnouncement.title : 'Detalle del anuncio'" max-width="1080px" @close="closeDetailModal">
      <template v-if="selectedAnnouncement">
        <div class="admin-announcements-page admin-announcements-page--modal">
          <div class="announcement-detail-grid admin-detail-grid admin-detail-grid--featured">
            <!-- Vista previa del anuncio seleccionado según su tipo y recursos visuales. -->
            <div class="announcement-preview-card">
              <div class="announcement-preview-card__visual" :style="previewCardStyle(selectedAnnouncement)">
                <img
                  v-if="selectedAnnouncement.type === 'promo_banner' && selectedAnnouncement.image"
                  :src="resolveMediaUrl(selectedAnnouncement.image, 'banner')"
                  :alt="selectedAnnouncement.title"
                  @error="handleMediaError($event, selectedAnnouncement.image, 'banner')"
                >
                <div class="announcement-preview-card__content">
                  <span class="announcement-preview-card__type">
                    <i :class="['fas', selectedAnnouncement.icon || 'fa-bullhorn']"></i>
                    {{ typeLabel(selectedAnnouncement.type) }}
                  </span>
                  <h3>{{ selectedAnnouncement.title }}</h3>
                  <p>{{ selectedAnnouncement.message || 'Sin mensaje configurado.' }}</p>
                  <p v-if="selectedAnnouncement.type === 'promo_banner' && selectedAnnouncement.subtitle" class="announcement-preview-card__subtitle">
                    {{ selectedAnnouncement.subtitle }}
                  </p>
                  <a v-if="selectedAnnouncement.type === 'promo_banner' && selectedAnnouncement.button_text" class="announcement-preview-card__button" href="#" @click.prevent>
                    {{ selectedAnnouncement.button_text }}
                  </a>
                </div>
              </div>
            </div>

            <div>
              <!-- Datos operativos del anuncio: estado, prioridad y vigencia. -->
              <AdminCard title="Configuración" icon="fas fa-cogs">
                <div class="admin-detail-summary">
                  <div class="admin-detail-summary__row"><span>Estado</span><strong>{{ announcementStatusLabel(selectedAnnouncement) }}</strong></div>
                  <div class="admin-detail-summary__row"><span>Prioridad</span><strong>{{ priorityLabel(selectedAnnouncement.priority) }}</strong></div>
                  <div class="admin-detail-summary__row"><span>Inicio</span><strong>{{ selectedAnnouncement.start_date ? formatDateTime(selectedAnnouncement.start_date) : 'Inmediato' }}</strong></div>
                  <div class="admin-detail-summary__row"><span>Fin</span><strong>{{ selectedAnnouncement.end_date ? formatDateTime(selectedAnnouncement.end_date) : 'Sin cierre' }}</strong></div>
                  <div v-if="selectedAnnouncement.type === 'promo_banner'" class="admin-detail-summary__row"><span>Botón</span><strong>{{ selectedAnnouncement.button_text || 'No configurado' }}</strong></div>
                  <div v-if="selectedAnnouncement.type === 'promo_banner'" class="admin-detail-summary__row admin-detail-summary__row--stack"><span>Enlace</span><strong>{{ selectedAnnouncement.button_link || 'Sin enlace' }}</strong></div>
                </div>
              </AdminCard>

              <!-- Copia completa del anuncio para revisión antes de editar. -->
              <AdminCard title="Mensaje completo" icon="fas fa-align-left" style="margin-top: 1.2rem;">
                <div class="detail-copy-block">
                  <p>{{ selectedAnnouncement.message || 'Sin mensaje.' }}</p>
                  <p v-if="selectedAnnouncement.type === 'promo_banner' && selectedAnnouncement.subtitle"><strong>Subtítulo:</strong> {{ selectedAnnouncement.subtitle }}</p>
                </div>
              </AdminCard>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedAnnouncement" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar anuncio
        </button>
      </template>
    </AdminModal>

    <!-- Modal de creación/edición; reutiliza el mismo formulario y validaciones del composable. -->
    <AdminModal :show="showEditorModal" :title="editingAnnouncementId ? 'Editar anuncio' : 'Nuevo anuncio'" max-width="920px" @close="closeEditorModal">
      <div class="admin-announcements-page admin-announcements-page--modal">
        <div class="editor-grid admin-editor-grid">
          <div>
            <!-- Tipo del anuncio: define campos visibles y componente de vista previa. -->
            <div class="form-group">
              <label for="announcement-type-editor">
                Tipo *
                <AdminInfoTooltip text="«Barra superior» aparece en la franja de la parte alta de la tienda. «Banner promocional» se muestra como bloque destacado." />
              </label>
              <select id="announcement-type-editor" v-model="form.type" class="form-control" @change="handleAnnouncementTypeChange()">
                <option value="top_bar">Barra superior</option>
                <option value="promo_banner">Banner promocional</option>
              </select>
              <p v-if="formErrors.type" class="form-error">{{ formErrors.type }}</p>
            </div>

            <!-- Título del anuncio: primer texto visible en la tienda. -->
            <div class="form-group">
              <label for="announcement-title">
                Título *
                <AdminInfoTooltip text="Título breve del anuncio. Visible en la barra o banner según el tipo seleccionado." />
              </label>
              <input id="announcement-title" v-model.trim="form.title" type="text" class="form-control" :class="{ 'is-invalid': formErrors.title }" @input="validateField('title')">
              <p v-if="formErrors.title" class="form-error">{{ formErrors.title }}</p>
            </div>

            <!-- Mensaje principal que acompaña el título del anuncio. -->
            <div class="form-group">
              <label for="announcement-message">
                Mensaje *
                <AdminInfoTooltip text="Texto principal del anuncio visible al usuario. Sé claro y directo." />
              </label>
              <textarea id="announcement-message" v-model.trim="form.message" rows="4" class="form-control" :class="{ 'is-invalid': formErrors.message }" @input="validateField('message')"></textarea>
              <p v-if="formErrors.message" class="form-error">{{ formErrors.message }}</p>
            </div>

            <!-- Subtítulo opcional exclusivo de banners promocionales. -->
            <div v-if="isPromoBannerType" class="form-group">
              <label for="announcement-subtitle">
                Subtítulo
                <AdminInfoTooltip text="Texto secundario opcional que complementa el mensaje principal." />
              </label>
              <input id="announcement-subtitle" v-model.trim="form.subtitle" type="text" class="form-control" @input="validateField('subtitle')">
            </div>

            <!-- Botón y destino del banner, visibles solo cuando el anuncio actúa como CTA. -->
            <div v-if="isPromoBannerType" class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="announcement-button-text">
                  Texto del botón
                  <AdminInfoTooltip text="Etiqueta del botón de acción del anuncio. Ejemplo: «Ver oferta», «Ir a la tienda»." />
                </label>
                <input id="announcement-button-text" v-model.trim="form.button_text" type="text" class="form-control" @input="validateField('button_text')">
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="announcement-link">
                  Destino del botón
                  <AdminInfoTooltip text="Página de la tienda a la que lleva el botón al hacer clic. Elige «Otro enlace personalizado» si necesitas una URL específica." />
                </label>
                <select id="announcement-link" v-model="selectedLinkOption" class="form-control" @change="onLinkOptionChange(selectedLinkOption)">
                  <optgroup v-for="group in linkOptionGroups" :key="group.label" :label="group.label">
                    <option v-for="page in group.options" :key="page.value" :value="page.value">{{ page.label }}</option>
                  </optgroup>
                </select>
                <small class="announcement-link-help">Elige un destino frecuente, una colección puntual o define un enlace manual para una campaña específica.</small>
                <input
                  v-if="selectedLinkOption === CUSTOM_STORE_LINK_VALUE"
                  v-model.trim="form.button_link"
                  type="text"
                  class="form-control mt-1"
                  :class="{ 'is-invalid': formErrors.button_link }"
                  placeholder="Ej. /producto/vestido-verano, /tienda?collection=12 o https://..."
                  @input="validateField('button_link')"
                >
                <small v-if="selectedLinkOption === CUSTOM_STORE_LINK_VALUE" class="announcement-link-help announcement-link-help--muted">También puedes apuntar a un producto concreto por slug o a una página externa.</small>
                <p v-if="formErrors.button_link" class="form-error">{{ formErrors.button_link }}</p>
              </div>
            </div>

            <p v-else class="announcement-form-tip">
              La barra superior solo usa título, mensaje, icono, colores, prioridad y fechas. Los campos de banner se ocultan automáticamente para evitar ruido en el formulario.
            </p>
          </div>

          <div>
            <!-- Configuración visual, prioridad y ventana de publicación. -->
            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="announcement-icon">
                  Icono *
                  <AdminInfoTooltip text="Ícono visual que acompaña el anuncio. Elige el que mejor represente el tipo de comunicación." />
                </label>
                <select id="announcement-icon" v-model="form.icon" class="form-control" :class="{ 'is-invalid': formErrors.icon }" @change="validateField('icon')">
                  <option value="fa-bullhorn">Megáfono</option>
                  <option value="fa-tags">Ofertas</option>
                  <option value="fa-percent">Descuento</option>
                  <option value="fa-truck">Envío</option>
                  <option value="fa-shipping-fast">Envío rápido</option>
                  <option value="fa-star">Destacado</option>
                  <option value="fa-clock">Urgencia</option>
                </select>
                <p v-if="formErrors.icon" class="form-error">{{ formErrors.icon }}</p>
              </div>

              <div class="form-group" style="flex: 1;">
                <label for="announcement-priority">
                  Prioridad *
                  <AdminInfoTooltip text="Número de orden para mostrar los anuncios cuando hay varios activos. Mayor número = mayor prioridad." />
                </label>
                <input id="announcement-priority" v-model.number="form.priority" type="number" min="0" max="100" class="form-control" :class="{ 'is-invalid': formErrors.priority }" @input="validateField('priority')">
                <p v-if="formErrors.priority" class="form-error">{{ formErrors.priority }}</p>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="announcement-start">
                  Inicio
                  <AdminInfoTooltip text="Fecha y hora desde cuando el anuncio es visible en la tienda. Dejar vacío para que sea inmediato." />
                </label>
                <input id="announcement-start" v-model="form.start_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.start_date }" @change="validateField('start_date')">
                <p v-if="formErrors.start_date" class="form-error">{{ formErrors.start_date }}</p>
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="announcement-end">
                  Fin
                  <AdminInfoTooltip text="Fecha y hora en que el anuncio deja de mostrarse. Dejar vacío para que no expire." />
                </label>
                <input id="announcement-end" v-model="form.end_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.end_date }" @change="validateField('end_date')">
                <p v-if="formErrors.end_date" class="form-error">{{ formErrors.end_date }}</p>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="announcement-bg">
                  Color de fondo
                  <AdminInfoTooltip text="Elige el color principal del anuncio. Los colores disponibles están registrados en el catálogo de la tienda." />
                </label>
                <div class="color-select-wrapper">
                  <span
                    class="color-select-swatch"
                    :style="{ background: form.background_color || '#0f7abf' }"
                    :title="selectedColorInfo ? selectedColorInfo.name : form.background_color"
                  ></span>
                  <select id="announcement-bg" v-model="form.background_color" class="form-control">
                    <option v-if="availableColors.length === 0" value="#0f7abf">Cargando colores...</option>
                    <option
                      v-for="color in availableColors.filter((c) => c.hex_code)"
                      :key="color.id"
                      :value="color.hex_code"
                    >{{ color.name }} — {{ color.hex_code }}</option>
                  </select>
                </div>
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="announcement-text-color">
                  Color del texto
                  <AdminInfoTooltip text="Color de las letras sobre el fondo del anuncio. Elige un color con buen contraste." />
                </label>
                <div class="color-select-wrapper">
                  <span
                    class="color-select-swatch"
                    :style="{ background: form.text_color || '#ffffff', border: form.text_color === '#ffffff' ? '1px solid #ccc' : undefined }"
                  ></span>
                  <select id="announcement-text-color" v-model="form.text_color" class="form-control">
                    <option v-for="tc in TEXT_COLOR_OPTIONS" :key="tc.value" :value="tc.value">{{ tc.label }}</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Carga opcional de imagen solo para banners promocionales. -->
            <div v-if="isPromoBannerType" class="form-group">
              <label>
                Imagen
                <AdminInfoTooltip text="Imagen opcional para el anuncio tipo banner. JPG, PNG o WEBP. Máximo 3 MB." />
              </label>
              <div class="admin-upload-box" @click="openImagePicker">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>{{ imagePreviewUrl ? 'Cambiar imagen del anuncio' : 'Selecciona una imagen opcional para el anuncio' }}</p>
                <small>JPG, PNG o WEBP. Máximo 3 MB.</small>
              </div>
              <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
              <div v-if="imagePreviewUrl" class="editor-preview-image">
                <img :src="imagePreviewUrl" alt="Vista previa del anuncio">
                <button type="button" class="btn btn-secondary btn-sm" title="Quitar imagen" @click="clearSelectedImage">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>

            <!-- Control de publicación inmediata del anuncio. -->
            <div class="form-group">
              <AdminToggleSwitch
                id="announcement-active"
                v-model="form.is_active"
                layout="inline"
                label="Publicar anuncio inmediatamente"
              />
            </div>
          </div>
        </div>

        <!-- Vista previa en vivo usando los mismos componentes que verá la tienda. -->
        <div class="editor-live-preview">
          <p class="editor-live-preview__label">
            <i class="fas fa-eye"></i>
            Vista previa — tal como se verá en la tienda
          </p>
          <div class="editor-live-preview__frame">
            <template v-if="form.type === 'top_bar'">
              <div class="preview-site-context preview-site-context--topbar">
                <TopAnnouncementBar :announcement="{ ...formPreview, background_color: form.background_color, text_color: form.text_color }" />
              </div>
            </template>

            <template v-else-if="form.type === 'promo_banner'">
              <div class="preview-site-context preview-site-context--banner">
                <PromoBanner :banner="formPreview" />
              </div>
            </template>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveAnnouncement">
          <i class="fas fa-save"></i>
          {{ editingAnnouncementId ? 'Guardar cambios' : 'Crear anuncio' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import '../views/AdminAnnouncementsPage.css'
import TopAnnouncementBar from '../../../components/home/TopAnnouncementBar.vue'
import PromoBanner from '../../home/components/PromoBanner.vue'
import { handleMediaError } from '../../../utils/media'
import { useAdminAnnouncements } from '../composables/useAdminAnnouncements'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

// La vista actúa como orquestador visual; la lógica de datos, validación y exportación vive en useAdminAnnouncements.
const {
  CUSTOM_STORE_LINK_VALUE,
  TEXT_COLOR_OPTIONS,
  activeFilterCount,
  announcementStats,
  availableColors,
  canCreateAnnouncement,
  clearFilters,
  clearSelectedImage,
  closeDetailModal,
  closeEditorModal,
  confirmDeleteAnnouncement,
  editingAnnouncementId,
  exportAnnouncements,
  exportingFormat,
  filteredAnnouncements,
  filters,
  form,
  formErrors,
  formPreview,
  formatDateTime,
  handleAnnouncementTypeChange,
  imageInputRef,
  imagePreviewUrl,
  isPromoBannerType,
  linkOptionGroups,
  loading,
  onImageSelected,
  onLinkOptionChange,
  openCreateModal,
  openDetailModal,
  openEditFromDetail,
  openEditModal,
  openImagePicker,
  pagination,
  previewCardStyle,
  priorityClass,
  priorityLabel,
  resolveMediaUrl,
  saveAnnouncement,
  selectedAnnouncement,
  selectedColorInfo,
  selectedLinkOption,
  showDetailModal,
  showEditorModal,
  truncateText,
  typeLabel,
  validateField,
  announcementStatusClass,
  announcementStatusLabel,
} = useAdminAnnouncements()
</script>
