// frontend/src/router/index.js
import { createRouter, createWebHistory } from "vue-router";
import LoginView from "@/views/LoginView.vue";
import UserListView from "@/views/UserListView.vue";
import UserCreateView from "@/views/UserCreateView.vue";
import MapView from "@/views/MapView.vue";
import CourierManagement from "@/views/CourierManagement.vue";
import CourierDashboard from "@/views/CourierDashboard.vue";
import CourierTracker from "@/views/CourierTracker.vue";
import DeliveryManagement from "@/views/DeliveryManagement.vue";

const routes = [
  {
    path: "/login",
    component: LoginView,
    meta: { title: 'Login', public: true }
  },
  {
    path: "/",
    redirect: "/login"
  },
  {
    path: "/users",
    component: UserListView,
    meta: {
      requiresAuth: true,
      title: 'Gestión de Usuarios',
      requiresAdmin: true
    },
  },
  {
    path: "/users/create",
    component: UserCreateView,
    meta: {
      requiresAuth: true,
      title: 'Crear Usuario',
      requiresAdmin: true
    },
  },
  {
    path: "/map",
    component: MapView,
    meta: {
      requiresAuth: true,
      title: 'Mapa'
    },
  },
  {
    path: "/couriers",
    component: CourierManagement,
    meta: {
      requiresAuth: true,
      title: 'Gestión de Mensajeros',
      requiresAdmin: true
    },
  },
  {
    path: "/dashboard-mensajeros",
    component: CourierDashboard,
    meta: {
      requiresAuth: true,
      title: 'Dashboard Mensajeros',
      requiresCourier: true
    },
  },
  {
    path: "/courier-tracker",
    component: CourierTracker,
    meta: {
      requiresAuth: true,
      title: 'Mi Tracking',
      requiresCourier: true
    },
  },
  {
    path: "/deliveries",
    component: DeliveryManagement,
    meta: {
      requiresAuth: true,
      title: 'Gestión de Entregas'
    },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Guard de autenticación y roles
router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem("token");

  // Rutas públicas
  if (to.meta.public) {
    return next();
  }

  // Verificar autenticación
  if (to.meta.requiresAuth && !token) {
    return next("/login");
  }

  // Si está autenticado, cargar información del usuario
  if (token) {
    try {
      const response = await fetch('http://localhost:8000/api/user', {

        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });

      if (response.ok) {
        const userData = await response.json();

        // Verificar roles
        if (to.meta.requiresAdmin && !userData.user.isAdmin()) {
          return next('/deliveries'); // Redirigir a una ruta permitida
        }

        if (to.meta.requiresCourier && !userData.user.isCourier()) {
          return next('/deliveries'); // Redirigir a una ruta permitida
        }

        next();
      } else {
        // Token inválido
        localStorage.removeItem('token');
        next('/login');
      }
    } catch (error) {
      console.error('Error verificando autenticación:', error);
      localStorage.removeItem('token');
      next('/login');
    }
  } else {
    next();
  }
});

// Actualizar título de la página
router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} - Mi App Delivery` : 'Mi App Delivery';
});

export default router;
