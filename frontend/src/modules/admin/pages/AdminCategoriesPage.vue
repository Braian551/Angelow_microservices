<template>
  <div>
    <AdminPageHeader
      icon="fas fa-folder-open"
      title="Categorias"
      subtitle="Administra las categorias de productos."
      :breadcrumbs="[{ label: 'Categorias' }]"
    >
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nueva categoria</button>
      </template>
    </AdminPageHeader>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['thumb', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState v-else-if="categories.length === 0" icon="fas fa-folder-open" title="Sin categorias" description="Crea categorias para organizar los productos." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead><tr><th>Imagen</th><th>Nombre</th><th>Slug</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr v-for="c in categories" :key="c.id">
              <td><img :src="catImage(c)" alt="" style="width:40px;height:40px;border-radius:6px;object-fit:cover;"></td>
              <td><strong>{{ c.name }}</strong></td>
              <td>{{ c.slug }}</td>
              <td><span class="status-badge" :class="c.is_active !== false ? 'active' : 'inactive'">{{ c.is_active !== false ? 'Activa' : 'Inactiva' }}</span></td>
              <td>
                <button class="action-btn edit" @click="openModal(c)"><i class="fas fa-edit"></i></button>
                <button class="action-btn delete" @click="confirmDelete(c)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <!-- Modal -->
    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal">
        <div class="admin-modal-header">
          <h3>{{ editing ? 'Editar categoria' : 'Nueva categoria' }}</h3>
          <button class="admin-modal-close" @click="showModal = false">&times;</button>
        </div>
        <div class="admin-modal-body">
          <div class="form-group">
            <label>Nombre *</label>
            <input v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="errors.name = form.name.trim() ? '' : 'Obligatorio'">
            <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
          </div>
          <div class="form-group">
            <label>Slug</label>
            <input v-model="form.slug" class="form-control" placeholder="se-genera-automaticamente">
          </div>
          <div class="form-group">
            <label>Estado</label>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.is_active">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
        <div class="admin-modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveCategory">{{ editing ? 'Actualizar' : 'Crear' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'

const { showSnackbar } = useSnackbarSystem()
const { showAlert } = useAlertSystem()

const categories = ref([])
const loading = ref(true)
const showModal = ref(false)
const editing = ref(null)
const form = reactive({ name: '', slug: '', is_active: true })
const errors = reactive({ name: '' })

function catImage(c) {
  if (c.image) {
    if (c.image.startsWith('http') || c.image.startsWith('/')) return c.image
    return `/uploads/categories/${c.image}`
  }
  return '/assets/foundnotimages/category.png'
}

function openModal(cat = null) {
  editing.value = cat
  form.name = cat?.name || ''
  form.slug = cat?.slug || ''
  form.is_active = cat?.is_active !== false
  errors.name = ''
  showModal.value = true
}

async function loadCategories() {
  loading.value = true
  try {
    const res = await catalogHttp.get('/admin/categories')
    const data = res.data?.data || res.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    categories.value = rows.map((item) => ({
      ...item,
      name: item.name || item.nombre || 'Sin nombre',
      image: item.image || item.imagen || null,
      is_active: typeof item.is_active === 'boolean' ? item.is_active : Boolean(Number(item.activo ?? 1)),
    }))
  } catch { showSnackbar({ type: 'error', message: 'Error cargando categorias' }) }
  finally { loading.value = false }
}

async function saveCategory() {
  if (!form.name.trim()) { errors.name = 'Obligatorio'; return }
  try {
    const payload = {
      nombre: form.name.trim(),
      slug: form.slug?.trim() || null,
      activo: form.is_active,
    }

    if (editing.value?.id) {
      await catalogHttp.put(`/admin/categories/${editing.value.id}`, payload)
    } else {
      await catalogHttp.post('/admin/categories', payload)
    }

    showSnackbar({ type: 'success', message: editing.value ? 'Categoria actualizada' : 'Categoria creada' })
    showModal.value = false
    await loadCategories()
  } catch { showSnackbar({ type: 'error', message: 'Error guardando categoria' }) }
}

function confirmDelete(cat) {
  showAlert({ type: 'warning', title: 'Eliminar', message: `¿Eliminar "${cat.name}"?`, actions: [
    { text: 'Cancelar', style: 'secondary' },
    { text: 'Eliminar', style: 'danger', callback: async () => {
      try {
        await catalogHttp.delete(`/admin/categories/${cat.id}`)
        showSnackbar({ type: 'success', message: 'Categoria eliminada' })
        await loadCategories()
      }
      catch { showSnackbar({ type: 'error', message: 'Error eliminando' }) }
    }},
  ]})
}

onMounted(loadCategories)
</script>
