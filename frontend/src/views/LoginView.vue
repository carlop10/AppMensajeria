<!-- frontend/src/views/LoginView.vue -->
<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo">
          <span class="logo-icon">🚚</span>
          <h1>Mi App Delivery</h1>
        </div>
        <p class="subtitle">Sistema de Gestión de Entregas</p>
      </div>

      <form @submit.prevent="login" class="login-form">
        <div class="form-group">
          <label for="email">Correo Electrónico</label>
          <input
            v-model="email"
            type="email"
            id="email"
            placeholder="tu@ejemplo.com"
            required
            :disabled="loading"
          />
          <!-- <span class="input-icon">📧</span> -->
        </div>

        <div class="form-group">
          <label for="password">Contraseña</label>
          <input
            v-model="password"
            type="password"
            id="password"
            placeholder="••••••••"
            required
            :disabled="loading"
          />
          <!-- <span class="input-icon">🔒</span> -->

        </div>

        <button
          type="submit"
          class="login-btn"
          :disabled="loading || !email || !password"
        >
          <span v-if="loading" class="loading-spinner"></span>
          <span v-else>🚀 Iniciar Sesión</span>
          {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
        </button>

        <div v-if="error" class="error-message">
          <span class="error-icon">⚠️</span>
          {{ error }}
        </div>
      </form>

      <!-- <div class="login-footer">
        <p class="demo-accounts">
          <strong>Cuentas de demostración:</strong><br>
          👨‍💼 Admin: admin@example.com / 12345678<br>
          🏍️ Mensajero: courier@example.com / 12345678
        </p>
      </div> -->
    </div>

    <div class="background-shapes">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import apiClient from "@/api/axios.js";

const router = useRouter();
const email = ref("");
const password = ref("");
const error = ref("");
const loading = ref(false);

// Para desarrollo/demo - autocompletar credenciales
const demoAccounts = {
  admin: { email: 'admin@example.com', password: '12345678' },
  courier: { email: 'courier@example.com', password: '12345678' }
};

const canSubmit = computed(() => {
  return email.value && password.value && !loading.value;
});

const login = async () => {
  if (!canSubmit.value) return;

  loading.value = true;
  error.value = "";

  try {
    const response = await apiClient.post("/login", {
      email: email.value,
      password: password.value,
    });

    const { token, user } = response.data;

    // Guardar token y usuario
    localStorage.setItem("token", token);
    localStorage.setItem("user", JSON.stringify(user));

    // Configurar el header de autorización para futuras requests
    apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    // Mensaje de bienvenida
    const welcomeMessage = getWelcomeMessage(user);

    // Redirigir según el rol del usuario
    setTimeout(() => {
      redirectUser(user);
    }, 500);

  } catch (err) {
    console.error('Error de login:', err);
    error.value = err.response?.data?.message ||
                  err.response?.data?.error ||
                  "Error al conectar con el servidor. Verifica tu conexión.";
  } finally {
    loading.value = false;
  }
};

const getWelcomeMessage = (user) => {
  if (user.role === 'admin') {
    return `¡Bienvenido administrador ${user.name}!`;
  } else if (user.courier) {
    return `¡Bienvenido mensajero ${user.name}!`;
  } else {
    return `¡Bienvenido ${user.name}!`;
  }
};

const redirectUser = (user) => {
  if (user.role === 'admin') {
    router.push("/deliveries");
  } else if (user.courier) {
    router.push("/courier-tracker");
  } else {
    router.push("/deliveries");
  }
};

// Función para autocompletar credenciales de demo (opcional)
const fillDemoCredentials = (type) => {
  email.value = demoAccounts[type].email;
  password.value = demoAccounts[type].password;
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
  position: relative;
  overflow: hidden;
}

.login-card {
  background: white;
  padding: 2.5rem;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
  position: relative;
  z-index: 10;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.logo-icon {
  font-size: 2rem;
}

.logo h1 {
  font-size: 1.5rem;
  color: #1f2937;
  margin: 0;
}

.subtitle {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.form-group input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
  background: #f9fafb;
}

.form-group input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group input:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

.input-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.125rem;
  color: #9ca3af;
}

.login-btn {
  width: 100%;
  padding: 0.875rem 1.5rem;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 48px;
}

.login-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #1d4ed8, #1e40af);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.login-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: #fef2f2;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 1px solid #fecaca;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.error-icon {
  font-size: 1rem;
}

.login-footer {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.demo-accounts {
  font-size: 0.75rem;
  color: #6b7280;
  text-align: center;
  line-height: 1.4;
}

.demo-accounts strong {
  color: #374151;
  display: block;
  margin-bottom: 0.5rem;
}

.background-shapes {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
  pointer-events: none;
}

.shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
}

.shape-1 {
  width: 200px;
  height: 200px;
  top: -50px;
  right: -50px;
}

.shape-2 {
  width: 150px;
  height: 150px;
  bottom: 100px;
  left: -50px;
}

.shape-3 {
  width: 100px;
  height: 100px;
  bottom: -30px;
  right: 30%;
}

/* Responsive */
@media (max-width: 480px) {
  .login-card {
    padding: 2rem 1.5rem;
  }

  .logo {
    flex-direction: column;
    gap: 0.5rem;
  }

  .logo h1 {
    font-size: 1.25rem;
  }
}
</style>
