<template>
  <div class="app">
    <header class="header">
      <h1>Biblioteca - Sistema de Gestión</h1>
      <nav class="nav">
        <router-link to="/">Inicio</router-link>
        <router-link to="/usuarios">Usuarios</router-link>
        <router-link to="/libros">Libros</router-link>
        <router-link to="/prestamos">Préstamos</router-link>
        <router-link to="/estadisticas">Estadísticas</router-link>
      </nav>
    </header>
    <main class="container">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
</script>
