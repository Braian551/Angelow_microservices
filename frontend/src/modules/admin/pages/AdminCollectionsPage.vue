<template>
  <div class="admin-entity-page">
    <AdminPageHeader
      icon="fas fa-layer-group"
      title="Colecciones"
      subtitle="Organiza temporadas y lanzamientos manteniendo el comportamiento del panel legacy."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Colecciones' }]"
    >
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()">
          <i class="fas fa-plus"></i> Nueva coleccion
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />

    <div class="filters-bar admin-entity-filters">
      <div class="admin-entity-filters__group admin-entity-filters__search">
        <label for="collection-search">Buscar</label>
        <input id="collection-search" v-model="search" type="text" placeholder="Nombre, descripcion o fecha">
      </div>
      <div class="admin-entity-filters__group">
        <label for="collection-status">Estado</label>
        <select id="collection-status" v-model="statusFilter">
          <option value="">Todas</option>
          <option value="active">Activas</option>
          <option value="inactive">Inactivas</option>
        </select>
      </div>
      <div class="admin-entity-filters__summary">
        <span><i class="fas fa-list"></i> {{ filteredCollections.length }} resultado(s)</span>
      </div>
    </div>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCollections.length === 0"
        icon="fas fa-layer-group"
        title="Sin colecciones visibles"
        description="Crea una nueva coleccion o ajusta los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Descripcion</th>
              <th>Fecha</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="collection in filteredCollections" :key="collection.id">
              <td>
                <img
                  :src="resolveCollectionImage(collection)"
                  :alt="collection.name"
                  class="admin-entity-filters__thumb"
                  @error="onCollectionImageError($event, collection.image)"
                >
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
                  <button class="action-btn delete" type="button" :disabled="collection.product_count > 0" @click="confirmDelete(collection)"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showModal" :title="editing ? 'Editar coleccion' : 'Nueva coleccion'" max-width="760px" @close="closeModal">
      <div class="admin-entity-filters__form">
        <div class="form-group admin-entity-filters__form--full">
          <label for="collection-name">Nombre *</label>
          <input id="collection-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="validateField('name')">
          <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
        </div>

        <div class="form-group">
          <label for="collection-slug">Slug</label>
          <input id="collection-slug" v-model="form.slug" class="form-control">
        </div>

        <div class="form-group">
          <label for="collection-date">Fecha de lanzamiento</label>
          <input id="collection-date" v-model="form.launch_date" type="date" class="form-control">
        </div>

        <div class="form-group admin-entity-filters__form--full">
          <label for="collection-image">Ruta de imagen</label>
          <input id="collection-image" v-model="form.image" class="form-control" placeholder="/uploads/collections/mi-coleccion.jpg">
        </div>

        <div class="form-group admin-entity-filters__form--full">
          <label for="collection-description">Descripcion</label>
          <textarea id="collection-description" v-model="form.description" class="form-control" rows="4"></textarea>
        </div>

        <div class="form-group admin-entity-filters__form--full admin-entity-filters__toggle">
          <div>
            <strong>Coleccion activa</strong>
            <p>Si esta inactiva, queda fuera de los flujos promocionales y de gestion habitual.</p>
          </div>
          <label class="toggle-switch">
            <input v-model="form.is_active" type="checkbox">
            <span class="toggle-slider"></span>
          </label>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveCollection">{{ editing ? 'Actualizar coleccion' : 'Crear coleccion' }}</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const collections = ref([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const showModal = ref(false)
const editing = ref(null)

const form = reactive({
  name: '',
  slug: '',
  description: '',
  image: '',
  launch_date: '',
  is_active: true,
})

const errors = reactive({ name: '' })

const filteredCollections = computed(() => {
  const term = search.value.trim().toLowerCase()
  return collections.value.filter((collection) => {
    const matchesSearch = !term || [collection.name, collection.slug, collection.description, formatDate(collection.launch_date)]
      .some((value) => String(value || '').toLowerCase().includes(term))

    const matchesStatus = !statusFilter.value
      || (statusFilter.value === 'active' && collection.is_active)
      || (statusFilter.value === 'inactive' && !collection.is_active)

    return matchesSearch && matchesStatus
  })
})

const stats = computed(() => {
  const total = collections.value.length
  const active = collections.value.filter((collection) => collection.is_active).length
  const inactive = total - active
  const linkedProducts = collections.value.reduce((sum, collection) => sum + Number(collection.product_count || 0), 0)

  return [
    { key: 'total', label: 'Colecciones totales', value: String(total), icon: 'fas fa-layer-group', color: 'primary' },
    { key: 'active', label: 'Colecciones activas', value: String(active), icon: 'fas fa-check-circle', color: 'success' },
    { key: 'inactive', label: 'Colecciones inactivas', value: String(inactive), icon: 'fas fa-pause-circle', color: 'warning' },
    { key: 'products', label: 'Productos asociados', value: String(linkedProducts), icon: 'fas fa-box-open', color: 'info' },
  ]
})

function normalizeCollection(row) {
  return {
    ...row,
    id: Number(row.id),
    name: row.name || row.nombre || 'Sin nombre',
    slug: row.slug || '',
    description: row.description || row.descripcion || '',
    image: row.image || row.imagen || null,
    launch_date: row.launch_date || '',
    product_count: Number(row.product_count || 0),
    is_active: typeof row.is_active === 'boolean' ? row.is_active : Boolean(Number(row.activo ?? 1)),
  }
}

function resolveCollectionImage(collection) {
  return resolveMediaUrl(collection.image, 'collection')
}

function onCollectionImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'collection')
}

function excerpt(value, max = 100) {
  const text = String(value || '').trim()
  if (!text) return 'Sin descripcion'
  return text.length > max ? `${text.slice(0, max).trim()}...` : text
}

function formatDate(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleDateString('es-CO')
}

function validateField(field) {
  if (field === 'name') {
    errors.name = form.name.trim().length >= 2 ? '' : 'El nombre es obligatorio y debe tener al menos 2 caracteres.'
  }
}

function resetForm() {
  form.name = ''
  form.slug = ''
  form.description = ''
  form.image = ''
  form.launch_date = ''
  form.is_active = true
  errors.name = ''
}

function openModal(collection = null) {
  editing.value = collection
  resetForm()

  if (collection) {
    form.name = collection.name
    form.slug = collection.slug
    form.description = collection.description
    form.image = collection.image || ''
    form.launch_date = collection.launch_date || ''
    form.is_active = collection.is_active
  }

  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editing.value = null
}

async function loadCollections() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/collections')
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    collections.value = rows.map(normalizeCollection)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando colecciones' })
  } finally {
    loading.value = false
  }
}

async function saveCollection() {
  validateField('name')
  if (errors.name) return

  const payload = {
    nombre: form.name.trim(),
    slug: form.slug?.trim() || null,
    descripcion: form.description?.trim() || null,
    imagen: form.image?.trim() || null,
    launch_date: form.launch_date || null,
    activo: form.is_active,
  }

  try {
    if (editing.value?.id) {
      await catalogHttp.put(`/admin/collections/${editing.value.id}`, payload)
      showSnackbar({ type: 'success', message: 'Coleccion actualizada' })
    } else {
      await catalogHttp.post('/admin/collections', payload)
      showSnackbar({ type: 'success', message: 'Coleccion creada' })
    }

    closeModal()
    await loadCollections()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error guardando coleccion' })
  }
}

function confirmDelete(collection) {
  showAlert({
    type: 'warning',
    title: 'Eliminar coleccion',
    message: collection.product_count > 0
      ? `La coleccion ${collection.name} tiene productos asociados y no se puede eliminar.`
      : `¿Deseas eliminar la coleccion ${collection.name}?`,
    actions: collection.product_count > 0
      ? [{ text: 'Entendido', style: 'primary' }]
      : [
          { text: 'Cancelar', style: 'secondary' },
          {
            text: 'Eliminar',
            style: 'danger',
            callback: async () => {
              try {
                await catalogHttp.delete(`/admin/collections/${collection.id}`)
                showSnackbar({ type: 'success', message: 'Coleccion eliminada' })
                await loadCollections()
              } catch (error) {
                showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error eliminando coleccion' })
              }
            },
          },
        ],
  })
}

async function toggleStatus(collection) {
  try {
    await catalogHttp.put(`/admin/collections/${collection.id}`, {
      nombre: collection.name,
      slug: collection.slug || null,
      descripcion: collection.description || null,
      imagen: collection.image || null,
      launch_date: collection.launch_date || null,
      activo: !collection.is_active,
    })
    showSnackbar({ type: 'success', message: !collection.is_active ? 'Coleccion activada' : 'Coleccion desactivada' })
    await loadCollections()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error actualizando estado' })
  }
}

onMounted(loadCollections)
</script>

<style scoped>
/* Estilos específicos de Colecciones — los comunes están en admin.css */

@media (max-width: 768px) {
  .admin-entity-filters__form {
    grid-template-columns: 1fr;
  }
}
</style>
