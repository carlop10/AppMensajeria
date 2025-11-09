<!-- frontend/src/views/MapView.vue - VERSIÓN CON DEBUG -->
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
          <div class="stat-icon">📍</div>
          <div class="stat-info">
            <div class="stat-value">{{ couriersStats.withLocation }}</div>
            <div class="stat-label">Con Ubicación</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">🟢</div>
          <div class="stat-info">
            <div class="stat-value">{{ couriersStats.online }}</div>
            <div class="stat-label">En Línea</div>
          </div>
        </div>
      </div>

      <!-- Debug info -->
      <div class="debug-info" v-if="debugMode">
        <small>Con ubicación: {{ couriersStats.withLocation }}/{{ couriersStats.total }}</small>
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
        <button @click="toggleDebug" class="btn btn-secondary">
          <span class="btn-icon">🐛</span>
          {{ debugMode ? 'Ocultar Debug' : 'Mostrar Debug' }}
        </button>

        <button @click="toggleAutoRefresh" :class="`btn ${autoRefresh ? 'btn-warning' : 'btn-primary'}`">
          <span class="btn-icon">{{ autoRefresh ? '⏸️' : '🔄' }}</span>
          {{ autoRefresh ? `Actualizando (${refreshInterval}s)` : 'Actualizar Automático' }}
        </button>

        <button @click="refreshData" class="btn btn-secondary">
          <span class="btn-icon">🔍</span>
          Actualizar Ahora
        </button>

        <button @click="fitToBounds" class="btn btn-secondary" :disabled="!hasLocations">
          <span class="btn-icon">🎯</span>
          Ver Todos
        </button>
      </div>
    </div>

    <!-- Debug Panel -->
    <div v-if="debugMode" class="debug-panel">
      <h4>🔍 Información de Debug</h4>
      <div class="debug-content">
        <div class="debug-section">
          <strong>Mensajeros con ubicación:</strong>
          <div v-for="courier in couriersWithLocation" :key="courier.id" class="debug-item">
            {{ courier.name }}: {{ courier.current_location.lat }}, {{ courier.current_location.lng }}
          </div>
          <div v-if="couriersWithLocation.length === 0" class="debug-item">
            ❌ No hay mensajeros con ubicación
          </div>
        </div>

        <div class="debug-section">
          <strong>Mensajeros sin ubicación:</strong>
          <div v-for="courier in couriersWithoutLocation" :key="courier.id" class="debug-item">
            {{ courier.name }}: ❌ Sin ubicación
          </div>
        </div>
      </div>
    </div>

    <!-- Mapa y Panel Lateral -->
    <div class="map-layout">
      <!-- Mapa Principal -->
      <div class="map-container">
        <div id="map" class="main-map"></div>

        <!-- Estado del Mapa -->
        <div v-if="loading" class="map-loading">
          <div class="loading-spinner"></div>
          <p>Cargando ubicaciones de mensajeros...</p>
        </div>

        <div v-else-if="!hasLocations" class="map-empty">
          <div class="empty-icon">🏍️</div>
          <h3>No hay mensajeros con ubicación</h3>
          <p>Los mensajeros aparecerán aquí cuando actualicen su ubicación</p>
          <div class="empty-actions">
            <button @click="refreshData" class="btn btn-primary">
              <span class="btn-icon">🔄</span>
              Reintentar
            </button>
            <button @click="toggleDebug" class="btn btn-secondary">
              <span class="btn-icon">🐛</span>
              Ver Debug
            </button>
          </div>
        </div>

        <!-- Leyenda del Mapa -->
        <div v-if="hasLocations" class="map-legend">
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
          <span class="location-count" v-if="debugMode">
            📍 {{ couriersWithLocation.length }}
          </span>
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
              :class="{
                active: selectedCourier?.id === courier.id,
                'has-location': hasLocation(courier),
                'no-location': !hasLocation(courier)
              }"
              @click="focusOnCourier(courier)"
            >
              <div class="courier-avatar" :class="{ 'no-location': !hasLocation(courier) }">
                {{ getInitials(courier.name) }}
              </div>

              <div class="courier-info">
                <div class="courier-name">
                  <strong>{{ courier.name }}</strong>
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
                    {{ courier.current_load || 0 }} / {{ courier.max_capacity }} entregas
                  </div>

                  <div class="detail" v-if="courier.last_location_update">
                    <span class="detail-icon">🕒</span>
                    {{ formatTimeAgo(courier.last_location_update) }}
                  </div>

                  <div class="detail location-indicator" :class="{ 'has-location': hasLocation(courier) }">
                    <span class="detail-icon">📍</span>
                    {{ hasLocation(courier) ? 'Con ubicación' : 'Sin ubicación' }}
                  </div>
                </div>
              </div>

              <div class="courier-actions">
                <button
                  @click.stop="centerOnCourier(courier)"
                  class="btn-icon"
                  :title="hasLocation(courier) ? 'Centrar en mapa' : 'Sin ubicación'"
                  :disabled="!hasLocation(courier)"
                >
                  🎯
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
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
const debugMode = ref(true); // Iniciar en modo debug para ver el problema
const refreshInterval = ref(30);

// Filtros
const filters = ref({
  status: 'all',
  vehicle: 'all'
});

// Estadísticas
const couriersStats = ref({
  total: 0,
  withLocation: 0,
  online: 0
});

// Computed
const filteredCouriers = computed(() => {
  return couriers.value.filter(courier => {
    if (filters.value.status !== 'all' && courier.status !== filters.value.status) return false;
    if (filters.value.vehicle !== 'all' && courier.vehicle_type !== filters.value.vehicle) return false;
    return true;
  });
});

const hasLocations = computed(() => {
  return couriersStats.value.withLocation > 0;
});

const couriersWithLocation = computed(() => {
  return couriers.value.filter(courier => hasLocation(courier));
});

const couriersWithoutLocation = computed(() => {
  return couriers.value.filter(courier => !hasLocation(courier));
});

// Función para verificar si un mensajero tiene ubicación
const hasLocation = (courier) => {
  return courier.current_location &&
         courier.current_location.lat !== null &&
         courier.current_location.lng !== null &&
         courier.current_location.lat !== undefined &&
         courier.current_location.lng !== undefined;
};

// ... (el resto del código se mantiene igual, solo cambian las funciones de actualización)

async function loadCouriers() {
  loading.value = true;

  try {
    const { data } = await apiClient.get("/couriers");

    console.log('📦 Mensajeros cargados:', data);
    console.log('🔍 Estructura del primer mensajero:', data[0]);

    // Debug: verificar ubicaciones
    data.forEach(courier => {
      console.log(`📍 ${courier.name}:`, courier.current_location);
    });

    couriers.value = data;
    updateStats();
    updateMapMarkers();

  } catch (error) {
    console.error("❌ Error cargando mensajeros:", error);
  } finally {
    loading.value = false;
  }
}

function updateStats() {
  couriersStats.value.total = couriers.value.length;
  couriersStats.value.withLocation = couriers.value.filter(courier =>
    hasLocation(courier)
  ).length;
  couriersStats.value.online = couriers.value.filter(c =>
    c.status !== 'offline' && c.is_active !== false
  ).length;
}

function updateMapMarkers() {
  // Limpiar marcadores antiguos
  Object.values(markers.value).forEach(marker => {
    if (marker && map.value) {
      map.value.removeLayer(marker);
    }
  });
  markers.value = {};

  let markersAdded = 0;

  // Agregar nuevos marcadores para mensajeros con ubicación
  filteredCouriers.value.forEach(courier => {
    if (!hasLocation(courier)) {
      console.log(`❌ ${courier.name} sin ubicación`);
      return;
    }

    const icon = createCourierIcon(courier.status, courier.vehicle_type);

    try {
      const marker = L.marker([
        courier.current_location.lat,
        courier.current_location.lng
      ], { icon }).addTo(map.value);

      // Popup informativo
      const popupContent = `
        <div class="courier-popup">
          <h4>${courier.name}</h4>
          <div class="popup-details">
            <p><strong>Estado:</strong> <span class="status-${courier.status}">${getStatusText(courier.status)}</span></p>
            <p><strong>Vehículo:</strong> ${getVehicleName(courier.vehicle_type)}</p>
            <p><strong>Teléfono:</strong> ${courier.phone || 'No disponible'}</p>
            <p><strong>Entregas:</strong> ${courier.current_load || 0} / ${courier.max_capacity}</p>
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
      markersAdded++;

      console.log(`✅ Marcador agregado para ${courier.name} en`, courier.current_location);

    } catch (error) {
      console.error(`❌ Error agregando marcador para ${courier.name}:`, error);
    }
  });

  console.log(`🗺️ Marcadores actualizados: ${markersAdded} mensajeros con ubicación`);
}

function toggleDebug() {
  debugMode.value = !debugMode.value;
}

// ... (el resto de las funciones se mantienen igual)
</script>

<style scoped>
/* Estilos adicionales para debug */
.debug-info {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #f3f4f6;
  border-radius: 4px;
  font-size: 0.75rem;
}

.debug-panel {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 2rem;
}

.debug-panel h4 {
  margin: 0 0 0.5rem 0;
  color: #92400e;
}

.debug-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.debug-section {
  font-size: 0.875rem;
}

.debug-item {
  padding: 0.25rem 0;
  font-family: monospace;
}

.location-count {
  background: #3b82f6;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.courier-card.no-location {
  opacity: 0.6;
}

.courier-avatar.no-location {
  background: #6b7280 !important;
}

.location-indicator.has-location {
  color: #22c55e;
  font-weight: 600;
}

.location-indicator:not(.has-location) {
  color: #ef4444;
}

.empty-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}
</style>
