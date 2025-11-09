<!-- frontend/src/views/CourierManagement.vue -->
<template>
  <div class="management-wrapper">
    <div class="header-section">
      <div class="header-content">
        <h2>🏍️ Gestión de Mensajeros</h2>
        <p class="subtitle">Administra los mensajeros y su disponibilidad</p>
      </div>
      <button @click="openCreateModal" class="btn-primary">
        <span class="btn-icon">➕</span>
        Nuevo Mensajero
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
        <div class="stat-icon">🟢</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.available }}</div>
          <div class="stat-label">Disponibles</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🟡</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.busy }}</div>
          <div class="stat-label">Ocupados</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔴</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.offline }}</div>
          <div class="stat-label">Desconectados</div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filters">
      <div class="filter-group">
        <label>Estado:</label>
        <select v-model="filters.status" @change="loadCouriers">
          <option value="">Todos los estados</option>
          <option value="available">Disponible</option>
          <option value="busy">Ocupado</option>
          <option value="offline">Desconectado</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Activos:</label>
        <select v-model="filters.active" @change="loadCouriers">
          <option value="">Todos</option>
          <option value="true">Solo activos</option>
          <option value="false">Solo inactivos</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Vehículo:</label>
        <select v-model="filters.vehicle_type" @change="loadCouriers">
          <option value="">Todos</option>
          <option value="motocicleta">Motocicleta</option>
          <option value="bicicleta">Bicicleta</option>
          <option value="carro">Carro</option>
          <option value="caminando">Caminando</option>
        </select>
      </div>
    </div>

    <!-- Tabla de mensajeros -->
    <div class="table-container">
      <div class="table-header">
        <h3>Lista de Mensajeros ({{ couriers.length }})</h3>
        <div class="table-actions">
          <button @click="loadCouriers" class="btn-secondary">
            <span class="btn-icon">🔄</span>
            Actualizar
          </button>
          <button @click="exportToCSV" class="btn-secondary" :disabled="couriers.length === 0">
            <span class="btn-icon">📊</span>
            Exportar CSV
          </button>
        </div>
      </div>

      <table v-if="couriers.length > 0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Mensajero</th>
            <th>Contacto</th>
            <th>Vehículo</th>
            <th>Estado</th>
            <th>Carga</th>
            <th>Rendimiento</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="courier in couriers" :key="courier.id"
              :class="{ 'inactive': !courier.is_active }">
            <td class="courier-id">#{{ courier.id }}</td>
            <td class="courier-info">
              <div class="courier-name">
                <strong>{{ courier.name }}</strong>
                <span v-if="!courier.is_active" class="inactive-badge">Inactivo</span>
              </div>
              <div class="courier-email">{{ courier.email }}</div>
            </td>
            <td class="contact-info">
              <div class="phone">📞 {{ courier.phone }}</div>
              <div v-if="courier.last_location_update" class="last-update">
                📍 {{ formatTimeAgo(courier.last_location_update) }}
              </div>
            </td>
            <td class="vehicle-info">
              <div class="vehicle-type">
                <span class="vehicle-icon">{{ getVehicleIcon(courier.vehicle_type) }}</span>
                {{ getVehicleName(courier.vehicle_type) }}
              </div>
              <div v-if="courier.vehicle_plate" class="vehicle-plate">
                🪪 {{ courier.vehicle_plate }}
              </div>
            </td>
            <td class="status-cell">
              <span :class="`status-badge ${courier.status}`">
                {{ statusText(courier.status) }}
              </span>
            </td>
            <td class="load-info">
              <div class="load-bar">
                <div class="load-fill" :style="{ width: `${(courier.current_load / courier.max_capacity) * 100}%` }"></div>
                <span class="load-text">{{ courier.current_load }}/{{ courier.max_capacity }}</span>
              </div>
            </td>
            <td class="performance-info">
              <div class="performance-stats">
                <div class="stat">
                  <span class="stat-value">{{ courier.performance.completed_today }}</span>
                  <span class="stat-label">hoy</span>
                </div>
                <div class="stat">
                  <span class="stat-value">{{ courier.performance.completion_rate }}%</span>
                  <span class="stat-label">éxito</span>
                </div>
              </div>
            </td>
            <td class="actions">
              <div class="action-buttons">
                <button @click="viewCourier(courier)" class="btn-icon" title="Ver detalles">
                  👁️
                </button>
                <button @click="editCourier(courier)" class="btn-icon" title="Editar">
                  ✏️
                </button>
                <button @click="toggleActive(courier)"
                        :class="`btn-icon ${courier.is_active ? 'btn-warning' : 'btn-success'}`"
                        :title="courier.is_active ? 'Desactivar' : 'Activar'">
                  {{ courier.is_active ? '⏸️' : '▶️' }}
                </button>
                <button @click="showOnMap(courier)" class="btn-icon" title="Ver en mapa"
                        :disabled="!courier.last_location_update">
                  🗺️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <div class="empty-icon">🏍️</div>
        <h3>No hay mensajeros registrados</h3>
        <p>Comienza agregando tu primer mensajero al sistema</p>
        <button @click="openCreateModal" class="btn-primary">
          <span class="btn-icon">➕</span>
          Crear primer mensajero
        </button>
      </div>
    </div>

    <!-- Modal de Crear/Editar Mensajero -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content modal-medium">
        <div class="modal-header">
          <h3>{{ editingCourier ? '✏️ Editar Mensajero' : '➕ Nuevo Mensajero' }}</h3>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>

        <form @submit.prevent="saveCourier" class="courier-form">
          <div class="form-section">
            <h4>Información Personal</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Nombre completo *</label>
                <input v-model="form.name" type="text" required
                       placeholder="Ej: Carlos Rodríguez" />
              </div>
              <div class="form-group">
                <label>Email *</label>
                <input v-model="form.email" type="email" required
                       placeholder="ejemplo@correo.com"
                       :readonly="!!editingCourier" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Teléfono *</label>
                <input v-model="form.phone" type="tel" required
                       placeholder="+57 300 123 4567" />
              </div>
              <div class="form-group" v-if="!editingCourier">
                <label>Contraseña *</label>
                <input v-model="form.password" type="password" required
                       minlength="8" placeholder="Mínimo 8 caracteres" />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4>Información del Vehículo</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Tipo de vehículo *</label>
                <select v-model="form.vehicle_type" required>
                  <option value="">Seleccionar tipo...</option>
                  <option value="motocicleta">🏍️ Motocicleta</option>
                  <option value="bicicleta">🚲 Bicicleta</option>
                  <option value="carro">🚗 Carro</option>
                  <option value="caminando">🚶 Caminando</option>
                </select>
              </div>
              <div class="form-group">
                <label>Placa (opcional)</label>
                <input v-model="form.vehicle_plate"
                       placeholder="Ej: ABC123" />
              </div>
            </div>

            <div class="form-group">
              <label>Capacidad máxima de entregas *</label>
              <input v-model="form.max_capacity" type="number"
                     min="1" max="20" required
                     placeholder="Número máximo de entregas simultáneas" />
              <div class="help-text">
                Número máximo de entregas que puede manejar al mismo tiempo
              </div>
            </div>
          </div>

          <div v-if="editingCourier" class="form-section">
            <h4>Estado del Mensajero</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Estado actual</label>
                <select v-model="form.status">
                  <option value="available">🟢 Disponible</option>
                  <option value="busy">🟡 Ocupado</option>
                  <option value="offline">🔴 Desconectado</option>
                </select>
              </div>
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="form.is_active" />
                  <span class="checkmark"></span>
                  Mensajero activo
                </label>
                <div class="help-text">
                  Los mensajeros inactivos no recibirán nuevas entregas
                </div>
              </div>
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
              {{ saving ? 'Guardando...' : (editingCourier ? 'Actualizar' : 'Crear Mensajero') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Detalles del Mensajero -->
    <div v-if="viewingCourier" class="modal-overlay" @click.self="viewingCourier = null">
      <div class="modal-content">
        <div class="modal-header">
          <h3>👤 Mensajero #{{ viewingCourier.id }}</h3>
          <button @click="viewingCourier = null" class="btn-close">✕</button>
        </div>

        <div class="courier-details">
          <div class="detail-section">
            <h4>Información Personal</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Nombre:</label>
                <span>{{ viewingCourier.name }}</span>
              </div>
              <div class="detail-item">
                <label>Email:</label>
                <span>{{ viewingCourier.email }}</span>
              </div>
              <div class="detail-item">
                <label>Teléfono:</label>
                <span>{{ viewingCourier.phone }}</span>
              </div>
              <div class="detail-item">
                <label>Estado:</label>
                <span :class="`status-badge ${viewingCourier.status}`">
                  {{ statusText(viewingCourier.status) }}
                </span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Información del Vehículo</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Tipo:</label>
                <span>
                  <span class="vehicle-icon">{{ getVehicleIcon(viewingCourier.vehicle_type) }}</span>
                  {{ getVehicleName(viewingCourier.vehicle_type) }}
                </span>
              </div>
              <div class="detail-item" v-if="viewingCourier.vehicle_plate">
                <label>Placa:</label>
                <span>{{ viewingCourier.vehicle_plate }}</span>
              </div>
              <div class="detail-item">
                <label>Capacidad:</label>
                <span>{{ viewingCourier.current_load }}/{{ viewingCourier.max_capacity }} entregas</span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Estadísticas de Rendimiento</h4>
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-number">{{ viewingCourier.performance.total_deliveries }}</div>
                <div class="stat-label">Total Entregas</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">{{ viewingCourier.performance.completed_today }}</div>
                <div class="stat-label">Hoy</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">{{ viewingCourier.performance.active_deliveries }}</div>
                <div class="stat-label">Activas</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">{{ viewingCourier.performance.completion_rate }}%</div>
                <div class="stat-label">Tasa de Éxito</div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button @click="viewingCourier = null" class="btn-secondary">Cerrar</button>
          <button @click="editCourier(viewingCourier)" class="btn-primary">Editar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import apiClient from "@/api/axios";

// Estado reactivo
const couriers = ref([]);
const showCreateModal = ref(false);
const editingCourier = ref(null);
const viewingCourier = ref(null);
const saving = ref(false);
const formErrors = ref({});

// Filtros
const filters = ref({
  status: '',
  active: '',
  vehicle_type: ''
});

// Estadísticas
const stats = ref({
  total: 0,
  available: 0,
  busy: 0,
  offline: 0
});

// Formulario
const form = ref({
  name: "",
  email: "",
  password: "",
  phone: "",
  vehicle_type: "",
  vehicle_plate: "",
  max_capacity: 5,
  status: "offline",
  is_active: true
});

// Computed
const filteredCouriers = computed(() => {
  return couriers.value.filter(courier => {
    if (filters.value.status && courier.status !== filters.value.status) return false;
    if (filters.value.active !== '' && courier.is_active !== (filters.value.active === 'true')) return false;
    if (filters.value.vehicle_type && courier.vehicle_type !== filters.value.vehicle_type) return false;
    return true;
  });
});

// Funciones de utilidad
const statusText = (status) => {
  const texts = {
    available: "Disponible",
    busy: "Ocupado",
    offline: "Desconectado",
  };
  return texts[status] || status;
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

// Funciones de datos
async function loadCouriers() {
  try {
    const params = {};
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.active !== '') params.active = filters.value.active;
    if (filters.value.vehicle_type) params.vehicle_type = filters.value.vehicle_type;

    const { data } = await apiClient.get("/couriers", { params });
    couriers.value = data;
    updateStats();
  } catch (err) {
    console.error("Error cargando mensajeros:", err);
    alert("Error al cargar mensajeros");
  }
}

async function loadStats() {
  try {
    const { data } = await apiClient.get("/couriers/stats");
    stats.value = data;
  } catch (err) {
    console.error("Error cargando estadísticas:", err);
  }
}

function updateStats() {
  stats.value.total = couriers.value.length;
  stats.value.available = couriers.value.filter(c => c.status === 'available' && c.is_active).length;
  stats.value.busy = couriers.value.filter(c => c.status === 'busy' && c.is_active).length;
  stats.value.offline = couriers.value.filter(c => c.status === 'offline' && c.is_active).length;
}

// Funciones de negocio
function openCreateModal() {
  editingCourier.value = null;
  showCreateModal.value = true;
  resetForm();
}

function editCourier(courier) {
  editingCourier.value = courier;
  form.value = {
    name: courier.name,
    email: courier.email,
    phone: courier.phone,
    vehicle_type: courier.vehicle_type,
    vehicle_plate: courier.vehicle_plate || "",
    max_capacity: courier.max_capacity,
    status: courier.status,
    is_active: courier.is_active
  };
  showCreateModal.value = true;
}

function viewCourier(courier) {
  viewingCourier.value = courier;
}

async function saveCourier() {
  saving.value = true;
  formErrors.value = {};

  try {
    if (editingCourier.value) {
      await apiClient.put(`/couriers/${editingCourier.value.id}`, form.value);
    } else {
      await apiClient.post("/couriers", form.value);
    }

    await loadCouriers();
    await loadStats();
    closeModal();

    alert(editingCourier.value ?
      "✅ Mensajero actualizado exitosamente" :
      "✅ Mensajero creado exitosamente"
    );
  } catch (err) {
    console.error("Error guardando mensajero:", err);
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors;
    } else {
      alert(err.response?.data?.message || "Error al guardar mensajero");
    }
  } finally {
    saving.value = false;
  }
}

async function toggleActive(courier) {
  const newStatus = !courier.is_active;
  const action = newStatus ? 'activar' : 'desactivar';

  if (!confirm(`¿Estás seguro de ${action} al mensajero ${courier.name}?`)) return;

  try {
    await apiClient.put(`/couriers/${courier.id}`, {
      is_active: newStatus
    });

    await loadCouriers();
    await loadStats();
    alert(`✅ Mensajero ${action}do exitosamente`);
  } catch (err) {
    console.error("Error cambiando estado:", err);
    alert("Error al cambiar estado del mensajero");
  }
}

function showOnMap(courier) {
  // Implementar navegación al mapa con el mensajero seleccionado
  alert(`Mostrar mensajero ${courier.name} en el mapa`);
}

function resetForm() {
  form.value = {
    name: "",
    email: "",
    password: "",
    phone: "",
    vehicle_type: "",
    vehicle_plate: "",
    max_capacity: 5,
    status: "offline",
    is_active: true
  };
  formErrors.value = {};
}

function closeModal() {
  showCreateModal.value = false;
  editingCourier.value = null;
  resetForm();
}

function exportToCSV() {
  const headers = ['ID', 'Nombre', 'Email', 'Teléfono', 'Vehículo', 'Placa', 'Estado', 'Activo', 'Carga Actual', 'Capacidad Máxima'];
  const csvData = couriers.value.map(courier => [
    courier.id,
    courier.name,
    courier.email,
    courier.phone,
    getVehicleName(courier.vehicle_type),
    courier.vehicle_plate || 'N/A',
    statusText(courier.status),
    courier.is_active ? 'Sí' : 'No',
    courier.current_load,
    courier.max_capacity
  ]);

  const csvContent = [headers, ...csvData]
    .map(row => row.map(field => `"${field}"`).join(','))
    .join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `mensajeros_${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Inicialización
onMounted(() => {
  loadCouriers();
  loadStats();
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

.filters select {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: 1px solid #d1d5db;
  background: white;
  min-width: 150px;
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

.courier-id {
  font-weight: 600;
  color: #3b82f6;
}

.courier-info .courier-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.inactive-badge {
  background: #ef4444;
  color: white;
  padding: 0.125rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
}

.courier-email {
  font-size: 0.875rem;
  color: #6b7280;
}

.contact-info .phone {
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.last-update {
  font-size: 0.75rem;
  color: #9ca3af;
}

.vehicle-info .vehicle-type {
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

.status-cell {
  text-align: center;
}

.status-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-block;
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

.load-info {
  text-align: center;
}

.load-bar {
  position: relative;
  background: #f3f4f6;
  border-radius: 10px;
  height: 20px;
  overflow: hidden;
}

.load-fill {
  background: #3b82f6;
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 10px;
}

.load-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.75rem;
  font-weight: 600;
  color: #374151;
}

.performance-info {
  text-align: center;
}

.performance-stats {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.stat-label {
  font-size: 0.75rem;
  color: #6b7280;
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
.courier-form {
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

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
}

.help-text {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Courier Details */
.courier-details {
  margin-bottom: 1.5rem;
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: bold;
  color: #3b82f6;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
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

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
