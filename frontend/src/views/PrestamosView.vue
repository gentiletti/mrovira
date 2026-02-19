<template>
  <div class="prestamos">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Gestión de Préstamos</h2>
        <button class="btn btn-primary" @click="openModal()">Nuevo Préstamo</button>
      </div>

      <div v-if="loading" class="loading">Cargando préstamos...</div>

      <div v-else-if="error" class="alert alert-error">{{ error }}</div>

      <div v-else-if="prestamos.length === 0" class="empty-state">
        No hay préstamos registrados.
      </div>

      <table v-else class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Libro</th>
            <th>Fecha Préstamo</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="prestamo in prestamos" :key="prestamo.id">
            <td>{{ prestamo.id }}</td>
            <td>
              <template v-if="typeof prestamo.usuario === 'object'">
                {{ prestamo.usuario?.nombre }} {{ prestamo.usuario?.apellidos }}
              </template>
              <template v-else>{{ prestamo.usuario }}</template>
            </td>
            <td>
              <template v-if="typeof prestamo.libro === 'object'">
                {{ prestamo.libro?.titulo }}
              </template>
              <template v-else>{{ prestamo.libro }}</template>
            </td>
            <td>{{ formatDate(prestamo.fechaPrestamo) }}</td>
            <td>
              <span v-if="prestamo.fechaDevolucion" class="badge badge-success">
                Devuelto {{ formatDate(prestamo.fechaDevolucion) }}
              </span>
              <span v-else class="badge badge-warning">Activo</span>
            </td>
            <td class="actions">
              <button
                v-if="!prestamo.fechaDevolucion"
                class="btn btn-sm btn-success"
                @click="devolverLibro(prestamo.id!)"
              >
                Devolver
              </button>
              <button class="btn btn-sm btn-danger" @click="deletePrestamo(prestamo.id!)">
                Eliminar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Nuevo Préstamo -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Nuevo Préstamo</h3>
          <button class="modal-close" @click="closeModal">&times;</button>
        </div>

        <form @submit.prevent="savePrestamo">
          <div class="form-group">
            <label class="form-label">Usuario</label>
            <select v-model="form.usuarioId" class="form-control" required>
              <option value="">Seleccione un usuario</option>
              <option v-for="u in usuarios" :key="u.id" :value="u.id">
                {{ u.nombre }} {{ u.apellidos }} ({{ u.dni }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Libro</label>
            <select v-model="form.libroId" class="form-control" required>
              <option value="">Seleccione un libro</option>
              <option v-for="l in libros" :key="l.id" :value="l.id">
                {{ l.titulo }} - {{ l.autor }} ({{ l.isbn }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Fecha de Préstamo</label>
            <input
              v-model="form.fechaPrestamo"
              type="datetime-local"
              class="form-control"
              required
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
import { prestamoService, usuarioService, libroService } from '../services/api'
import type { Prestamo, Usuario, Libro } from '../types'

const prestamos = ref<Prestamo[]>([])
const usuarios = ref<Usuario[]>([])
const libros = ref<Libro[]>([])
const loading = ref(true)
const error = ref('')
const showModal = ref(false)
const saving = ref(false)
const formError = ref('')

const form = ref({
  usuarioId: '',
  libroId: '',
  fechaPrestamo: '',
})

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const loadData = async () => {
  try {
    loading.value = true
    error.value = ''
    const [p, u, l] = await Promise.all([
      prestamoService.getAll(),
      usuarioService.getAll(),
      libroService.getAll(),
    ])
    prestamos.value = p
    usuarios.value = u
    libros.value = l
  } catch (e: any) {
    error.value = e.response?.data?.detail || 'Error al cargar datos'
  } finally {
    loading.value = false
  }
}

const openModal = () => {
  formError.value = ''
  form.value = {
    usuarioId: '',
    libroId: '',
    fechaPrestamo: new Date().toISOString().slice(0, 16),
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formError.value = ''
}

const savePrestamo = async () => {
  try {
    saving.value = true
    formError.value = ''

    await prestamoService.create({
      usuario: `/api/usuarios/${form.value.usuarioId}`,
      libro: `/api/libros/${form.value.libroId}`,
      fechaPrestamo: new Date(form.value.fechaPrestamo).toISOString(),
    })

    closeModal()
    await loadData()
  } catch (e: any) {
    formError.value = e.response?.data?.detail || e.response?.data?.['hydra:description'] || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

const devolverLibro = async (id: number) => {
  if (!confirm('¿Confirma la devolución del libro?')) return

  try {
    await prestamoService.devolver(id)
    await loadData()
  } catch (e: any) {
    error.value = e.response?.data?.detail || 'Error al devolver libro'
  }
}

const deletePrestamo = async (id: number) => {
  if (!confirm('¿Está seguro de eliminar este préstamo?')) return

  try {
    await prestamoService.delete(id)
    await loadData()
  } catch (e: any) {
    error.value = e.response?.data?.detail || 'Error al eliminar préstamo'
  }
}

onMounted(loadData)
</script>
