<template>
  <div>
    <AdminPageHeader icon="fas fa-tags" title="Codigos de descuento" subtitle="Crea y gestiona codigos de descuento promocionales." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Codigos de descuento' }]">
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nuevo codigo</button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :stats="discountStats" :loading="loading" />

    <AdminCard title="Codigos de descuento" icon="fas fa-tags" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','pill','line','line','line','line','pill','btn']" />
      <AdminEmptyState v-else-if="codes.length === 0" icon="fas fa-tags" title="Sin codigos de descuento" description="Crea codigos promocionales para tus clientes." />
      <table v-else class="admin-table">
        <thead><tr><th>Codigo</th><th>Tipo</th><th>Valor</th><th>Min. compra</th><th>Usos / Limite</th><th>Expira</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="c in codes" :key="c.id">
            <td><code style="background:#f5f5f5;padding:2px 8px;border-radius:4px;">{{ c.code }}</code></td>
            <td>{{ c.type === 'percent' ? 'Porcentaje' : 'Fijo' }}</td>
            <td><strong>{{ c.type === 'percent' ? `${c.value}%` : `$${Number(c.value || 0).toLocaleString()}` }}</strong></td>
            <td>{{ c.min_purchase ? `$${Number(c.min_purchase).toLocaleString()}` : '—' }}</td>
            <td>{{ c.times_used || 0 }} / {{ c.max_uses || '∞' }}</td>
            <td>{{ c.expires_at || 'Sin fecha' }}</td>
            <td><span class="status-badge" :class="c.active ? 'approved' : 'rejected'">{{ c.active ? 'Activo' : 'Inactivo' }}</span></td>
            <td>
              <button class="btn btn-sm btn-secondary" @click="openModal(c)"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger" @click="deleteCode(c.id)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </AdminCard>

    <!-- Modal -->
    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal" style="max-width:550px;">
        <div class="modal-header"><h3>{{ editing ? 'Editar codigo' : 'Nuevo codigo' }}</h3><button class="modal-close" @click="showModal = false">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Codigo</label><input v-model="form.code" class="form-input" placeholder="Ej: VERANO20" style="text-transform:uppercase;" /></div>
          <div class="form-row">
            <div class="form-group" style="flex:1;"><label>Tipo</label>
              <select v-model="form.type" class="form-input"><option value="percent">Porcentaje</option><option value="fixed">Monto fijo</option></select>
            </div>
            <div class="form-group" style="flex:1;"><label>Valor</label><input v-model.number="form.value" type="number" class="form-input" min="0" /></div>
          </div>
          <div class="form-row">
            <div class="form-group" style="flex:1;"><label>Compra minima ($)</label><input v-model.number="form.min_purchase" type="number" class="form-input" min="0" /></div>
            <div class="form-group" style="flex:1;"><label>Usos maximos</label><input v-model.number="form.max_uses" type="number" class="form-input" min="0" placeholder="0 = ilimitado" /></div>
          </div>
          <div class="form-group"><label>Fecha de expiracion</label><input v-model="form.expires_at" type="date" class="form-input" /></div>
          <div class="form-group"><label class="toggle-label"><input type="checkbox" v-model="form.active" /> Activo</label></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveCode">{{ editing ? 'Guardar' : 'Crear' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { discountHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'

const { showSnackbar } = useSnackbarSystem()
const codes = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const emptyForm = { code: '', type: 'percent', value: 10, min_purchase: 0, max_uses: 0, expires_at: '', active: true }
const form = ref({ ...emptyForm })

const discountStats = computed(() => [
  { key: 'total', label: 'Total codigos', value: codes.value.length, icon: 'fas fa-tags', color: 'primary' },
  { key: 'active', label: 'Activos', value: codes.value.filter(c => c.active).length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'uses', label: 'Usos totales', value: codes.value.reduce((s, c) => s + (c.times_used || 0), 0), icon: 'fas fa-chart-bar', color: 'info' },
])

function openModal(code = null) {
  editing.value = code ? code.id : null
  form.value = code ? { ...code } : { ...emptyForm }
  showModal.value = true
}

async function loadCodes() {
  loading.value = true
  try {
    const { data } = await discountHttp.get('/admin/discount-codes')
    codes.value = data.data || data || []
  } catch { codes.value = [] } finally { loading.value = false }
}

async function saveCode() {
  if (!form.value.code?.trim()) { showSnackbar({ type: 'warning', message: 'El codigo es requerido' }); return }
  if (!form.value.value) { showSnackbar({ type: 'warning', message: 'El valor es requerido' }); return }
  const payload = { ...form.value, code: form.value.code.toUpperCase().trim() }
  try {
    if (editing.value) {
      await discountHttp.put(`/admin/discount-codes/${editing.value}`, payload)
      showSnackbar({ type: 'success', message: 'Codigo actualizado' })
    } else {
      await discountHttp.post('/admin/discount-codes', payload)
      showSnackbar({ type: 'success', message: 'Codigo creado' })
    }
    showModal.value = false
    await loadCodes()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar codigo' }) }
}

async function deleteCode(id) {
  if (!confirm('¿Eliminar este codigo de descuento?')) return
  try {
    await discountHttp.delete(`/admin/discount-codes/${id}`)
    showSnackbar({ type: 'success', message: 'Codigo eliminado' })
    await loadCodes()
  } catch { showSnackbar({ type: 'error', message: 'Error al eliminar' }) }
}

onMounted(loadCodes)
</script>
