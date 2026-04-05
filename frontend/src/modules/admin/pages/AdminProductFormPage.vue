<template>
  <div>
    <AdminPageHeader :icon="isEditing ? 'fas fa-edit' : 'fas fa-plus-circle'" :title="isEditing ? 'Editar producto' : 'Nuevo producto'" :subtitle="isEditing ? 'Modifica los datos del producto.' : 'Agrega un nuevo producto al catalogo.'" :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Productos', to: '/admin/productos' }, { label: isEditing ? 'Editar' : 'Nuevo' }]">
      <template #actions>
        <RouterLink to="/admin/productos" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver
        </RouterLink>
      </template>
    </AdminPageHeader>

    <AdminCard :flush="false">
      <form @submit.prevent="saveProduct">
        <div class="form-row">
          <div class="form-group">
            <label for="name">Nombre del producto *</label>
            <input id="name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="validateField('name')">
            <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
          </div>
          <div class="form-group">
            <label for="slug">Slug</label>
            <input id="slug" v-model="form.slug" class="form-control" placeholder="se-genera-automaticamente">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="price">Precio *</label>
            <input id="price" v-model="form.price" type="number" step="100" class="form-control" :class="{ 'is-invalid': errors.price }" @input="validateField('price')">
            <p v-if="errors.price" class="form-error">{{ errors.price }}</p>
          </div>
          <div class="form-group">
            <label for="category">Categoria</label>
            <select id="category" v-model="form.category_id" class="form-control">
              <option value="">Seleccionar...</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="description">Descripcion</label>
          <textarea id="description" v-model="form.description" class="form-control" rows="4"></textarea>
        </div>

        <div class="form-group">
          <label>Imagen principal</label>
          <div class="image-upload-area" @click="$refs.fileInput.click()">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Haz clic para seleccionar una imagen</p>
          </div>
          <input ref="fileInput" type="file" accept="image/*" style="display: none" @change="handleImage">
          <div v-if="imagePreview" class="image-preview">
            <img :src="imagePreview" alt="Preview">
            <button class="remove-image" type="button" @click="removeImage">&times;</button>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Estado</label>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.is_active">
              <span class="toggle-slider"></span>
            </label>
            <span style="margin-left: 0.5rem; font-size: 1.3rem;">{{ form.is_active ? 'Activo' : 'Inactivo' }}</span>
          </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
          <RouterLink to="/admin/productos" class="btn btn-secondary">Cancelar</RouterLink>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <i class="fas fa-save"></i> {{ saving ? 'Guardando...' : 'Guardar producto' }}
          </button>
        </div>
      </form>
    </AdminCard>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminCard from '../components/AdminCard.vue'

const route = useRoute()
const router = useRouter()
const { showSnackbar } = useSnackbarSystem()

const isEditing = computed(() => Boolean(route.params.id))
const saving = ref(false)
const categories = ref([])
const imagePreview = ref(null)
const imageFile = ref(null)

const form = reactive({
  name: '',
  slug: '',
  price: '',
  category_id: '',
  description: '',
  is_active: true,
})

const errors = reactive({
  name: '',
  price: '',
})

function validateField(field) {
  if (field === 'name') {
    errors.name = form.name.trim().length < 2 ? 'El nombre es obligatorio (min. 2 caracteres).' : ''
  }
  if (field === 'price') {
    errors.price = !form.price || Number(form.price) <= 0 ? 'El precio debe ser mayor a 0.' : ''
  }
}

function handleImage(e) {
  const file = e.target.files?.[0]
  if (!file) return
  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
}

function removeImage() {
  imageFile.value = null
  imagePreview.value = null
}

async function saveProduct() {
  validateField('name')
  validateField('price')
  if (errors.name || errors.price) return

  saving.value = true
  try {
    const payload = {
      nombre: form.name.trim(),
      slug: form.slug?.trim() || null,
      precio: Number(form.price),
      category_id: form.category_id || null,
      descripcion: form.description?.trim() || null,
      activo: form.is_active,
    }

    if (isEditing.value) {
      await catalogHttp.put(`/admin/products/${route.params.id}`, payload)
    } else {
      await catalogHttp.post('/admin/products', payload)
    }

    showSnackbar({ type: 'success', message: isEditing.value ? 'Producto actualizado' : 'Producto creado' })
    router.push('/admin/productos')
  } catch {
    showSnackbar({ type: 'error', message: 'Error guardando producto' })
  } finally {
    saving.value = false
  }
}

async function loadCategories() {
  try {
    const res = await catalogHttp.get('/admin/categories')
    const data = res.data?.data || res.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    categories.value = rows.map((category) => ({
      ...category,
      name: category.name || category.nombre || 'Sin nombre',
    }))
  } catch { /* ignora */ }
}

async function loadProduct() {
  if (!isEditing.value) return

  try {
    const response = await catalogHttp.get(`/admin/products/${route.params.id}`)
    const data = response.data?.data || {}
    const product = data.product || data

    form.name = product.name || product.nombre || ''
    form.slug = product.slug || ''
    form.price = Number(product.price ?? product.precio ?? 0)
    form.category_id = product.category_id || ''
    form.description = product.description || product.descripcion || ''
    form.is_active = typeof product.is_active === 'boolean'
      ? product.is_active
      : Boolean(Number(product.activo ?? 1))

    const currentImage = product.image || product.imagen || product.image_url || null
    if (currentImage) {
      imagePreview.value = currentImage.startsWith('http') || currentImage.startsWith('/')
        ? currentImage
        : `/uploads/productos/${currentImage}`
    }
  } catch {
    showSnackbar({ type: 'error', message: 'No se pudo cargar el producto' })
  }
}

onMounted(async () => {
  await loadCategories()
  await loadProduct()
})
</script>
