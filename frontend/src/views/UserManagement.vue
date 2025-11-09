<!-- frontend/src/views/UserManagement.vue -->
<template>
  <div class="management-wrapper">
    <div class="header-section">
      <div class="header-content">
        <h2>👥 Gestión de Usuarios</h2>
        <p class="subtitle">Administra todos los usuarios del sistema</p>
      </div>
      <button @click="openCreateModal" class="btn-primary">
        <span class="btn-icon">➕</span>
        Nuevo Usuario
      </button>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.total }}</div>
          <div class="stat-label">Total</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">👑</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.admins }}</div>
          <div class="stat-label">Administradores</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🏍️</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.couriers }}</div>
          <div class="stat-label">Mensajeros</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">👤</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.regular }}</div>
          <div class="stat-label">Usuarios</div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filters">
      <div class="filter-group">
        <label>Rol:</label>
        <select v-model="filters.role" @change="loadUsers">
          <option value="">Todos los roles</option>
          <option value="admin">Administradores</option>
          <option value="courier">Mensajeros</option>
          <option value="user">Usuarios regulares</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Buscar:</label>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Nombre o email..."
          @input="debouncedSearch"
        />
      </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="table-container">
      <div class="table-header">
        <h3>Lista de Usuarios ({{ users.length }})</h3>
        <div class="table-actions">
          <button @click="loadUsers" class="btn-secondary">
            <span class="btn-icon">🔄</span>
            Actualizar
          </button>
          <button @click="exportToCSV" class="btn-secondary" :disabled="users.length === 0">
            <span class="btn-icon">📊</span>
            Exportar CSV
          </button>
        </div>
      </div>

      <table v-if="users.length > 0">
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Información</th>
            <th>Estado</th>
            <th>Registro</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id"
              :class="{ 'inactive': !user.is_active }">
            <td class="user-info">
              <div class="user-avatar">
                {{ getUserInitials(user.name) }}
              </div>
              <div class="user-details">
                <strong>{{ user.name }}</strong>
                <div class="user-email">{{ user.email }}</div>
              </div>
            </td>
            <td class="role-cell">
              <span :class="`role-badge ${user.role}`">
                <span class="role-icon">{{ getRoleIcon(user.role) }}</span>
                {{ user.role_display }}
              </span>
              <div v-if="user.courier" class="courier-info">
                <small>📞 {{ user.courier.phone }}</small>
              </div>
            </td>
            <td class="additional-info">
              <div v-if="user.courier" class="courier-details">
                <div class="vehicle-info">
                  <span class="vehicle-icon">{{ getVehicleIcon(user.courier.vehicle_type) }}</span>
                  {{ getVehicleName(user.courier.vehicle_type) }}
                </div>
                <div v-if="user.courier.vehicle_plate" class="vehicle-plate">
                  🪪 {{ user.courier.vehicle_plate }}
                </div>
              </div>
              <div v-else class="no-additional-info">
                <span class="text-muted">Sin información adicional</span>
              </div>
            </td>
            <td class="status-cell">
              <div class="status-indicator">
                <span class="status-dot" :class="user.is_active ? 'active' : 'inactive'"></span>
                {{ user.is_active ? 'Activo' : 'Inactivo' }}
              </div>
              <div v-if="user.courier" class="courier-status">
                <span :class="`status-badge mini ${user.courier.status}`">
                  {{ user.courier.status }}
                </span>
              </div>
            </td>
            <td class="date-info">
              <div class="register-date">
                {{ formatDate(user.created_at) }}
              </div>
              <div v-if="user.last_login" class="last-login">
                <small>Último acceso: {{ formatTimeAgo(user.last_login) }}</small>
              </div>
            </td>
            <td class="actions">
              <div class="action-buttons">
                <button @click="viewUser(user)" class="btn-icon" title="Ver detalles">
                  👁️
                </button>
                <button @click="editUser(user)" class="btn-icon" title="Editar">
                  ✏️
                </button>
                <button @click="toggleActive(user)"
                        :class="`btn-icon ${user.is_active ? 'btn-warning' : 'btn-success'}`"
                        :title="user.is_active ? 'Desactivar' : 'Activar'">
                  {{ user.is_active ? '⏸️' : '▶️' }}
                </button>
                <button v-if="!user.courier && user.role !== 'admin'"
                        @click="convertToCourier(user)"
                        class="btn-icon btn-info"
                        title="Convertir a mensajero">
                  🏍️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>No hay usuarios registrados</h3>
        <p>Comienza agregando el primer usuario al sistema</p>
        <button @click="openCreateModal" class="btn-primary">
          <span class="btn-icon">➕</span>
          Crear primer usuario
        </button>
      </div>
    </div>

    <!-- Modal de Crear/Editar Usuario -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content modal-medium">
        <div class="modal-header">
          <h3>{{ editingUser ? '✏️ Editar Usuario' : '➕ Nuevo Usuario' }}</h3>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>

        <form @submit.prevent="saveUser" class="user-form">
          <div class="form-section">
            <h4>Información Personal</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Nombre completo *</label>
                <input v-model="form.name" type="text" required
                       placeholder="Ej: María González" />
              </div>
              <div class="form-group">
                <label>Email *</label>
                <input v-model="form.email" type="email" required
                       placeholder="ejemplo@correo.com"
                       :readonly="!!editingUser" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" v-if="!editingUser">
                <label>Contraseña *</label>
                <input v-model="form.password" type="password" required
                       minlength="8" placeholder="Mínimo 8 caracteres" />
              </div>
              <div class="form-group" v-if="!editingUser">
                <label>Confirmar contraseña *</label>
                <input v-model="form.password_confirmation" type="password" required
                       placeholder="Repite la contraseña" />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4>Rol del Usuario</h4>
            <div class="form-group">
              <label>Seleccionar rol *</label>
              <div class="role-options">
                <label class="role-option" :class="{ selected: form.role === 'admin' }">
                  <input
                    v-model="form.role"
                    type="radio"
                    value="admin"
                    required
                  />
                  <div class="role-content">
                    <span class="role-icon">👑</span>
                    <div class="role-info">
                      <strong>Administrador</strong>
                      <small>Acceso completo al sistema</small>
                    </div>
                  </div>
                </label>

                <label class="role-option" :class="{ selected: form.role === 'courier' }">
                  <input
                    v-model="form.role"
                    type="radio"
                    value="courier"
                  />
                  <div class="role-content">
                    <span class="role-icon">🏍️</span>
                    <div class="role-info">
                      <strong>Mensajero</strong>
                      <small>Gestión de entregas y tracking</small>
                    </div>
                  </div>
                </label>

                <label class="role-option" :class="{ selected: form.role === 'user' }">
                  <input
                    v-model="form.role"
                    type="radio"
                    value="user"
                  />
                  <div class="role-content">
                    <span class="role-icon">👤</span>
                    <div class="role-info">
                      <strong>Usuario Regular</strong>
                      <small>Acceso básico al sistema</small>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <div v-if="form.role === 'courier'" class="form-section">
            <h4>Información del Mensajero</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Teléfono *</label>
                <input v-model="form.phone" type="tel"
                       placeholder="+57 300 123 4567" />
              </div>
              <div class="form-group">
                <label>Tipo de vehículo *</label>
                <select v-model="form.vehicle_type">
                  <option value="">Seleccionar tipo...</option>
                  <option value="motocicleta">🏍️ Motocicleta</option>
                  <option value="bicicleta">🚲 Bicicleta</option>
                  <option value="carro">🚗 Carro</option>
                  <option value="caminando">🚶 Caminando</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Placa (opcional)</label>
              <input v-model="form.vehicle_plate"
                     placeholder="Ej: ABC123" />
            </div>
          </div>

          <div class="form-errors" v-if="Object.keys(formErrors).length > 0">
            <h4>Errores en el formulario:</h4>
            <ul>
              <li v-for="(error, field) in formErrors" :key="field">
                <strong>{{ field }}:</strong> {{ error[0] }}
              </li>
            </ul>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-secondary">
              Cancelar
            </button>
            <button type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Guardando...' : (editingUser ? 'Actualizar' : 'Crear Usuario') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Detalles del Usuario -->
    <div v-if="viewingUser" class="modal-overlay" @click.self="viewingUser = null">
      <div class="modal-content">
        <div class="modal-header">
          <h3>👤 Usuario #{{ viewingUser.id }}</h3>
          <button @click="viewingUser = null" class="btn-close">✕</button>
        </div>

        <div class="user-details-modal">
          <div class="user-avatar-large">
            {{ getUserInitials(viewingUser.name) }}
          </div>

          <div class="detail-section">
            <h4>Información Personal</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Nombre:</label>
                <span>{{ viewingUser.name }}</span>
              </div>
              <div class="detail-item">
                <label>Email:</label>
                <span>{{ viewingUser.email }}</span>
              </div>
              <div class="detail-item">
                <label>Rol:</label>
                <span :class="`role-badge ${viewingUser.role}`">
                  <span class="role-icon">{{ getRoleIcon(viewingUser.role) }}</span>
                  {{ viewingUser.role_display }}
                </span>
              </div>
              <div class="detail-item">
                <label>Estado:</label>
                <span class="status-indicator">
                  <span class="status-dot" :class="viewingUser.is_active ? 'active' : 'inactive'"></span>
                  {{ viewingUser.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </div>
            </div>
          </div>

          <div v-if="viewingUser.courier" class="detail-section">
            <h4>Información del Mensajero</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Teléfono:</label>
                <span>{{ viewingUser.courier.phone }}</span>
              </div>
              <div class="detail-item">
                <label>Vehículo:</label>
                <span>
                  <span class="vehicle-icon">{{ getVehicleIcon(viewingUser.courier.vehicle_type) }}</span>
                  {{ getVehicleName(viewingUser.courier.vehicle_type) }}
                </span>
              </div>
              <div class="detail-item" v-if="viewingUser.courier.vehicle_plate">
                <label>Placa:</label>
                <span>{{ viewingUser.courier.vehicle_plate }}</span>
              </div>
              <div class="detail-item">
                <label>Estado:</label>
                <span :class="`status-badge ${viewingUser.courier.status}`">
                  {{ viewingUser.courier.status }}
                </span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Información de Registro</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Fecha de registro:</label>
                <span>{{ formatDate(viewingUser.created_at) }}</span>
              </div>
              <div class="detail-item" v-if="viewingUser.last_login">
                <label>Último acceso:</label>
                <span>{{ formatDateTime(viewingUser.last_login) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button @click="viewingUser = null" class="btn-secondary">Cerrar</button>
          <button @click="editUser(viewingUser)" class="btn-primary">Editar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import apiClient from "@/api/axios";

// Estado reactivo
const users = ref([]);
const showCreateModal = ref(false);
const editingUser = ref(null);
const viewingUser = ref(null);
const saving = ref(false);
const formErrors = ref({});

// Filtros
const filters = ref({
  role: '',
  search: ''
});

// Estadísticas
const stats = ref({
  total: 0,
  admins: 0,
  couriers: 0,
  regular: 0
});

// Formulario
const form = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  role: "user",
  phone: "",
  vehicle_type: "",
  vehicle_plate: ""
});

// Computed
const filteredUsers = computed(() => {
  return users.value.filter(user => {
    if (filters.value.role && user.role !== filters.value.role) return false;
    if (filters.value.search) {
      const searchTerm = filters.value.search.toLowerCase();
      return user.name.toLowerCase().includes(searchTerm) ||
             user.email.toLowerCase().includes(searchTerm);
    }
    return true;
  });
});

// Funciones de utilidad
const getRoleIcon = (role) => {
  const icons = {
    admin: "👑",
    courier: "🏍️",
    user: "👤"
  };
  return icons[role] || "👤";
};

const getVehicleIcon = (type) => {
  const icons = {
    motocicleta: "🏍️",
    bicicleta: "🚲",
    carro: "🚗",
    caminando: "🚶"
  };
  return icons[type] || "🚗";
};

const getVehicleName = (type) => {
  const names = {
    motocicleta: "Motocicleta",
    bicicleta: "Bicicleta",
    carro: "Carro",
    caminando: "Caminando"
  };
  return names[type] || type;
};

const getUserInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('es-CO');
};

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('es-CO');
};

const formatTimeAgo = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffMins < 1) return 'Ahora';
  if (diffMins < 60) return `Hace ${diffMins} min`;
  if (diffHours < 24) return `Hace ${diffHours} h`;
  if (diffDays === 1) return 'Ayer';
  return `Hace ${diffDays} días`;
};

// Debounced search
let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadUsers();
  }, 500);
};

// Funciones de datos
async function loadUsers() {
  try {
    const params = {};
    if (filters.value.role) params.role = filters.value.role;
    if (filters.value.search) params.search = filters.value.search;

    const { data } = await apiClient.get("/users", { params });
    users.value = data;
    updateStats();
  } catch (err) {
    console.error("Error cargando usuarios:", err);
    alert("Error al cargar usuarios");
  }
}

function updateStats() {
  stats.value.total = users.value.length;
  stats.value.admins = users.value.filter(u => u.role === 'admin').length;
  stats.value.couriers = users.value.filter(u => u.role === 'courier').length;
  stats.value.regular = users.value.filter(u => u.role === 'user').length;
}

// Funciones de negocio
function openCreateModal() {
  editingUser.value = null;
  showCreateModal.value = true;
  resetForm();
}

function editUser(user) {
  editingUser.value = user;
  form.value = {
    name: user.name,
    email: user.email,
    role: user.role,
    phone: user.courier?.phone || "",
    vehicle_type: user.courier?.vehicle_type || "",
    vehicle_plate: user.courier?.vehicle_plate || ""
  };
  showCreateModal.value = true;
}

function viewUser(user) {
  viewingUser.value = user;
}

async function saveUser() {
  saving.value = true;
  formErrors.value = {};

  try {
    if (editingUser.value) {
      await apiClient.put(`/users/${editingUser.value.id}`, form.value);
    } else {
      await apiClient.post("/users", form.value);
    }

    await loadUsers();
    closeModal();

    alert(editingUser.value ?
      "✅ Usuario actualizado exitosamente" :
      "✅ Usuario creado exitosamente"
    );
  } catch (err) {
    console.error("Error guardando usuario:", err);
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors;
    } else {
      alert(err.response?.data?.message || "Error al guardar usuario");
    }
  } finally {
    saving.value = false;
  }
}

async function toggleActive(user) {
  const newStatus = !user.is_active;
  const action = newStatus ? 'activar' : 'desactivar';

  if (!confirm(`¿Estás seguro de ${action} al usuario ${user.name}?`)) return;

  try {
    await apiClient.put(`/users/${user.id}`, {
      is_active: newStatus
    });

    await loadUsers();
    alert(`✅ Usuario ${action}do exitosamente`);
  } catch (err) {
    console.error("Error cambiando estado:", err);
    alert("Error al cambiar estado del usuario");
  }
}

async function convertToCourier(user) {
  if (!confirm(`¿Convertir a ${user.name} en mensajero?`)) return;

  try {
    await apiClient.put(`/users/${user.id}`, {
      role: 'courier'
    });

    await loadUsers();
    alert("✅ Usuario convertido a mensajero exitosamente");
  } catch (err) {
    console.error("Error convirtiendo usuario:", err);
    alert("Error al convertir usuario a mensajero");
  }
}

function resetForm() {
  form.value = {
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "user",
    phone: "",
    vehicle_type: "",
    vehicle_plate: ""
  };
  formErrors.value = {};
}

function closeModal() {
  showCreateModal.value = false;
  editingUser.value = null;
  resetForm();
}

function exportToCSV() {
  const headers = ['ID', 'Nombre', 'Email', 'Rol', 'Estado', 'Teléfono', 'Vehículo', 'Placa', 'Fecha Registro'];
  const csvData = users.value.map(user => [
    user.id,
    user.name,
    user.email,
    user.role_display,
    user.is_active ? 'Activo' : 'Inactivo',
    user.courier?.phone || 'N/A',
    user.courier ? getVehicleName(user.courier.vehicle_type) : 'N/A',
    user.courier?.vehicle_plate || 'N/A',
    formatDate(user.created_at)
  ]);

  const csvContent = [headers, ...csvData]
    .map(row => row.map(field => `"${field}"`).join(','))
    .join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `usuarios_${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Inicialización
onMounted(() => {
  loadUsers();
});
</script>

<style scoped>
.management-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1rem;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
}

.header-content h2 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 1.75rem;
}

.subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 1rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-icon {
  font-size: 1.125rem;
}

/* Estadísticas */
.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  border-left: 4px solid #3b82f6;
}

.stat-icon {
  font-size: 2rem;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: bold;
  color: #1f2937;
}

.stat-label {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Filtros */
.filters {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.filters select, .filters input {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: 1px solid #d1d5db;
  background: white;
  min-width: 200px;
}

/* Tabla */
.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.table-header h3 {
  margin: 0;
  color: #1f2937;
}

.table-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}

th {
  background: #f9fafb;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

tr:hover {
  background: #f9fafb;
}

tr.inactive {
  background: #fef2f2;
  opacity: 0.7;
}

tr.inactive:hover {
  background: #fee2e2;
}

/* Celdas específicas */
.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
}

.user-details strong {
  display: block;
  margin-bottom: 0.25rem;
}

.user-email {
  font-size: 0.875rem;
  color: #6b7280;
}

.role-cell {
  text-align: center;
}

.role-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.role-badge.admin {
  background: #fef3c7;
  color: #92400e;
}

.role-badge.courier {
  background: #dbeafe;
  color: #1e40af;
}

.role-badge.user {
  background: #f3f4f6;
  color: #374151;
}

.role-icon {
  font-size: 1rem;
}

.courier-info {
  margin-top: 0.25rem;
}

.additional-info {
  max-width: 200px;
}

.courier-details .vehicle-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.vehicle-icon {
  font-size: 1.125rem;
}

.vehicle-plate {
  font-size: 0.875rem;
  color: #6b7280;
}

.no-additional-info {
  color: #9ca3af;
  font-style: italic;
}

.status-cell {
  text-align: center;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: center;
  margin-bottom: 0.25rem;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-dot.active {
  background: #22c55e;
}

.status-dot.inactive {
  background: #ef4444;
}

.status-badge.mini {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.status-badge.available {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.busy {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.offline {
  background: #f3f4f6;
  color: #6b7280;
}

.date-info {
  text-align: center;
}

.register-date {
  margin-bottom: 0.25rem;
}

.last-login {
  font-size: 0.75rem;
  color: #9ca3af;
}

.actions {
  text-align: center;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.btn-icon {
  padding: 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1.125rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: #f3f4f6;
  transform: scale(1.1);
}

.btn-icon:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-warning:hover {
  background: #fef3c7;
}

.btn-success:hover {
  background: #d1fae5;
}

.btn-info:hover {
  background: #e0f2fe;
}

.empty-state {
  padding: 3rem;
  text-align: center;
  color: #6b7280;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #374151;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-medium {
  max-width: 700px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  color: #1f2937;
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0.25rem;
}

.btn-close:hover {
  color: #374151;
}

/* Form Styles */
.user-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
}

.form-section h4 {
  margin: 0 0 1rem 0;
  color: #374151;
  font-size: 1.125rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group input:readonly {
  background: #f9fafb;
  color: #6b7280;
}

/* Role Options */
.role-options {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.role-option {
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.role-option:hover {
  border-color: #d1d5db;
}

.role-option.selected {
  border-color: #3b82f6;
  background: #f0f9ff;
}

.role-option input[type="radio"] {
  display: none;
}

.role-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.role-icon {
  font-size: 1.5rem;
}

.role-info strong {
  display: block;
  margin-bottom: 0.25rem;
  color: #374151;
}

.role-info small {
  color: #6b7280;
  font-size: 0.875rem;
}

/* User Details Modal */
.user-details-modal {
  margin-bottom: 1.5rem;
}

.user-avatar-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.5rem;
  margin: 0 auto 2rem auto;
}

.detail-section {
  margin-bottom: 1.5rem;
}

.detail-section h4 {
  margin: 0 0 0.75rem 0;
  color: #374151;
  font-size: 1.125rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.detail-item span {
  color: #4b5563;
}

.form-errors {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  padding: 1rem;
  color: #991b1b;
}

.form-errors h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
}

.form-errors ul {
  margin: 0;
  padding-left: 1.5rem;
}

.form-errors li {
  margin-bottom: 0.25rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.text-muted {
  color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .filters {
    flex-direction: column;
    align-items: stretch;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .table-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .table-actions {
    justify-content: stretch;
  }

  .table-actions button {
    flex: 1;
  }

  .modal-actions {
    flex-direction: column;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .role-options {
    gap: 0.25rem;
  }

  .role-content {
    gap: 0.5rem;
  }

  .role-icon {
    font-size: 1.25rem;
  }
}
</style>
