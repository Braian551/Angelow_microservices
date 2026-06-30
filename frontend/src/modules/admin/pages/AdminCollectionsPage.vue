<!--
  Componente: AdminCollectionsPage
  Descripción: Página administrativa para gestionar colecciones de productos (temporadas,
  lanzamientos o agrupaciones temáticas). Permite crear, editar, activar/desactivar y
  eliminar colecciones. Incluye barra de búsqueda con filtros, tabla con paginación,
  y un modal de edición con campos de nombre, slug, fecha de lanzamiento, descripción
  e imagen de portada. La lógica de negocio se encuentra en el composable
  useAdminCollections().
-->
<template>
  <div class="admin-entity-page admin-collections-page">
    <AdminPageHeader
      icon="fas fa-layer-group"
      title="Colecciones"
      subtitle="Organiza temporadas y lanzamientos manteniendo la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Colecciones' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva colección
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y estado"
      placeholder="Nombre, descripción o fecha"
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="collection-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="collection-status" v-model="statusFilter">
              <option value="">Todas</option>
              <option value="active">Activas</option>
              <option value="inactive">Inactivas</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearFilters">
              <i class="fas fa-times-circle"></i>
              Limpiar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} colecciones`" />

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCollections.length === 0"
        icon="fas fa-layer-group"
        title="Sin colecciones visibles"
        description="Crea una nueva colección o ajusta los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Fecha</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="collection in pagination.paginatedItems" :key="collection.id">
              <td>
                <AdminTableImage :src="resolveCollectionImage(collection)" :alt="collection.name" :original-path="collection.image" fallback-type="collection" />
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ collection.name }}</strong>
                  <span>{{ collection.slug || 'Sin slug' }}</span>
                </div>
              </td>
              <td><p class="admin-entity-name__description">{{ excerpt(collection.description, 120) }}</p></td>
              <td>{{ formatDate(collection.launch_date) }}</td>
              <td><span class="admin-entity-filters__pill">{{ collection.product_count }} producto(s)</span></td>
              <td>
                <span class="status-badge" :class="collection.is_active ? 'active' : 'inactive'">
                  {{ collection.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" @click="openModal(collection)"><i class="fas fa-edit"></i></button>
                  <button class="action-btn view" type="button" @click="toggleStatus(collection)"><i class="fas fa-power-off"></i></button>
                  <button v-if="collection.product_count === 0" class="action-btn delete" type="button" title="Eliminar" @click="confirmDelete(collection)"><i class="fas fa-trash"></i></button>
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

    <AdminModal
      :show="showModal"
      :icon="editing ? 'fas fa-edit' : 'fas fa-plus'"
      :title="editing ? 'Editar colección' : 'Nueva colección'"
      :subtitle="editing ? 'Actualiza los datos de esta colección.' : 'Completa los datos para crear una nueva colección.'"
      max-width="860px"
      @close="closeModal"
    >
      <div class="admin-collections-page admin-collections-page--modal">
        <div class="admin-editor-grid">
          <!-- Columna izquierda: datos principales -->
          <div>
            <div class="form-group">
              <label for="collection-name">
                Nombre *
                <AdminInfoTooltip text="Nombre de la colección o temporada. Por ejemplo: «Verano 2025», «Colección Básicos» o «Edición especial»." />
              </label>
              <input
                id="collection-name"
                v-model="form.name"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
                placeholder="Ej. Verano 2025, Línea Básicos"
                @input="onNameInput"
              >
              <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
            </div>

            <div class="form-group">
              <label for="collection-slug">
                Identificador (slug)
                <AdminInfoTooltip text="Clave única que identifica la colección en las URLs. Se genera automáticamente al escribir el nombre. Solo letras minúsculas, números y guiones." />
              </label>
              <input
                id="collection-slug"
                v-model="form.slug"
                class="form-control"
                placeholder="se-genera-automaticamente"
                @input="onSlugInput"
              >
              <p v-if="form.slug" class="admin-field-hint">URL: <code>/tienda/coleccion/{{ form.slug }}</code></p>
            </div>

            <div class="form-group">
              <label for="collection-date">
                Fecha de lanzamiento
                <AdminInfoTooltip text="Fecha en que la colección fue o será presentada. Es informativa y no activa ni desactiva la colección de forma automática." />
              </label>
              <input id="collection-date" v-model="form.launch_date" type="date" class="form-control">
            </div>

            <div class="form-group">
              <label for="collection-description">
                Descripción
                <AdminInfoTooltip text="Descripción interna con el concepto y alcance de la colección. No es visible al cliente en la tienda." />
              </label>
              <textarea
                id="collection-description"
                v-model="form.description"
                class="form-control"
                rows="4"
                placeholder="Describe el concepto, temporada o inspiración de esta colección."
              ></textarea>
            </div>

            <AdminToggleSwitch
              id="collection-active"
              v-model="form.is_active"
              title="Colección activa"
              description="Si está activa, puede asociarse a productos y participa en los flujos promocionales del catálogo."
            />
          </div>

          <!-- Columna derecha: imagen -->
          <div>
            <div class="form-group">
              <label>
                Imagen de portada
                <AdminInfoTooltip text="Imagen principal que representa visualmente la colección en la tienda y el catálogo. Formatos: JPG, PNG o WEBP. Máximo 4 MB." />
              </label>
              <div class="admin-upload-box" @click="openImagePicker">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>{{ imagePreviewUrl ? 'Cambiar imagen' : 'Selecciona la imagen de portada' }}</p>
                <small>JPG, PNG o WEBP · Máximo 4 MB</small>
              </div>
              <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
              <p v-if="errors.image" class="form-error">{{ errors.image }}</p>
              <div v-if="imagePreviewUrl" class="admin-image-preview">
                <img :src="imagePreviewUrl" alt="Vista previa de la imagen de colección">
                <div class="admin-image-preview__actions">
                  <button class="btn btn-secondary btn-sm" type="button" title="Quitar imagen" @click="clearSelectedImage">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveCollection">
          <i :class="editing ? 'fas fa-save' : 'fas fa-plus'"></i>
          {{ editing ? 'Guardar cambios' : 'Crear colección' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
// =====================================================
// Imports de la vista y componentes preservados
// =====================================================
import '../views/AdminCollectionsPage.css'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminTableImage from '../components/AdminTableImage.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminCollections } from '../composables/useAdminCollections'

// =====================================================
// Orquestación de la lógica de colecciones
// =====================================================
const {
  activeFilterCount,   // Conteo de filtros actualmente activos
  clearFilters,        // Función que restablece todos los filtros de búsqueda
  clearSelectedImage,  // Función que elimina la imagen seleccionada del formulario
  closeModal,          // Función que cierra el modal de creación/edición
  confirmDelete,       // Función que muestra el diálogo de confirmación para eliminar
  editing,             // Referencia reactiva que indica si se está editando una colección existente
  errors,              // Objeto reactivo con los errores de validación del formulario
  excerpt,             // Función auxiliar que recorta un texto a cierta cantidad de caracteres
  filteredCollections, // Lista reactiva de colecciones filtradas según búsqueda y estado
  form,                // Objeto reactivo del formulario con los campos de la colección
  formatDate,          // Función que formatea una fecha para mostrarla en la tabla
  imageInputRef,       // Referencia al input de tipo file para la imagen de portada
  imagePreviewUrl,     // URL de la imagen seleccionada para previsualización
  loading,             // Booleano reactivo que indica si se están cargando datos
  onImageSelected,     // Handler que procesa la imagen elegida por el usuario
  onNameInput,         // Handler que ejecuta acciones al escribir en el campo nombre
  onSlugInput,         // Handler que ejecuta acciones al escribir en el campo slug
  openImagePicker,     // Función que abre el selector de archivos de imagen
  openModal,           // Función que abre el modal, recibe una colección opcional para editar
  pagination,          // Objeto con el estado de paginación (página actual, items, etc.)
  resolveCollectionImage, // Función que resuelve la URL de la imagen de una colección
  saveCollection,      // Función que valida y guarda la colección (crea o actualiza)
  search,              // Referencia reactiva con el texto de búsqueda
  showModal,           // Booleano reactivo que controla la visibilidad del modal
  stats,               // Array con las estadísticas mostradas en la cuadrícula superior
  statusFilter,        // Referencia reactiva con el filtro de estado seleccionado
  toggleStatus,        // Función que alterna el estado activo/inactivo de una colección
} = useAdminCollections()
</script>
