import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import UsuariosView from '../views/UsuariosView.vue'
import LibrosView from '../views/LibrosView.vue'
import PrestamosView from '../views/PrestamosView.vue'
import EstadisticasView from '../views/EstadisticasView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/usuarios',
      name: 'usuarios',
      component: UsuariosView,
    },
    {
      path: '/libros',
      name: 'libros',
      component: LibrosView,
    },
    {
      path: '/prestamos',
      name: 'prestamos',
      component: PrestamosView,
    },
    {
      path: '/estadisticas',
      name: 'estadisticas',
      component: EstadisticasView,
    },
  ],
})

export default router
