<template>
  <div>
    <AdminPageHeader icon="fas fa-bullhorn" title="Anuncios" subtitle="Gestiona los anuncios y banners de la tienda." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Anuncios' }]">
      <template #actions>
        <button class="btn btn-primary" @click="openModal()"><i class="fas fa-plus"></i> Nuevo anuncio</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Anuncios" icon="fas fa-bullhorn" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line','pill','pill','line','line','pill','btn']" />
      <AdminEmptyState v-else-if="announcements.length === 0" icon="fas fa-bullhorn" title="Sin anuncios" description="Crea anuncios para informar a tus clientes." />
      <table v-else class="admin-table">
        <thead><tr><th>Titulo</th><th>Tipo</th><th>Prioridad</th><th>Inicio</th><th>Fin</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="a in announcements" :key="a.id">
            <td><strong>{{ a.title }}</strong></td>
            <td>{{ typeLabel(a.type) }}</td>
            <td><span class="status-badge" :class="a.priority === 'high' ? 'rejected' : a.priority === 'medium' ? 'pending' : 'approved'">{{ a.priority || 'Normal' }}</span></td>
            <td>{{ a.start_date || '—' }}</td>
            <td>{{ a.end_date || '—' }}</td>
            <td><span class="status-badge" :class="a.active ? 'approved' : 'rejected'">{{ a.active ? 'Activo' : 'Inactivo' }}</span></td>
            <td>
              <button class="btn btn-sm btn-secondary" @click="openModal(a)"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger" @click="deleteAnnouncement(a.id)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </AdminCard>

    <!-- Modal -->
    <div v-if="showModal" class="admin-modal-overlay" @click.self="showModal = false">
      <div class="admin-modal" style="max-width:600px;">
        <div class="modal-header"><h3>{{ editing ? 'Editar anuncio' : 'Nuevo anuncio' }}</h3><button class="modal-close" @click="showModal = false">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Titulo</label><input v-model="form.title" class="form-input" /></div>
          <div class="form-group"><label>Contenido</label><textarea v-model="form.content" class="form-input" rows="3"></textarea></div>
          <div class="form-row">
            <div class="form-group" style="flex:1;"><label>Tipo</label>
              <select v-model="form.type" class="form-input"><option value="banner">Banner</option><option value="bar">Barra superior</option><option value="popup">Pop-up</option></select>
            </div>
            <div class="form-group" style="flex:1;"><label>Prioridad</label>
              <select v-model="form.priority" class="form-input"><option value="low">Baja</option><option value="medium">Media</option><option value="high">Alta</option></select>
            </div>
          </div>
          <div class="form-group"><label>URL destino (opcional)</label><input v-model="form.url" class="form-input" placeholder="https://..." /></div>
          <div class="form-group"><label>Imagen</label>
            <div class="image-upload-zone" @click="$refs.fileInput.click()"><i class="fas fa-cloud-upload-alt"></i><p>Haz clic para subir imagen</p></div>
            <input ref="fileInput" type="file" accept="image/*" style="display:none;" @change="onFileSelect" />
            <img v-if="form.image_url" :src="form.image_url" class="image-preview" style="max-width:200px;margin-top:0.5rem;border-radius:6px;" />
          </div>
          <div class="form-row">
            <div class="form-group" style="flex:1;"><label>Fecha inicio</label><input v-model="form.start_date" type="date" class="form-input" /></div>
            <div class="form-group" style="flex:1;"><label>Fecha fin</label><input v-model="form.end_date" type="date" class="form-input" /></div>
          </div>
          <div class="form-group"><label class="toggle-label"><input type="checkbox" v-model="form.active" /> Activo</label></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveAnnouncement">{{ editing ? 'Guardar' : 'Crear' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { notificationHttp } from '../../../services/http'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const { showSnackbar } = useSnackbarSystem()
const announcements = ref([]), loading = ref(true), showModal = ref(false), editing = ref(null)
const emptyForm = { title: '', content: '', type: 'banner', priority: 'medium', url: '', image_url: '', start_date: '', end_date: '', active: true }
const form = ref({ ...emptyForm })

function typeLabel(t) { return { banner: 'Banner', bar: 'Barra superior', popup: 'Pop-up' }[t] || t }
function openModal(a = null) {
  editing.value = a ? a.id : null
  form.value = a ? { ...a } : { ...emptyForm }
  showModal.value = true
}

function onFileSelect(e) {
  const file = e.target.files?.[0]
  if (file) form.value.image_url = URL.createObjectURL(file)
  // TODO: Subir archivo al servidor
}

async function loadAnnouncements() {
  loading.value = true
  try {
    const { data } = await notificationHttp.get('/admin/announcements')
    announcements.value = data.data || data || []
  } catch { announcements.value = [] } finally { loading.value = false }
}

async function saveAnnouncement() {
  if (!form.value.title?.trim()) { showSnackbar({ type: 'warning', message: 'El titulo es requerido' }); return }
  try {
    if (editing.value) {
      await notificationHttp.put(`/admin/announcements/${editing.value}`, form.value)
      showSnackbar({ type: 'success', message: 'Anuncio actualizado' })
    } else {
      await notificationHttp.post('/admin/announcements', form.value)
      showSnackbar({ type: 'success', message: 'Anuncio creado' })
    }
    showModal.value = false
    await loadAnnouncements()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar anuncio' }) }
}

async function deleteAnnouncement(id) {
  if (!confirm('¿Eliminar este anuncio?')) return
  try {
    await notificationHttp.delete(`/admin/announcements/${id}`)
    showSnackbar({ type: 'success', message: 'Anuncio eliminado' })
    await loadAnnouncements()
  } catch { showSnackbar({ type: 'error', message: 'Error al eliminar' }) }
}

onMounted(loadAnnouncements)
</script>
