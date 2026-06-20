<template>
  <div class="admin-entity-page admin-categories-page">
    <AdminPageHeader
      icon="fas fa-folder-open"
      title="Categorías"
      subtitle="Gestiona estructura, estado y contenido descriptivo de las categorías del catálogo."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Categorías' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva categoría
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y estado"
      placeholder="Nombre, slug o descripción"
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="category-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="category-status" v-model="statusFilter">
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} categorías`" />

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCategories.length === 0"
        icon="fas fa-folder-open"
        title="Sin categorías visibles"
        description="Ajusta los filtros o crea una nueva categoría para empezar."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in pagination.paginatedItems" :key="category.id">
              <td>
                <AdminTableImage :src="resolveCategoryImage(category)" :alt="category.name" :original-path="category.image" fallback-type="category" />
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ category.name }}</strong>
                  <span>{{ category.slug || 'Sin slug' }}</span>
                </div>
              </td>
              <td>
                <p class="admin-entity-name__description">{{ excerpt(category.description, 120) }}</p>
              </td>
              <td>
                <span class="admin-entity-filters__pill">{{ category.product_count }} producto(s)</span>
              </td>
              <td>
                <span class="status-badge" :class="category.is_active ? 'active' : 'inactive'">
                  {{ category.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" title="Editar" @click="openModal(category)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn view" type="button" :title="category.is_active ? 'Desactivar' : 'Activar'" @click="toggleStatus(category)">
                    <i class="fas fa-power-off"></i>
                  </button>
                  <button v-if="category.product_count === 0" class="action-btn delete" type="button" title="Eliminar" @click="confirmDelete(category)">
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

    <AdminModal
      :show="showModal"
      :icon="editing ? 'fas fa-edit' : 'fas fa-plus'"
      :title="editing ? 'Editar categoría' : 'Nueva categoría'"
      :subtitle="editing ? 'Actualiza los datos de esta categoría.' : 'Completa los datos para crear una nueva categoría.'"
      max-width="860px"
      @close="closeModal"
    >
      <div class="admin-categories-page admin-categories-page--modal">
        <div class="admin-editor-grid">
          <!-- Columna izquierda: datos principales -->
          <div>
            <div class="form-group">
              <label for="category-name">
                Nombre *
                <AdminInfoTooltip text="Nombre visible de la categoría en la tienda y en el panel de administración. Debe ser claro y reconocible para el equipo." />
              </label>
              <input
                id="category-name"
                v-model="form.name"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
                placeholder="Ej. Vestidos, Pijamas, Ropa Deportiva"
                @input="onNameInput"
              >
              <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
            </div>

            <div class="form-group">
              <label for="category-slug">
                Identificador (slug)
                <AdminInfoTooltip text="Clave única que identifica la categoría en las URLs. Se genera automáticamente al escribir el nombre. Solo letras minúsculas, números y guiones." />
              </label>
              <input
                id="category-slug"
                v-model="form.slug"
                class="form-control"
                placeholder="se-genera-automaticamente"
                @input="onSlugInput"
              >
              <p v-if="form.slug" class="admin-field-hint">URL: <code>/tienda/categoria/{{ form.slug }}</code></p>
            </div>

            <div class="form-group">
              <label for="category-description">
                Descripción
                <AdminInfoTooltip text="Descripción interna del alcance y contenido de la categoría. No es visible al cliente en la tienda." />
              </label>
              <textarea
                id="category-description"
                v-model="form.description"
                class="form-control"
                rows="4"
                placeholder="Describe el tipo de prendas que incluye esta categoría."
              ></textarea>
            </div>

            <AdminToggleSwitch
              id="category-active"
              v-model="form.is_active"
              title="Categoría activa"
              description="Si está activa, puede asociarse a productos y aparece disponible en los filtros del catálogo."
            />
          </div>

          <!-- Columna derecha: imagen -->
          <div>
            <div class="form-group">
              <label>
                Imagen de la categoría
                <AdminInfoTooltip text="Imagen representativa que se muestra en la tienda y el catálogo. Formatos admitidos: JPG, PNG o WEBP. Máximo 4 MB." />
              </label>
              <div class="admin-upload-box" @click="openImagePicker">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>{{ imagePreviewUrl ? 'Cambiar imagen' : 'Selecciona la imagen de la categoría' }}</p>
                <small>JPG, PNG o WEBP · Máximo 4 MB</small>
              </div>
              <input ref="imageInputRef" type="file" accept="image/*" style="display: none;" @change="onImageSelected">
              <p v-if="errors.image" class="form-error">{{ errors.image }}</p>
              <div v-if="imagePreviewUrl" class="admin-image-preview">
                <img :src="imagePreviewUrl" alt="Vista previa de la imagen de categoría">
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
        <button class="btn btn-primary" type="button" @click="saveCategory">
          <i :class="editing ? 'fas fa-save' : 'fas fa-plus'"></i>
          {{ editing ? 'Guardar cambios' : 'Crear categoría' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
// =====================================================
// Imports de la vista y componentes preservados
// =====================================================
import '../views/AdminCategoriesPage.css'
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
import { useAdminCategories } from '../composables/useAdminCategories'

// =====================================================
// Orquestación de la lógica de categorías
// =====================================================
const {
  activeFilterCount,
  clearFilters,
  clearSelectedImage,
  closeModal,
  confirmDelete,
  editing,
  errors,
  excerpt,
  filteredCategories,
  form,
  imageInputRef,
  imagePreviewUrl,
  loading,
  onImageSelected,
  onNameInput,
  onSlugInput,
  openImagePicker,
  openModal,
  pagination,
  resolveCategoryImage,
  saveCategory,
  search,
  showModal,
  stats,
  statusFilter,
  toggleStatus,
} = useAdminCategories()
</script>
