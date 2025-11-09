<!-- frontend/src/App.vue -->
<template>
  <div id="app">
    <AppNavbar />
    <main :class="{ 'with-navbar': user }">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppNavbar from '@/components/Navbar.vue'

const user = ref(null)

onMounted(() => {
  // Verificar si hay usuario autenticado
  const token = localStorage.getItem('token')
  const userData = localStorage.getItem('user')

  if (token && userData) {
    user.value = JSON.parse(userData)
  }
})
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  background: #f8fafc;
  color: #334155;
  line-height: 1.5;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

main {
  flex: 1;
  padding: 0;
}

main.with-navbar {
  padding-top: 0;
}

/* Estilos globales para botones */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  font-size: 0.875rem;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #1d4ed8, #1e40af);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

/* Estilos para tarjetas */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
}

/* Utilidades de texto */
.text-muted {
  color: #6b7280;
}

.text-center {
  text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
  .btn {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
  }

  .card {
    padding: 1rem;
    border-radius: 8px;
  }
}
</style>
