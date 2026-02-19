export interface Usuario {
  id?: number
  nombre: string
  apellidos: string
  dni: string
  prestamos?: Prestamo[]
}

export interface Libro {
  id?: number
  titulo: string
  autor: string
  isbn: string
}

export interface Prestamo {
  id?: number
  usuario?: Usuario | string
  libro?: Libro | string
  fechaPrestamo: string
  fechaDevolucion?: string | null
}

export interface EstadisticasResponse {
  periodo: {
    desde: string
    hasta: string
  }
  totalUsuarios: number
  usuarios: EstadisticaUsuario[]
}

export interface EstadisticaUsuario {
  id: number
  nombre: string
  apellidos: string
  dni: string
  totalPrestamos: number
}

export interface ApiResponse<T> {
  'hydra:member': T[]
  'hydra:totalItems': number
}
