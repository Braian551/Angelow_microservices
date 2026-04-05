<template>
  <div>
    <AdminPageHeader
      icon="fas fa-ruler"
      title="Tallas"
      subtitle="Administra las tallas disponibles."
      :breadcrumbs="[{ label: 'Tallas' }]"
    >
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nueva talla</button>
      </template>
    </AdminPageHeader>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'btn']" />
      <AdminEmptyState v-else-if="items.length === 0" icon="fas fa-ruler" title="Sin tallas" description="Crea tallas para los productos." />
      <div v-else class="table-responsive"><table class="dashboard-table">
        <thead><tr><th>ID</th><th>Nombre</th><th>Orden</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="s in items" :key="s.id">
            <td>{{ s.id }}</td><td><strong>{{ s.name }}</strong></td><td>{{ s.sort_order || '-' }}</td>
            <td><button class="action-btn edit" @click="openModal(s)"><i class="fas fa-edit"></i></button><button class="action-btn delete" @click="remove(s)"><i class="fas fa-trash"></i></button></td>
          </tr>
        </tbody>
      </table></div>
    </AdminCard>

    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal" style="max-width:400px;">
        <div class="admin-modal-header"><h3>{{ editing ? 'Editar' : 'Nueva' }} talla</h3><button class="admin-modal-close" @click="showModal = false">&times;</button></div>
        <div class="admin-modal-body">
          <div class="form-group"><label>Nombre *</label><input v-model="form.name" class="form-control" :class="{ 'is-invalid': !form.name.trim() && submitted }"></div>
          <div class="form-group"><label>Orden</label><input v-model="form.sort_order" type="number" class="form-control"></div>
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
const items = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null), submitted = ref(false)
const form = reactive({ name: '', sort_order: '' })

function openModal(s = null) { editing.value = s; form.name = s?.name || ''; form.sort_order = s?.sort_order || ''; submitted.value = false; showModal.value = true }

function normalizeSize(rawSize, index) {
  return {
    id: rawSize.id ?? index + 1,
    name: rawSize.name || rawSize.nombre || rawSize.size_label || 'Sin nombre',
    sort_order: rawSize.sort_order ?? rawSize.order_position ?? rawSize.orden ?? null,
  }
}

async function load() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/sizes')
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    items.value = rows.map(normalizeSize)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando tallas' })
  } finally {
    loading.value = false
  }
}

async function save() {
  submitted.value = true
  if (!form.name.trim()) return

  try {
    const payload = {
      name: form.name.trim(),
      sort_order: form.sort_order === '' ? null : Number(form.sort_order),
    }

    if (editing.value?.id) {
      await catalogHttp.put(`/admin/sizes/${editing.value.id}`, payload)
      showSnackbar({ type: 'success', message: 'Talla actualizada' })
    } else {
      await catalogHttp.post('/admin/sizes', payload)
      showSnackbar({ type: 'success', message: 'Talla creada' })
    }

    showModal.value = false
    await load()
  } catch {
    showSnackbar({ type: 'error', message: 'Error guardando talla' })
  }
}

function remove(s) {
  showAlert({
    type: 'warning',
    title: 'Eliminar',
    message: `¿Eliminar "${s.name}"?`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await catalogHttp.delete(`/admin/sizes/${s.id}`)
            showSnackbar({ type: 'success', message: 'Talla eliminada' })
            await load()
          } catch {
            showSnackbar({ type: 'error', message: 'Error eliminando talla' })
          }
        },
      },
    ],
  })
}

onMounted(load)
</script>
