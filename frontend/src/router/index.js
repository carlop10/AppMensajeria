// frontend/src/router/index.js
import { createRouter, createWebHistory } from "vue-router";
import LoginView from "@/views/LoginView.vue";
import UserManagement from "@/views/UserManagement.vue";
import MapView from "@/views/MapView.vue";
import CourierManagement from "@/views/CourierManagement.vue";
import CourierDashboard from "@/views/CourierDashboard.vue";
import CourierTracker from "@/views/CourierTracker.vue";
import DeliveryManagement from "@/views/DeliveryManagement.vue";

const routes = [
  {
    path: "/login",
    component: LoginView,
    meta: { title: 'Login' }
  },
  {
    path: "/",
    redirect: "/login"
  },
  {
    path: "/users",
    component: UserManagement,
    meta: {
      requiresAuth: true,
      title: 'Gestión de Usuarios',
      requiresAdmin: true
    },
  },
  {
    path: "/map",
    component: MapView,
    meta: { requiresAuth: true, title: 'Mapa' },
  },
  {
    path: "/couriers",
    component: CourierManagement,
    meta: { requiresAuth: true, title: 'Gestión de Mensajeros' },
  },
  {
    path: "/dashboard-mensajeros",
    component: CourierDashboard,
    meta: { requiresAuth: true, title: 'Dashboard Mensajeros' },
  },
  {
    path: "/courier-tracker",
    component: CourierTracker,
    meta: { requiresAuth: true, title: 'Mi Tracking' },
  },
  {
    path: "/deliveries",
    component: DeliveryManagement,
    meta: { requiresAuth: true, title: 'Gestión de Entregas' },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Proteger rutas que requieran login
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");

  if (to.meta.requiresAuth && !token) {
    next("/login");
  } else {
    next();
  }
});

// Actualizar título de la página
router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} - Mi App` : 'Mi App';
});

export default router;
