<template>
  <div class="usuarios">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Gestión de Usuarios</h2>
        <button class="btn btn-primary" @click="openModal()">Nuevo Usuario</button>
      </div>

      <div v-if="loading" class="loading">Cargando usuarios...</div>

      <div v-else-if="error" class="alert alert-error">{{ error }}</div>

      <div v-else-if="usuarios.length === 0" class="empty-state">
        No hay usuarios registrados.
      </div>

      <table v-else class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellidos</th>
            <th>DNI</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="usuario in usuarios" :key="usuario.id">
            <td>{{ usuario.id }}</td>
            <td>{{ usuario.nombre }}</td>
            <td>{{ usuario.apellidos }}</td>
            <td>{{ usuario.dni }}</td>
            <td class="actions">
              <button class="btn btn-sm btn-warning" @click="openModal(usuario)">Editar</button>
              <button class="btn btn-sm btn-danger" @click="deleteUsuario(usuario.id!)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          <button class="modal-close" @click="closeModal">&times;</button>
        </div>

        <form @submit.prevent="saveUsuario">
          <div class="form-group">
            <label class="form-label">Nombre</label>
            <input
              v-model="form.nombre"
              type="text"
              class="form-control"
              required
              placeholder="Ingrese el nombre"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Apellidos</label>
            <input
              v-model="form.apellidos"
              type="text"
              class="form-control"
              required
              placeholder="Ingrese los apellidos"
            />
          </div>

          <div class="form-group">
            <label class="form-label">DNI</label>
            <input
              v-model="form.dni"
              type="text"
              class="form-control"
              required
              placeholder="Ingrese el DNI"
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
import { usuarioService } from '../services/api'
import type { Usuario } from '../types'

const usuarios = ref<Usuario[]>([])
const loading = ref(true)
const error = ref('')
const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')

const form = ref({
  nombre: '',
  apellidos: '',
  dni: '',
})

const loadUsuarios = async () => {
  try {
    loading.value = true
    error.value = ''
    usuarios.value = await usuarioService.getAll()
  } catch (e: any) {
    error.value = e.response?.data?.detail || 'Error al cargar usuarios'
  } finally {
    loading.value = false
  }
}

const openModal = (usuario?: Usuario) => {
  formError.value = ''
  if (usuario) {
    isEditing.value = true
    editingId.value = usuario.id!
    form.value = {
      nombre: usuario.nombre,
      apellidos: usuario.apellidos,
      dni: usuario.dni,
    }
  } else {
    isEditing.value = false
    editingId.value = null
    form.value = { nombre: '', apellidos: '', dni: '' }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formError.value = ''
}

const saveUsuario = async () => {
  try {
    saving.value = true
    formError.value = ''

    if (isEditing.value && editingId.value) {
      await usuarioService.update(editingId.value, form.value)
    } else {
      await usuarioService.create(form.value)
    }

    closeModal()
    await loadUsuarios()
  } catch (e: any) {
    formError.value = e.response?.data?.detail || e.response?.data?.['hydra:description'] || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

const deleteUsuario = async (id: number) => {
  if (!confirm('¿Está seguro de eliminar este usuario?')) return

  try {
    await usuarioService.delete(id)
    await loadUsuarios()
  } catch (e: any) {
    error.value = e.response?.data?.detail || 'Error al eliminar usuario'
  }
}

onMounted(loadUsuarios)
</script>
