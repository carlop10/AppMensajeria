<!-- frontend/src/components/AppNavbar.vue -->
<template>
  <nav class="app-navbar" v-if="user">
    <div class="navbar-brand">
      <router-link to="/" class="brand-link">
        <span class="brand-icon">🚚</span>
        <span class="brand-text">Mi App Delivery</span>
      </router-link>
    </div>

    <div class="navbar-menu">
      <!-- Navegación para Administradores -->
      <template v-if="user.isAdmin()">
        <router-link to="/users" class="nav-link" active-class="active">
          <span class="nav-icon">👥</span>
          <span class="nav-text">Usuarios</span>
        </router-link>

        <router-link to="/couriers" class="nav-link" active-class="active">
          <span class="nav-icon">🏍️</span>
          <span class="nav-text">Mensajeros</span>
        </router-link>
      </template>

      <!-- Navegación común para todos los usuarios autenticados -->
      <router-link to="/deliveries" class="nav-link" active-class="active">
        <span class="nav-icon">📦</span>
        <span class="nav-text">Entregas</span>
      </router-link>

      <router-link to="/map" class="nav-link" active-class="active">
        <span class="nav-icon">🗺️</span>
        <span class="nav-text">Mapa</span>
      </router-link>

      <!-- Navegación específica para Mensajeros -->
      <template v-if="user.isCourier()">
        <router-link to="/courier-tracker" class="nav-link" active-class="active">
          <span class="nav-icon">📍</span>
          <span class="nav-text">Mi Tracking</span>
        </router-link>

        <router-link to="/dashboard-mensajeros" class="nav-link" active-class="active">
          <span class="nav-icon">📊</span>
          <span class="nav-text">Mi Dashboard</span>
        </router-link>
      </template>
    </div>

    <div class="navbar-user">
      <div class="user-info">
        <div class="user-avatar">
          {{ getUserInitials(user.name) }}
        </div>
        <div class="user-details">
          <span class="user-name">{{ user.name }}</span>
          <span class="user-role">{{ user.role_display }}</span>
        </div>
      </div>

      <div class="navbar-actions">
        <button @click="showUserMenu = !showUserMenu" class="user-menu-btn">
          <span class="menu-icon">⚙️</span>
        </button>

        <div v-if="showUserMenu" class="user-menu-dropdown">
          <router-link to="/profile" class="dropdown-item" @click="showUserMenu = false">
            <span class="dropdown-icon">👤</span>
            Mi Perfil
          </router-link>
          <button @click="logout" class="dropdown-item logout-btn">
            <span class="dropdown-icon">🚪</span>
            Cerrar Sesión
          </button>
        </div>
      </div>

      <!-- Overlay para cerrar el menú al hacer clic fuera -->
      <div v-if="showUserMenu" class="menu-overlay" @click="showUserMenu = false"></div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import apiClient from "@/api/axios";

const router = useRouter();
const user = ref(null);
const showUserMenu = ref(false);

// Función para obtener iniciales del usuario
const getUserInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

// Cargar información del usuario
onMounted(async () => {
  await loadUser();

  // Cerrar menú al hacer clic fuera
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
  const userMenu = document.querySelector('.navbar-actions');
  if (userMenu && !userMenu.contains(event.target)) {
    showUserMenu.value = false;
  }
};

const loadUser = async () => {
  const token = localStorage.getItem("token");
  if (!token) return;

  try {
    const { data } = await apiClient.get("/user");
    const userData = data.user || data;

    user.value = {
      ...userData,
      isAdmin: () => userData.role === 'admin',
      isCourier: () => userData.role === 'courier' || userData.courier !== null,
      role_display: userData.role_display ||
                   (userData.role === 'admin' ? 'Administrador' :
                   (userData.courier ? 'Mensajero' : 'Usuario'))
    };
  } catch (error) {
    console.error('Error cargando usuario:', error);
    // Si hay error de autenticación, hacer logout
    if (error.response?.status === 401) {
      logout();
    }
  }
};

const logout = async () => {
  try {
    await apiClient.post("/logout");
  } catch (error) {
    console.error('Error en logout:', error);
  } finally {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    user.value = null;
    showUserMenu.value = false;
    router.push("/login");
  }
};
</script>

<style scoped>
.app-navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  color: white;
  padding: 0 2rem;
  height: 64px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  position: relative;
  z-index: 1000;
}

/* Brand */
.navbar-brand {
  display: flex;
  align-items: center;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: white;
  font-weight: 700;
  font-size: 1.25rem;
}

.brand-icon {
  font-size: 1.5rem;
}

.brand-text {
  background: linear-gradient(135deg, #3b82f6, #60a5fa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Navigation Menu */
.navbar-menu {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  justify-content: center;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: #cbd5e1;
  border-radius: 8px;
  transition: all 0.2s ease;
  font-weight: 500;
  position: relative;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  transform: translateY(-1px);
}

.nav-link.active {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
}

.nav-icon {
  font-size: 1.125rem;
}

.nav-text {
  font-size: 0.875rem;
}

/* User Section */
.navbar-user {
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  color: white;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  font-size: 0.875rem;
}

.user-role {
  font-size: 0.75rem;
  color: #94a3b8;
}

/* User Menu */
.navbar-actions {
  position: relative;
}

.user-menu-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-menu-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

.menu-icon {
  font-size: 1rem;
}

.user-menu-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  min-width: 180px;
  padding: 0.5rem;
  margin-top: 0.5rem;
  z-index: 1001;
  animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: #374151;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  border-radius: 6px;
  transition: all 0.2s ease;
  cursor: pointer;
  font-size: 0.875rem;
}

.dropdown-item:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.dropdown-icon {
  font-size: 1rem;
  width: 16px;
  text-align: center;
}

.logout-btn {
  color: #dc2626;
}

.logout-btn:hover {
  background: #fef2f2;
  color: #b91c1c;
}

.menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 999;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .app-navbar {
    padding: 0 1rem;
  }

  .nav-text {
    display: none;
  }

  .nav-link {
    padding: 0.75rem;
  }

  .user-details {
    display: none;
  }
}

@media (max-width: 768px) {
  .navbar-menu {
    gap: 0.25rem;
  }

  .nav-link {
    padding: 0.5rem;
  }

  .brand-text {
    display: none;
  }

  .app-navbar {
    padding: 0 0.75rem;
  }
}

@media (max-width: 640px) {
  .navbar-menu {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #1e293b;
    padding: 0.5rem;
    justify-content: space-around;
    border-top: 1px solid #374151;
  }

  .app-navbar {
    padding-bottom: 60px; /* Espacio para el menú móvil */
  }

  .nav-link {
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.75rem;
    padding: 0.5rem;
  }

  .nav-icon {
    font-size: 1.25rem;
  }
}

/* Efectos de transición para rutas */
.nav-link {
  position: relative;
  overflow: hidden;
}

.nav-link::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 0;
  height: 2px;
  background: linear-gradient(135deg, #3b82f6, #60a5fa);
  transition: all 0.3s ease;
  transform: translateX(-50%);
}

.nav-link:hover::after,
.nav-link.active::after {
  width: 80%;
}

/* Efecto de brillo en hover */
.nav-link:hover .nav-icon {
  filter: brightness(1.2);
  transform: scale(1.1);
  transition: all 0.2s ease;
}
</style>
