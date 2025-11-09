<!-- frontend/src/views/MapView.vue -->
<template>
  <div class="map-dashboard">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1>🗺️ Mapa de Mensajeros</h1>
        <p class="subtitle">Monitorea la ubicación en tiempo real de todos los mensajeros</p>
      </div>

      <div class="header-stats">
        <div class="stat-card">
          <div class="stat-icon">🏍️</div>
          <div class="stat-info">
            <div class="stat-value">{{ couriersStats.total }}</div>
            <div class="stat-label">Total</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">🟢</div>
          <div class="stat-info">
            <div class="stat-value">{{ couriersStats.online }}</div>
            <div class="stat-label">En Línea</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">📦</div>
          <div class="stat-info">
            <div class="stat-value">{{ couriersStats.activeDeliveries }}</div>
            <div class="stat-label">Entregas Activas</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Controles del Mapa -->
    <div class="map-controls">
      <div class="controls-left">
        <div class="filter-group">
          <label>Filtrar por estado:</label>
          <select v-model="filters.status" @change="applyFilters">
            <option value="all">Todos los estados</option>
            <option value="available">Disponibles</option>
            <option value="busy">Ocupados</option>
            <option value="offline">Desconectados</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Vehículo:</label>
          <select v-model="filters.vehicle" @change="applyFilters">
            <option value="all">Todos los vehículos</option>
            <option value="motocicleta">Motocicleta</option>
            <option value="bicicleta">Bicicleta</option>
            <option value="carro">Carro</option>
            <option value="caminando">Caminando</option>
          </select>
        </div>
      </div>

      <div class="controls-right">
        <button @click="toggleAutoRefresh" :class="`btn ${autoRefresh ? 'btn-warning' : 'btn-primary'}`">
          <span class="btn-icon">{{ autoRefresh ? '⏸️' : '🔄' }}</span>
          {{ autoRefresh ? `Actualizando (${refreshInterval}s)` : 'Actualizar Automático' }}
        </button>

        <button @click="refreshData" class="btn btn-secondary">
          <span class="btn-icon">🔍</span>
          Actualizar Ahora
        </button>

        <button @click="fitToBounds" class="btn btn-secondary">
          <span class="btn-icon">🎯</span>
          Ver Todos
        </button>
      </div>
    </div>

    <!-- Mapa y Panel Lateral -->
    <div class="map-layout">
      <!-- Mapa Principal -->
      <div class="map-container">
        <div id="map" class="main-map"></div>

        <!-- Leyenda del Mapa -->
        <div class="map-legend">
          <h4>Leyenda:</h4>
          <div class="legend-items">
            <div class="legend-item">
              <span class="legend-color available"></span>
              <span>Disponible</span>
            </div>
            <div class="legend-item">
              <span class="legend-color busy"></span>
              <span>Ocupado</span>
            </div>
            <div class="legend-item">
              <span class="legend-color offline"></span>
              <span>Desconectado</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de Mensajeros -->
      <div class="couriers-panel">
        <div class="panel-header">
          <h3>Mensajeros ({{ filteredCouriers.length }})</h3>
          <button @click="togglePanel" class="btn-icon">
            {{ panelCollapsed ? '📋' : '➡️' }}
          </button>
        </div>

        <div v-if="!panelCollapsed" class="panel-content">
          <div v-if="filteredCouriers.length === 0" class="empty-panel">
            <p>No hay mensajeros que coincidan con los filtros</p>
          </div>

          <div v-else class="couriers-list">
            <div
              v-for="courier in filteredCouriers"
              :key="courier.id"
              class="courier-card"
              :class="{ active: selectedCourier?.id === courier.id }"
              @click="focusOnCourier(courier)"
            >
              <div class="courier-avatar">
                {{ getInitials(courier.user.name) }}
              </div>

              <div class="courier-info">
                <div class="courier-name">
                  <strong>{{ courier.user.name }}</strong>
                  <span :class="`status-badge ${courier.status}`">
                    {{ getStatusText(courier.status) }}
                  </span>
                </div>

                <div class="courier-details">
                  <div class="detail">
                    <span class="detail-icon">{{ getVehicleIcon(courier.vehicle_type) }}</span>
                    {{ getVehicleName(courier.vehicle_type) }}
                  </div>

                  <div class="detail" v-if="courier.vehicle_plate">
                    <span class="detail-icon">🪪</span>
                    {{ courier.vehicle_plate }}
                  </div>

                  <div class="detail">
                    <span class="detail-icon">📦</span>
                    {{ courier.active_deliveries_count }} entregas activas
                  </div>

                  <div class="detail" v-if="courier.last_location_update">
                    <span class="detail-icon">🕒</span>
                    {{ formatTimeAgo(courier.last_location_update) }}
                  </div>
                </div>
              </div>

              <div class="courier-actions">
                <button @click.stop="centerOnCourier(courier)" class="btn-icon" title="Centrar en mapa">
                  🎯
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Cargando ubicaciones de mensajeros...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import apiClient from "@/api/axios";

// Estado reactivo
const map = ref(null);
const couriers = ref([]);
const markers = ref({});
const selectedCourier = ref(null);
const loading = ref(false);
const autoRefresh = ref(true);
const panelCollapsed = ref(false);
const refreshInterval = ref(30); // segundos

// Filtros
const filters = ref({
  status: 'all',
  vehicle: 'all'
});

// Estadísticas
const couriersStats = ref({
  total: 0,
  online: 0,
  activeDeliveries: 0
});

// Iconos personalizados para diferentes estados y vehículos
const courierIcons = {
  available: {
    motocicleta: L.divIcon({
      className: 'courier-marker available motorcycle',
      html: '<div style="background: #22c55e; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🏍️</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    bicicleta: L.divIcon({
      className: 'courier-marker available bicycle',
      html: '<div style="background: #22c55e; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚲</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    carro: L.divIcon({
      className: 'courier-marker available car',
      html: '<div style="background: #22c55e; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚗</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    caminando: L.divIcon({
      className: 'courier-marker available walking',
      html: '<div style="background: #22c55e; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚶</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    })
  },
  busy: {
    motocicleta: L.divIcon({
      className: 'courier-marker busy motorcycle',
      html: '<div style="background: #f59e0b; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🏍️</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    bicicleta: L.divIcon({
      className: 'courier-marker busy bicycle',
      html: '<div style="background: #f59e0b; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚲</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    carro: L.divIcon({
      className: 'courier-marker busy car',
      html: '<div style="background: #f59e0b; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚗</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    caminando: L.divIcon({
      className: 'courier-marker busy walking',
      html: '<div style="background: #f59e0b; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚶</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    })
  },
  offline: {
    motocicleta: L.divIcon({
      className: 'courier-marker offline motorcycle',
      html: '<div style="background: #6b7280; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🏍️</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    bicicleta: L.divIcon({
      className: 'courier-marker offline bicycle',
      html: '<div style="background: #6b7280; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚲</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    carro: L.divIcon({
      className: 'courier-marker offline car',
      html: '<div style="background: #6b7280; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚗</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    }),
    caminando: L.divIcon({
      className: 'courier-marker offline walking',
      html: '<div style="background: #6b7280; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🚶</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    })
  }
};

// Computed
const filteredCouriers = computed(() => {
  return couriers.value.filter(courier => {
    if (filters.value.status !== 'all' && courier.status !== filters.value.status) return false;
    if (filters.value.vehicle !== 'all' && courier.vehicle_type !== filters.value.vehicle) return false;
    return true;
  });
});

// Funciones de utilidad
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const getStatusText = (status) => {
  const texts = {
    available: 'Disponible',
    busy: 'Ocupado',
    offline: 'Desconectado'
  };
  return texts[status] || status;
};

const getVehicleIcon = (type) => {
  const icons = {
    motocicleta: '🏍️',
    bicicleta: '🚲',
    carro: '🚗',
    caminando: '🚶'
  };
  return icons[type] || '🚗';
};

const getVehicleName = (type) => {
  const names = {
    motocicleta: 'Motocicleta',
    bicicleta: 'Bicicleta',
    carro: 'Carro',
    caminando: 'Caminando'
  };
  return names[type] || type;
};

const formatTimeAgo = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);

  if (diffMins < 1) return 'Ahora';
  if (diffMins < 60) return `Hace ${diffMins} min`;
  if (diffHours < 24) return `Hace ${diffHours} h`;
  return `Hace ${Math.floor(diffHours / 24)} días`;
};

// Funciones del mapa
async function loadCouriers() {
  loading.value = true;

  try {
    const { data } = await apiClient.get("/couriers", {
      params: {
        with_location: true,
        with_user: true,
        with_active_deliveries: true
      }
    });

    couriers.value = data;
    updateStats();
    updateMapMarkers();

  } catch (error) {
    console.error("Error cargando mensajeros:", error);
  } finally {
    loading.value = false;
  }
}

function updateStats() {
  couriersStats.value.total = couriers.value.length;
  couriersStats.value.online = couriers.value.filter(c =>
    c.status !== 'offline' && c.is_active
  ).length;
  couriersStats.value.activeDeliveries = couriers.value.reduce(
    (sum, courier) => sum + (courier.active_deliveries_count || 0), 0
  );
}

function updateMapMarkers() {
  // Limpiar marcadores antiguos
  Object.values(markers.value).forEach(marker => {
    if (marker) map.value.removeLayer(marker);
  });
  markers.value = {};

  // Agregar nuevos marcadores
  filteredCouriers.value.forEach(courier => {
    if (!courier.current_location) return;

    const icon = courierIcons[courier.status]?.[courier.vehicle_type] || courierIcons.offline.motocicleta;

    const marker = L.marker([
      courier.current_location.lat,
      courier.current_location.lng
    ], { icon }).addTo(map.value);

    // Popup informativo
    const popupContent = `
      <div class="courier-popup">
        <h4>${courier.user.name}</h4>
        <div class="popup-details">
          <p><strong>Estado:</strong> <span class="status-${courier.status}">${getStatusText(courier.status)}</span></p>
          <p><strong>Vehículo:</strong> ${getVehicleName(courier.vehicle_type)}</p>
          <p><strong>Teléfono:</strong> ${courier.phone}</p>
          <p><strong>Entregas activas:</strong> ${courier.active_deliveries_count || 0}</p>
          <p><strong>Última actualización:</strong> ${formatTimeAgo(courier.last_location_update)}</p>
        </div>
      </div>
    `;

    marker.bindPopup(popupContent);

    // Evento click en el marcador
    marker.on('click', () => {
      selectedCourier.value = courier;
    });

    markers.value[courier.id] = marker;
  });
}

function focusOnCourier(courier) {
  selectedCourier.value = courier;
  centerOnCourier(courier);
}

function centerOnCourier(courier) {
  if (!courier.current_location) return;

  map.value.setView([
    courier.current_location.lat,
    courier.current_location.lng
  ], 15);

  // Abrir popup del marcador
  if (markers.value[courier.id]) {
    markers.value[courier.id].openPopup();
  }
}

function fitToBounds() {
  const bounds = L.latLngBounds();

  filteredCouriers.value.forEach(courier => {
    if (courier.current_location) {
      bounds.extend([courier.current_location.lat, courier.current_location.lng]);
    }
  });

  if (bounds.isValid()) {
    map.value.fitBounds(bounds, { padding: [20, 20] });
  }
}

function applyFilters() {
  updateMapMarkers();
}

function toggleAutoRefresh() {
  autoRefresh.value = !autoRefresh.value;
}

function refreshData() {
  loadCouriers();
}

function togglePanel() {
  panelCollapsed.value = !panelCollapsed.value;
}

// Intervalo de actualización automática
let refreshTimer = null;

function startAutoRefresh() {
  if (refreshTimer) clearInterval(refreshTimer);

  if (autoRefresh.value) {
    refreshTimer = setInterval(() => {
      refreshData();
    }, refreshInterval.value * 1000);
  }
}

// Inicialización
onMounted(async () => {
  // Inicializar mapa
  map.value = L.map("map", {
    center: [10.9878, -74.7889], // Barranquilla
    zoom: 13,
    zoomControl: true,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
    maxZoom: 19,
  }).addTo(map.value);

  // Ajustar tamaño del mapa
  setTimeout(() => {
    map.value.invalidateSize();
  }, 100);

  // Cargar datos iniciales
  await loadCouriers();
  startAutoRefresh();
});

onUnmounted(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer);
  }
});
</script>

<style scoped>
.map-dashboard {
  width: 100%;
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
}

/* Header */
.dashboard-header {
  background: white;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h1 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 1.75rem;
}

.subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 1rem;
}

.header-stats {
  display: flex;
  gap: 1rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
}

.stat-icon {
  font-size: 1.5rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #1f2937;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Controles */
.map-controls {
  background: white;
  padding: 1rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.controls-left {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.filter-group select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  min-width: 150px;
}

.controls-right {
  display: flex;
  gap: 0.5rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover {
  background: #d97706;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
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

.btn-icon:hover {
  background: #f3f4f6;
  transform: scale(1.1);
}

/* Layout Principal */
.map-layout {
  display: grid;
  grid-template-columns: 1fr 350px;
  flex: 1;
  overflow: hidden;
}

.map-container {
  position: relative;
  display: flex;
  flex-direction: column;
}

.main-map {
  flex: 1;
  width: 100%;
  height: 100%;
}

/* Leyenda del Mapa */
.map-legend {
  position: absolute;
  bottom: 1rem;
  left: 1rem;
  background: white;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}

.map-legend h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  color: #374151;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.legend-color.available {
  background: #22c55e;
}

.legend-color.busy {
  background: #f59e0b;
}

.legend-color.offline {
  background: #6b7280;
}

/* Panel de Mensajeros */
.couriers-panel {
  background: white;
  border-left: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.panel-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.panel-header h3 {
  margin: 0;
  color: #1f2937;
  font-size: 1.125rem;
}

.panel-content {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}

.empty-panel {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
}

.couriers-list {
  padding: 0;
}

.courier-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: all 0.2s;
}

.courier-card:hover {
  background: #f9fafb;
}

.courier-card.active {
  background: #f0f9ff;
  border-left: 3px solid #3b82f6;
}

.courier-avatar {
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
  flex-shrink: 0;
}

.courier-info {
  flex: 1;
  min-width: 0;
}

.courier-name {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.courier-name strong {
  color: #1f2937;
  font-size: 0.875rem;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
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

.courier-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.detail-icon {
  font-size: 0.875rem;
  width: 16px;
  text-align: center;
}

.courier-actions {
  display: flex;
  align-items: center;
}

/* Loading State */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  z-index: 2000;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Popup Styles */
:deep(.courier-popup) {
  font-family: inherit;
  min-width: 250px;
}

:deep(.courier-popup h4) {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
}

:deep(.popup-details) {
  font-size: 0.875rem;
}

:deep(.popup-details p) {
  margin: 0.25rem 0;
}

:deep(.status-available) {
  color: #065f46;
  font-weight: 600;
}

:deep(.status-busy) {
  color: #92400e;
  font-weight: 600;
}

:deep(.status-offline) {
  color: #6b7280;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 1024px) {
  .map-layout {
    grid-template-columns: 1fr 300px;
  }

  .header-stats {
    gap: 0.5rem;
  }

  .stat-card {
    padding: 0.75rem 1rem;
  }
}

@media (max-width: 768px) {
  .map-layout {
    grid-template-columns: 1fr;
  }

  .couriers-panel {
    position: fixed;
    top: 0;
    right: -100%;
    width: 100%;
    max-width: 400px;
    height: 100%;
    z-index: 1000;
    transition: right 0.3s ease;
  }

  .couriers-panel:not(.collapsed) {
    right: 0;
  }

  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .header-stats {
    justify-content: space-between;
  }

  .map-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .controls-left, .controls-right {
    flex-wrap: wrap;
  }
}

@media (max-width: 640px) {
  .dashboard-header, .map-controls {
    padding: 1rem;
  }

  .header-content h1 {
    font-size: 1.5rem;
  }

  .stat-card {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
    padding: 0.75rem;
  }

  .stat-value {
    font-size: 1.25rem;
  }
}
</style>
