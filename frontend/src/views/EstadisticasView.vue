<template>
  <div class="estadisticas">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Estadísticas de Préstamos</h2>
      </div>

      <form @submit.prevent="loadEstadisticas" class="form-row" style="margin-bottom: 1.5rem;">
        <div class="form-group">
          <label class="form-label">Fecha Desde</label>
          <input
            v-model="desde"
            type="date"
            class="form-control"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Fecha Hasta</label>
          <input
            v-model="hasta"
            type="date"
            class="form-control"
            required
          />
        </div>

        <div class="form-group" style="display: flex; align-items: flex-end;">
          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Consultando...' : 'Consultar' }}
          </button>
        </div>
      </form>

      <div v-if="error" class="alert alert-error">{{ error }}</div>

      <div v-if="estadisticas" class="results">
        <div class="alert alert-success">
          <strong>Período:</strong> {{ estadisticas.periodo.desde }} al {{ estadisticas.periodo.hasta }}
          <br />
          <strong>Total de usuarios con préstamos:</strong> {{ estadisticas.totalUsuarios }}
        </div>

        <div v-if="estadisticas.usuarios.length === 0" class="empty-state">
          No se encontraron préstamos en el período seleccionado.
        </div>

        <table v-else class="table">
          <thead>
            <tr>
              <th>Posición</th>
              <th>Usuario</th>
              <th>DNI</th>
              <th>Total Préstamos</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(usuario, index) in estadisticas.usuarios" :key="usuario.id">
              <td>
                <span v-if="index === 0" class="badge badge-success">1</span>
                <span v-else-if="index === 1" class="badge badge-warning">2</span>
                <span v-else-if="index === 2" class="badge badge-danger">3</span>
                <span v-else>{{ index + 1 }}</span>
              </td>
              <td>{{ usuario.nombre }} {{ usuario.apellidos }}</td>
              <td>{{ usuario.dni }}</td>
              <td><strong>{{ usuario.totalPrestamos }}</strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { prestamoService } from '../services/api'
import type { EstadisticasResponse } from '../types'

const desde = ref('')
const hasta = ref('')
const loading = ref(false)
const error = ref('')
const estadisticas = ref<EstadisticasResponse | null>(null)

// Establecer fechas por defecto (primer y último día del mes actual)
const today = new Date()
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0)

desde.value = firstDay.toISOString().split('T')[0]
hasta.value = lastDay.toISOString().split('T')[0]

const loadEstadisticas = async () => {
  try {
    loading.value = true
    error.value = ''
    estadisticas.value = await prestamoService.getEstadisticas(desde.value, hasta.value)
  } catch (e: any) {
    error.value = e.response?.data?.error || e.response?.data?.detail || 'Error al cargar estadísticas'
    estadisticas.value = null
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.results {
  margin-top: 1rem;
}

.badge {
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}
</style>
