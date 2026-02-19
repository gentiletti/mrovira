<template>
  <div class="home">
    <div class="card">
      <h2>Bienvenido al Sistema de Gestión de Biblioteca</h2>
      <p>Este sistema permite gestionar usuarios, libros y préstamos de una biblioteca.</p>

      <div class="features">
        <div class="feature-card">
          <h3>Usuarios</h3>
          <p>Gestiona los usuarios registrados en la biblioteca.</p>
          <router-link to="/usuarios" class="btn btn-primary">Ver Usuarios</router-link>
        </div>

        <div class="feature-card">
          <h3>Libros</h3>
          <p>Administra el catálogo de libros disponibles.</p>
          <router-link to="/libros" class="btn btn-primary">Ver Libros</router-link>
        </div>

        <div class="feature-card">
          <h3>Préstamos</h3>
          <p>Registra y controla los préstamos de libros.</p>
          <router-link to="/prestamos" class="btn btn-primary">Ver Préstamos</router-link>
        </div>

        <div class="feature-card">
          <h3>Estadísticas</h3>
          <p>Consulta estadísticas de préstamos por período.</p>
          <router-link to="/estadisticas" class="btn btn-primary">Ver Estadísticas</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
.home h2 {
  margin-bottom: 1rem;
  color: var(--primary-color);
}

.home p {
  margin-bottom: 2rem;
  color: var(--text-light);
}

.features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}

.feature-card {
  background: var(--bg-color);
  padding: 1.5rem;
  border-radius: 8px;
  text-align: center;
}

.feature-card h3 {
  color: var(--text-color);
  margin-bottom: 0.5rem;
}

.feature-card p {
  margin-bottom: 1rem;
  font-size: 0.9rem;
}
</style>
