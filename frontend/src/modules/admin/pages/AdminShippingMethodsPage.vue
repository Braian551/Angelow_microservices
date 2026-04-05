<template>
  <div>
    <AdminPageHeader icon="fas fa-truck" title="Metodos de envio" subtitle="Define los metodos de envio disponibles." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Metodos de envio' }]">
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nuevo metodo</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Metodos de envio" icon="fas fa-truck" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','line','line','line','pill','btn']" />
      <AdminEmptyState v-else-if="methods.length === 0" icon="fas fa-truck" title="Sin metodos de envio" description="Define metodos de envio para tus pedidos." />
      <table v-else class="admin-table">
        <thead><tr><th>Nombre</th><th>Descripcion</th><th>Tiempo estimado</th><th>Costo base</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="m in methods" :key="m.id">
            <td><strong>{{ m.name }}</strong></td>
            <td>{{ m.description || '—' }}</td>
            <td>{{ m.estimated_days ? `${m.estimated_days} dias` : '—' }}</td>
            <td>${{ Number(m.base_cost || 0).toLocaleString() }}</td>
            <td><span class="status-badge" :class="m.active ? 'approved' : 'rejected'">{{ m.active ? 'Activo' : 'Inactivo' }}</span></td>
            <td>
              <button class="btn btn-sm btn-secondary" @click="openModal(m)"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger" @click="deleteMethod(m.id)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </AdminCard>

    <!-- Modal -->
    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal">
        <div class="modal-header"><h3>{{ editing ? 'Editar metodo' : 'Nuevo metodo' }}</h3><button class="modal-close" @click="showModal = false">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Nombre</label><input v-model="form.name" class="form-input" placeholder="Ej: Envio estandar" /></div>
          <div class="form-group"><label>Descripcion</label><textarea v-model="form.description" class="form-input" rows="2"></textarea></div>
          <div class="form-group"><label>Tiempo estimado (dias)</label><input v-model.number="form.estimated_days" type="number" class="form-input" min="0" /></div>
          <div class="form-group"><label>Costo base ($)</label><input v-model.number="form.base_cost" type="number" class="form-input" min="0" /></div>
          <div class="form-group"><label class="toggle-label"><input type="checkbox" v-model="form.active" /> Activo</label></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveMethod">{{ editing ? 'Guardar' : 'Crear' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { shippingHttp } from '../../../services/http'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const { showSnackbar } = useSnackbarSystem()
const methods = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const form = ref({ name: '', description: '', estimated_days: null, base_cost: 0, active: true })

function openModal(method = null) {
  editing.value = method ? method.id : null
  form.value = method
    ? { ...method }
    : { name: '', description: '', estimated_days: null, base_cost: 0, active: true }
  showModal.value = true
}

async function loadMethods() {
  loading.value = true
  try {
    const { data } = await shippingHttp.get('/admin/shipping-methods')
    methods.value = data.data || data || []
  } catch { methods.value = [] } finally { loading.value = false }
}

async function saveMethod() {
  if (!form.value.name?.trim()) { showSnackbar({ type: 'warning', message: 'El nombre es requerido' }); return }
  try {
    if (editing.value) {
      await shippingHttp.put(`/admin/shipping-methods/${editing.value}`, form.value)
      showSnackbar({ type: 'success', message: 'Metodo actualizado' })
    } else {
      await shippingHttp.post('/admin/shipping-methods', form.value)
      showSnackbar({ type: 'success', message: 'Metodo creado' })
    }
    showModal.value = false
    await loadMethods()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar metodo' }) }
}

async function deleteMethod(id) {
  if (!confirm('¿Eliminar este metodo de envio?')) return
  try {
    await shippingHttp.delete(`/admin/shipping-methods/${id}`)
    showSnackbar({ type: 'success', message: 'Metodo eliminado' })
    await loadMethods()
  } catch { showSnackbar({ type: 'error', message: 'Error al eliminar' }) }
}

onMounted(loadMethods)
</script>
