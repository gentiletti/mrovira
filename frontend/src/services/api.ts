import axios from 'axios'
import type { Usuario, Libro, Prestamo, ApiResponse, EstadisticasResponse } from '../types'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/ld+json',
    'Accept': 'application/ld+json',
  },
})

// Usuarios
export const usuarioService = {
  async getAll(): Promise<Usuario[]> {
    const response = await api.get<ApiResponse<Usuario>>('/usuarios')
    return response.data['member'] || response.data['hydra:member'] || []
  },

  async getById(id: number): Promise<Usuario> {
    const response = await api.get<Usuario>(`/usuarios/${id}`)
    return response.data
  },

  async create(usuario: Omit<Usuario, 'id'>): Promise<Usuario> {
    const response = await api.post<Usuario>('/usuarios', usuario)
    return response.data
  },

  async update(id: number, usuario: Omit<Usuario, 'id'>): Promise<Usuario> {
    const response = await api.patch<Usuario>(`/usuarios/${id}`, usuario, {
      headers: { 'Content-Type': 'application/merge-patch+json' },
    })
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/usuarios/${id}`)
  },
}

// Libros
export const libroService = {
  async getAll(): Promise<Libro[]> {
    const response = await api.get<ApiResponse<Libro>>('/libros')
    return response.data['member'] || response.data['hydra:member'] || []
  },

  async getById(id: number): Promise<Libro> {
    const response = await api.get<Libro>(`/libros/${id}`)
    return response.data
  },

  async create(libro: Omit<Libro, 'id'>): Promise<Libro> {
    const response = await api.post<Libro>('/libros', libro)
    return response.data
  },

  async update(id: number, libro: Omit<Libro, 'id'>): Promise<Libro> {
    const response = await api.patch<Libro>(`/libros/${id}`, libro, {
      headers: { 'Content-Type': 'application/merge-patch+json' },
    })
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/libros/${id}`)
  },
}

// Prestamos
export const prestamoService = {
  async getAll(): Promise<Prestamo[]> {
    const response = await api.get<ApiResponse<Prestamo>>('/prestamos')
    return response.data['member'] || response.data['hydra:member'] || []
  },

  async getById(id: number): Promise<Prestamo> {
    const response = await api.get<Prestamo>(`/prestamos/${id}`)
    return response.data
  },

  async create(prestamo: { usuario: string; libro: string; fechaPrestamo: string }): Promise<Prestamo> {
    const response = await api.post<Prestamo>('/prestamos', prestamo)
    return response.data
  },

  async devolver(id: number): Promise<Prestamo> {
    const response = await api.patch<Prestamo>(
      `/prestamos/${id}`,
      { fechaDevolucion: new Date().toISOString() },
      { headers: { 'Content-Type': 'application/merge-patch+json' } }
    )
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/prestamos/${id}`)
  },

  async getEstadisticas(desde: string, hasta: string): Promise<EstadisticasResponse> {
    const response = await api.get<EstadisticasResponse>('/estadisticas/prestamos', {
      params: { desde, hasta },
    })
    return response.data
  },
}

export default api
