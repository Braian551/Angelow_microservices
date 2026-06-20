<template>
  <div class="admin-entity-page admin-sizes-page">
    <AdminPageHeader
      icon="fas fa-ruler"
      title="Tallas"
      subtitle="Controla las tallas activas del catálogo, su prioridad y uso real en variantes."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Tallas' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva talla
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y estado"
      placeholder="Nombre o descripción"
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="size-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="size-status" v-model="statusFilter">
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

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} tallas`" />

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredSizes.length === 0"
        icon="fas fa-ruler"
        title="Sin tallas visibles"
        description="Crea una talla nueva o ajusta los filtros actuales."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table dashboard-table--sizes">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Orden</th>
              <th>Uso en variantes</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="size in pagination.paginatedItems" :key="size.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ size.name }}</strong>
                  <span>ID #{{ size.id }}</span>
                </div>
              </td>
              <td><p class="admin-entity-name__description">{{ excerpt(size.description, 120) }}</p></td>
              <td>{{ size.sort_order ?? '-' }}</td>
              <td><span class="admin-entity-filters__pill">{{ size.product_count }} variante(s)</span></td>
              <td>
                <span class="status-badge" :class="size.is_active ? 'active' : 'inactive'">
                  {{ size.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" @click="openModal(size)"><i class="fas fa-edit"></i></button>
                  <button class="action-btn view" type="button" @click="toggleStatus(size)"><i class="fas fa-power-off"></i></button>
                  <button v-if="size.product_count === 0" class="action-btn delete" type="button" title="Eliminar" @click="confirmDelete(size)"><i class="fas fa-trash"></i></button>
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

    <AdminModal :show="showModal" :title="editing ? 'Editar talla' : 'Nueva talla'" max-width="720px" @close="closeModal">
      <div class="admin-sizes-page admin-sizes-page--modal">
        <div class="admin-entity-form">
          <div class="form-group">
            <label for="size-name">
              Nombre *
              <AdminInfoTooltip text="Nombre corto de la talla tal como aparece al cliente. Ejemplos: XS, S, M, L, XL, XXL, 3XL." />
            </label>
            <input id="size-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" placeholder="Ej. XS, S, M, L, XL" @input="validateField('name')">
            <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
          </div>

          <div class="form-group">
            <label for="size-order">
              Posición en el listado
              <AdminInfoTooltip text="Número que define en qué orden aparece esta talla en los selectores del formulario. Las tallas con número menor aparecen primero. Ejemplo: XS=1, S=2, M=3, L=4." />
            </label>
            <input id="size-order" v-model="form.sort_order" type="number" min="0" class="form-control" placeholder="Ej. 1" @input="validateField('sort_order')">
            <p v-if="errors.sort_order" class="form-error">{{ errors.sort_order }}</p>
          </div>

          <div class="form-group admin-entity-form__full">
            <label for="size-description">
              Descripción
              <AdminInfoTooltip text="Nota interna sobre esta talla (rango de medidas, equivalencias, etc.). No es visible al cliente en la tienda." />
            </label>
            <textarea id="size-description" v-model="form.description" class="form-control" rows="3" placeholder="Opcional: rango de medidas, equivalencias internacionales, etc."></textarea>
          </div>

          <AdminToggleSwitch
            id="size-active"
            class="admin-entity-form__full"
            v-model="form.is_active"
            title="Talla activa"
            description="Si está activa, estará disponible para asignarla a variantes de productos. Si la desactivas, se conserva para historial."
          />
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveSize">{{ editing ? 'Actualizar talla' : 'Crear talla' }}</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
// =====================================================
// Imports de la vista y componentes compartidos
// =====================================================
import '../views/AdminSizesPage.css'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminSizes } from '../composables/useAdminSizes'

// =====================================================
// Orquestación de la lógica de tallas
// =====================================================
const {
  activeFilterCount,
  clearFilters,
  closeModal,
  confirmDelete,
  editing,
  errors,
  excerpt,
  filteredSizes,
  form,
  loading,
  openModal,
  pagination,
  saveSize,
  search,
  showModal,
  stats,
  statusFilter,
  toggleStatus,
  validateField,
} = useAdminSizes()
</script>
