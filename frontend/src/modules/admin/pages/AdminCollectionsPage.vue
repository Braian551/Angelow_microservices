<template>
  <div>
    <AdminPageHeader
      icon="fas fa-layer-group"
      title="Colecciones"
      subtitle="Administra las colecciones de productos."
      :breadcrumbs="[{ label: 'Colecciones' }]"
    >
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nueva coleccion</button>
      </template>
    </AdminPageHeader>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState v-else-if="items.length === 0" icon="fas fa-layer-group" title="Sin colecciones" description="Crea colecciones para agrupar productos." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead><tr><th>Imagen</th><th>Nombre</th><th>Slug</th><th>Fecha lanzamiento</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr v-for="c in items" :key="c.id">
              <td><img :src="imgUrl(c)" alt="" style="width:40px;height:40px;border-radius:6px;object-fit:cover;"></td>
              <td><strong>{{ c.name }}</strong></td>
              <td>{{ c.slug }}</td>
              <td>{{ c.launch_date ? new Date(c.launch_date).toLocaleDateString('es-CO') : '-' }}</td>
              <td><span class="status-badge" :class="c.is_active !== false ? 'active' : 'inactive'">{{ c.is_active !== false ? 'Activa' : 'Inactiva' }}</span></td>
              <td>
                <button class="action-btn edit" @click="openModal(c)"><i class="fas fa-edit"></i></button>
                <button class="action-btn delete" @click="remove(c)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal">
        <div class="admin-modal-header"><h3>{{ editing ? 'Editar' : 'Nueva' }} coleccion</h3><button class="admin-modal-close" @click="showModal = false">&times;</button></div>
        <div class="admin-modal-body">
          <div class="form-group"><label>Nombre *</label><input v-model="form.name" class="form-control"></div>
          <div class="form-group"><label>Slug</label><input v-model="form.slug" class="form-control"></div>
          <div class="form-group"><label>Fecha de lanzamiento</label><input type="date" v-model="form.launch_date" class="form-control"></div>
          <div class="form-group"><label>Estado</label><label class="toggle-switch"><input type="checkbox" v-model="form.is_active"><span class="toggle-slider"></span></label></div>
        </div>
        <div class="admin-modal-footer"><button class="btn btn-secondary" @click="showModal = false">Cancelar</button><button class="btn btn-primary" @click="save">Guardar</button></div>
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
const items = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const form = reactive({ name: '', slug: '', launch_date: '', is_active: true })

function imgUrl(c) { return c.image ? (c.image.startsWith('http') ? c.image : `/uploads/collections/${c.image}`) : '/assets/foundnotimages/category.png' }
function openModal(c = null) { editing.value = c; form.name = c?.name || ''; form.slug = c?.slug || ''; form.launch_date = c?.launch_date || ''; form.is_active = c?.is_active !== false; showModal.value = true }

async function load() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/collections')
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    items.value = rows.map((row) => ({
      ...row,
      name: row.name || row.nombre || 'Sin nombre',
      image: row.image || row.imagen || null,
      is_active: typeof row.is_active === 'boolean' ? row.is_active : Boolean(Number(row.activo ?? 1)),
    }))
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando colecciones' })
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!form.name.trim()) return

  try {
    const payload = {
      nombre: form.name.trim(),
      slug: form.slug?.trim() || null,
      launch_date: form.launch_date || null,
      activo: form.is_active,
    }

    if (editing.value?.id) {
      await catalogHttp.put(`/admin/collections/${editing.value.id}`, payload)
      showSnackbar({ type: 'success', message: 'Coleccion actualizada' })
    } else {
      await catalogHttp.post('/admin/collections', payload)
      showSnackbar({ type: 'success', message: 'Coleccion creada' })
    }

    showModal.value = false
    await load()
  } catch {
    showSnackbar({ type: 'error', message: 'Error guardando coleccion' })
  }
}

function remove(c) {
  showAlert({
    type: 'warning',
    title: 'Eliminar',
    message: `¿Eliminar "${c.name}"?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await catalogHttp.delete(`/admin/collections/${c.id}`)
            showSnackbar({ type: 'success', message: 'Coleccion eliminada' })
            await load()
          } catch {
            showSnackbar({ type: 'error', message: 'Error eliminando coleccion' })
          }
        },
      },
    ],
  })
}

onMounted(load)
</script>
