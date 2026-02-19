<template>
  <div class="libros">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Gestión de Libros</h2>
        <button class="btn btn-primary" @click="openModal()">Nuevo Libro</button>
      </div>

      <div v-if="loading" class="loading">Cargando libros...</div>

      <div v-else-if="error" class="alert alert-error">{{ error }}</div>

      <div v-else-if="libros.length === 0" class="empty-state">
        No hay libros registrados.
      </div>

      <table v-else class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Autor</th>
            <th>ISBN</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="libro in libros" :key="libro.id">
            <td>{{ libro.id }}</td>
            <td>{{ libro.titulo }}</td>
            <td>{{ libro.autor }}</td>
            <td>{{ libro.isbn }}</td>
            <td class="actions">
              <button class="btn btn-sm btn-warning" @click="openModal(libro)">Editar</button>
              <button class="btn btn-sm btn-danger" @click="deleteLibro(libro.id!)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ isEditing ? 'Editar Libro' : 'Nuevo Libro' }}</h3>
          <button class="modal-close" @click="closeModal">&times;</button>
        </div>

        <form @submit.prevent="saveLibro">
          <div class="form-group">
            <label class="form-label">Título</label>
            <input
              v-model="form.titulo"
              type="text"
              class="form-control"
              required
              placeholder="Ingrese el título"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Autor</label>
            <input
              v-model="form.autor"
              type="text"
              class="form-control"
              required
              placeholder="Ingrese el autor"
            />
          </div>

          <div class="form-group">
            <label class="form-label">ISBN</label>
            <input
              v-model="form.isbn"
              type="text"
              class="form-control"
              required
              placeholder="Ingrese el ISBN"
            />
          </div>

          <div v-if="formError" class="alert alert-error">{{ formError }}</div>

          <div class="modal-footer">
            <button type="button" class="btn" @click="closeModal">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { libroService } from '../services/api'
import type { Libro } from '../types'

const libros = ref<Libro[]>([])
const loading = ref(true)
const error = ref('')
const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')

const form = ref({
  titulo: '',
  autor: '',
  isbn: '',
})

const loadLibros = async () => {
  try {
    loading.value = true
    error.value = ''
    libros.value = await libroService.getAll()
  } catch (e: any) {
    error.value = e.response?.data?.detail || 'Error al cargar libros'
  } finally {
    loading.value = false
  }
}

const openModal = (libro?: Libro) => {
  formError.value = ''
  if (libro) {
    isEditing.value = true
    editingId.value = libro.id!
    form.value = {
      titulo: libro.titulo,
      autor: libro.autor,
      isbn: libro.isbn,
    }
  } else {
    isEditing.value = false
    editingId.value = null
    form.value = { titulo: '', autor: '', isbn: '' }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formError.value = ''
}

const saveLibro = async () => {
  try {
    saving.value = true
    formError.value = ''

    if (isEditing.value && editingId.value) {
      await libroService.update(editingId.value, form.value)
    } else {
      await libroService.create(form.value)
    }

    closeModal()
    await loadLibros()
  } catch (e: any) {
    formError.value = e.response?.data?.detail || e.response?.data?.['hydra:description'] || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

const deleteLibro = async (id: number) => {
  if (!confirm('¿Está seguro de eliminar este libro?')) return

  try {
    await libroService.delete(id)
    await loadLibros()
  } catch (e: any) {
    error.value = e.response?.data?.detail || 'Error al eliminar libro'
  }
}

onMounted(loadLibros)
</script>
