<!-- frontend/src/views/CourierTracker.vue - Versión mejorada -->
<template>
  <div class="tracker-wrapper">
    <div class="tracker-header">
      <h2>App del Mensajero</h2>
      <div class="courier-status">
        <span class="status-label">Estado:</span>
        <select v-model="status" @change="updateStatus" class="status-select">
          <option value="offline">🔴 Desconectado</option>
          <option value="available">🟢 Disponible</option>
          <option value="busy">🟡 Ocupado</option>
        </select>
      </div>
    </div>

    <!-- Panel de información -->
    <div class="tracking-info">
      <div class="info-card">
        <span class="info-label">Estado del GPS</span>
        <span :class="`info-value ${tracking ? 'active' : 'inactive'}`">
          {{ tracking ? '🟢 Activo' : '🔴 Inactivo' }}
        </span>
      </div>
      <div class="info-card">
        <span class="info-label">Entregas activas</span>
        <span class="info-value">{{ myDeliveries.length }}</span>
      </div>
      <div class="info-card">
        <span class="info-label">Velocidad</span>
        <span class="info-value">{{ speed ? `${speed} km/h` : '-' }}</span>
      </div>
      <div class="info-card">
        <span class="info-label">Última actualización</span>
        <span class="info-value">{{ lastUpdate || '-' }}</span>
      </div>
    </div>

    <!-- Controles -->
    <div class="controls">
      <button
        @click="toggleTracking"
        :class="`btn ${tracking ? 'btn-warning' : 'btn-primary'}`"
      >
        {{ tracking ? '⏸️ Pausar Tracking' : '▶️ Iniciar Tracking' }}
      </button>
      <button @click="centerMap" class="btn btn-secondary">
        🎯 Centrar en Mí
      </button>
      <button @click="refreshData" class="btn btn-secondary">
        🔄 Actualizar
      </button>
    </div>

    <!-- Mapa principal -->
    <div id="tracker-map" class="map-container"></div>

    <!-- Lista de entregas -->
    <div class="deliveries-section">
      <div class="section-header">
        <h3>Mis Entregas ({{ myDeliveries.length }})</h3>
        <div class="delivery-filters">
          <select v-model="deliveryFilter" class="filter-select">
            <option value="all">Todas las entregas</option>
            <option value="active">Solo activas</option>
            <option value="assigned">Asignadas</option>
            <option value="picked_up">Recogidas</option>
            <option value="in_transit">En camino</option>
          </select>
        </div>
      </div>

      <div v-if="filteredDeliveries.length === 0" class="empty-deliveries">
        <p>No tienes entregas {{ deliveryFilter !== 'all' ? deliveryFilterText[deliveryFilter] : '' }}</p>
      </div>

      <div v-else class="deliveries-list">
        <div
          v-for="delivery in filteredDeliveries"
          :key="delivery.id"
          class="delivery-card"
          :class="{ active: focusedDeliveryId === delivery.id }"
          @click="focusDelivery(delivery)"
        >
          <div class="delivery-header">
            <div class="delivery-title">
              <h4>#{{ delivery.id }} - {{ delivery.customer_name }}</h4>
              <p class="customer-phone">📞 {{ delivery.customer_phone }}</p>
            </div>
            <span :class="`status-badge ${delivery.status}`">
              {{ statusText(delivery.status) }}
            </span>
          </div>

          <div class="delivery-info">
            <p class="delivery-address">📍 {{ delivery.delivery_address }}</p>
            <div class="delivery-meta">
              <span class="meta-item">📏 {{ delivery.distance_km }} km</span>
              <span class="meta-item">⏱ {{ formatDeliveryTime(delivery) }}</span>
            </div>
            <p v-if="delivery.notes" class="delivery-notes">
              <strong>📝 Notas:</strong> {{ delivery.notes }}
            </p>
          </div>

          <div class="delivery-actions">
            <button
              v-if="delivery.status === 'assigned'"
              @click.stop="updateDeliveryStatus(delivery.id, 'picked_up')"
              class="btn-action btn-pickup"
            >
              📦 Marcar como Recogido
            </button>
            <button
              v-if="delivery.status === 'picked_up'"
              @click.stop="updateDeliveryStatus(delivery.id, 'in_transit')"
              class="btn-action btn-transit"
            >
              🚚 En Camino a Entrega
            </button>
            <button
              v-if="delivery.status === 'in_transit'"
              @click.stop="updateDeliveryStatus(delivery.id, 'delivered')"
              class="btn-action btn-delivered"
            >
              ✅ Marcar como Entregado
            </button>
            <button
              @click.stop="viewDeliveryDetails(delivery)"
              class="btn-action btn-info"
            >
              ℹ️ Detalles
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de detalles de entrega -->
    <div v-if="selectedDelivery" class="modal-overlay" @click.self="selectedDelivery = null">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Entrega #{{ selectedDelivery.id }}</h3>
          <button @click="selectedDelivery = null" class="btn-close">✕</button>
        </div>

        <div class="delivery-details">
          <div class="detail-section">
            <h4>Información del Cliente</h4>
            <p><strong>Nombre:</strong> {{ selectedDelivery.customer_name }}</p>
            <p><strong>Teléfono:</strong> {{ selectedDelivery.customer_phone }}</p>
            <p><strong>Dirección:</strong> {{ selectedDelivery.delivery_address }}</p>
          </div>

          <div class="detail-section">
            <h4>Información de la Entrega</h4>
            <p><strong>Estado:</strong> <span :class="`status-badge ${selectedDelivery.status}`">
              {{ statusText(selectedDelivery.status) }}
            </span></p>
            <p><strong>Distancia:</strong> {{ selectedDelivery.distance_km }} km</p>
            <p v-if="selectedDelivery.assigned_at">
              <strong>Asignada:</strong> {{ formatDateTime(selectedDelivery.assigned_at) }}
            </p>
            <p v-if="selectedDelivery.picked_up_at">
              <strong>Recogida:</strong> {{ formatDateTime(selectedDelivery.picked_up_at) }}
            </p>
          </div>

          <div v-if="selectedDelivery.notes" class="detail-section">
            <h4>Notas</h4>
            <p>{{ selectedDelivery.notes }}</p>
          </div>
        </div>

        <div class="modal-actions">
          <button @click="selectedDelivery = null" class="btn btn-secondary">Cerrar</button>
          <button
            v-if="selectedDelivery.status !== 'delivered'"
            @click="focusDelivery(selectedDelivery)"
            class="btn btn-primary"
          >
            🗺️ Ver en Mapa
          </button>
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
const tracking = ref(false);
const currentPosition = ref(null);
const currentMarker = ref(null);
const pathLine = ref(null);
const pathCoordinates = ref([]);
const status = ref('offline');
const speed = ref(null);
const lastUpdate = ref(null);
const error = ref(null);
const courierId = ref(null);
const myDeliveries = ref([]);
const deliveryMarkers = ref({});
const focusedDeliveryId = ref(null);
const selectedDelivery = ref(null);
const deliveryFilter = ref('active');

let watchId = null;
let updateInterval = null;

// Computed
const filteredDeliveries = computed(() => {
  if (deliveryFilter.value === 'all') return myDeliveries.value;
  if (deliveryFilter.value === 'active') {
    return myDeliveries.value.filter(d =>
      ['assigned', 'picked_up', 'in_transit'].includes(d.status)
    );
  }
  return myDeliveries.value.filter(d => d.status === deliveryFilter.value);
});

const deliveryFilterText = {
  all: 'todas',
  active: 'activas',
  assigned: 'asignadas',
  picked_up: 'recogidas',
  in_transit: 'en camino'
};

// Configuración de iconos
const courierIcon = L.divIcon({
  className: 'courier-marker',
  html: `<div style="background: #3b82f6; width: 40px; height: 40px; border-radius: 50%; border: 4px solid white; box-shadow: 0 2px 12px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; font-size: 20px;">🚗</div>`,
  iconSize: [40, 40],
  iconAnchor: [20, 20],
});

const pickupIcon = L.divIcon({
  className: 'pickup-marker',
  html: '<div style="background: #f59e0b; color: white; width: 36px; height: 36px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">📦</div>',
  iconSize: [36, 36],
  iconAnchor: [18, 18],
});

const deliveryIcon = L.divIcon({
  className: 'delivery-marker',
  html: '<div style="background: #22c55e; color: white; width: 36px; height: 36px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🏠</div>',
  iconSize: [36, 36],
  iconAnchor: [18, 18],
});

// Funciones de utilidad
const statusText = (status) => {
  const texts = {
    assigned: "Asignada",
    picked_up: "Recogida",
    in_transit: "En tránsito",
    delivered: "Entregada",
  };
  return texts[status] || status;
};

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('es-CO');
};

const formatDeliveryTime = (delivery) => {
  if (delivery.picked_up_at && delivery.assigned_at) {
    const assigned = new Date(delivery.assigned_at);
    const pickedUp = new Date(delivery.picked_up_at);
    const diff = Math.round((pickedUp - assigned) / 60000); // minutos
    return `${diff} min`;
  }
  return '-';
};

// Funciones principales
async function loadMyDeliveries() {
  try {
    const { data } = await apiClient.get("/my-deliveries");
    myDeliveries.value = data;
    updateDeliveryMarkers();
  } catch (err) {
    console.error("Error cargando entregas:", err);
    error.value = "Error al cargar entregas";
  }
}

function updateDeliveryMarkers() {
  // Limpiar marcadores anteriores
  Object.values(deliveryMarkers.value).forEach(markers => {
    markers.pickup.remove();
    markers.delivery.remove();
    if (markers.line) markers.line.remove();
  });
  deliveryMarkers.value = {};

  // Crear nuevos marcadores
  myDeliveries.value.forEach(delivery => {
    if (['assigned', 'picked_up', 'in_transit'].includes(delivery.status)) {
      const pickupMarker = L.marker(
        [delivery.pickup_location.lat, delivery.pickup_location.lng],
        { icon: pickupIcon }
      ).addTo(map.value).bindPopup(`
        <div class="map-popup">
          <strong>📦 Recogida #${delivery.id}</strong><br>
          <strong>Cliente:</strong> ${delivery.customer_name}<br>
          <strong>Teléfono:</strong> ${delivery.customer_phone}
        </div>
      `);

      const deliveryMarker = L.marker(
        [delivery.delivery_location.lat, delivery.delivery_location.lng],
        { icon: deliveryIcon }
      ).addTo(map.value).bindPopup(`
        <div class="map-popup">
          <strong>🏠 Entrega #${delivery.id}</strong><br>
          <strong>Cliente:</strong> ${delivery.customer_name}<br>
          <strong>Dirección:</strong> ${delivery.delivery_address}<br>
          <strong>Teléfono:</strong> ${delivery.customer_phone}
        </div>
      `);

      const line = L.polyline([
        [delivery.pickup_location.lat, delivery.pickup_location.lng],
        [delivery.delivery_location.lat, delivery.delivery_location.lng]
      ], {
        color: '#3b82f6',
        weight: 3,
        opacity: 0.6,
        dashArray: '5, 5'
      }).addTo(map.value);

      deliveryMarkers.value[delivery.id] = {
        pickup: pickupMarker,
        delivery: deliveryMarker,
        line: line
      };
    }
  });

  // Ajustar vista del mapa si hay entregas
  adjustMapView();
}

function adjustMapView() {
  const allPoints = [];

  // Agregar puntos de entregas
  myDeliveries.value.forEach(d => {
    if (['assigned', 'picked_up', 'in_transit'].includes(d.status)) {
      allPoints.push([d.pickup_location.lat, d.pickup_location.lng]);
      allPoints.push([d.delivery_location.lat, d.delivery_location.lng]);
    }
  });

  // Agregar posición actual si existe
  if (currentPosition.value) {
    allPoints.push([currentPosition.value.lat, currentPosition.value.lng]);
  }

  if (allPoints.length > 0) {
    const bounds = L.latLngBounds(allPoints);
    map.value.fitBounds(bounds, { padding: [50, 50] });
  }
}

function focusDelivery(delivery) {
  focusedDeliveryId.value = delivery.id;

  // Centrar en el punto de entrega
  map.value.setView(
    [delivery.delivery_location.lat, delivery.delivery_location.lng],
    15
  );

  // Abrir popup del marcador de entrega
  if (deliveryMarkers.value[delivery.id]) {
    deliveryMarkers.value[delivery.id].delivery.openPopup();
  }
}

function viewDeliveryDetails(delivery) {
  selectedDelivery.value = delivery;
}

async function updateDeliveryStatus(deliveryId, newStatus) {
  const confirmMessages = {
    picked_up: "¿Confirmar que recogiste el paquete?",
    in_transit: "¿Confirmar que vas en camino a la entrega?",
    delivered: "¿Confirmar que entregaste el paquete al cliente?"
  };

  if (!confirm(confirmMessages[newStatus])) return;

  try {
    await apiClient.patch(`/deliveries/${deliveryId}/status`, {
      status: newStatus
    });

    await loadMyDeliveries();

    const successMessages = {
      picked_up: "✅ Paquete marcado como recogido",
      in_transit: "🚚 En camino a la entrega",
      delivered: "🎉 ¡Entrega completada!"
    };

    alert(successMessages[newStatus]);

  } catch (err) {
    console.error("Error actualizando estado:", err);
    alert("❌ Error al actualizar el estado");
  }
}

async function updateStatus() {
  if (!courierId.value) return;

  try {
    await apiClient.put(`/couriers/${courierId.value}`, {
      status: status.value
    });
  } catch (err) {
    console.error("Error actualizando estado:", err);
    error.value = "Error al actualizar estado";
  }
}

// Funciones de geolocalización
async function sendLocationToServer(position) {
  if (!courierId.value || status.value === 'offline') return;

  try {
    const payload = {
      lat: position.coords.latitude,
      lng: position.coords.longitude,
      speed: position.coords.speed ? Math.round(position.coords.speed * 3.6) : null,
      accuracy: Math.round(position.coords.accuracy),
    };

    await apiClient.post(`/couriers/${courierId.value}/location`, payload);

    lastUpdate.value = new Date().toLocaleTimeString();
    speed.value = payload.speed;
    error.value = null;
  } catch (err) {
    error.value = "Error enviando ubicación al servidor";
    console.error("Error:", err);
  }
}

function updateMapPosition(position) {
  const lat = position.coords.latitude;
  const lng = position.coords.longitude;
  currentPosition.value = { lat, lng };

  // Actualizar marcador
  if (currentMarker.value) {
    currentMarker.value.setLatLng([lat, lng]);
  } else {
    currentMarker.value = L.marker([lat, lng], { icon: courierIcon })
      .addTo(map.value)
      .bindPopup("<strong>Tu ubicación actual</strong>");
  }

  // Actualizar ruta
  pathCoordinates.value.push([lat, lng]);
  if (pathLine.value) {
    pathLine.value.setLatLngs(pathCoordinates.value);
  } else {
    pathLine.value = L.polyline(pathCoordinates.value, {
      color: '#3b82f6',
      weight: 4,
      opacity: 0.7,
    }).addTo(map.value);
  }
}

function handlePositionUpdate(position) {
  updateMapPosition(position);

  if (position.coords.accuracy < 50) {
    sendLocationToServer(position);
  }
}

function handlePositionError(err) {
  error.value = `Error de GPS: ${err.message}`;
  console.error("Error de geolocalización:", err);
}

function startTracking() {
  if (!navigator.geolocation) {
    error.value = "Tu navegador no soporta geolocalización";
    return;
  }

  tracking.value = true;
  error.value = null;

  // Posición inicial
  navigator.geolocation.getCurrentPosition(
    handlePositionUpdate,
    handlePositionError,
    { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
  );

  // Seguimiento continuo
  watchId = navigator.geolocation.watchPosition(
    handlePositionUpdate,
    handlePositionError,
    { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
  );

  // Envío periódico al servidor
  updateInterval = setInterval(() => {
    if (currentPosition.value && status.value !== 'offline') {
      sendLocationToServer({
        coords: {
          latitude: currentPosition.value.lat,
          longitude: currentPosition.value.lng,
          accuracy: 30,
          speed: speed.value ? speed.value / 3.6 : null,
        }
      });
    }
  }, 30000); // Cada 30 segundos
}

function stopTracking() {
  tracking.value = false;

  if (watchId !== null) {
    navigator.geolocation.clearWatch(watchId);
    watchId = null;
  }

  if (updateInterval) {
    clearInterval(updateInterval);
    updateInterval = null;
  }
}

function toggleTracking() {
  if (tracking.value) {
    stopTracking();
  } else {
    startTracking();
  }
}

function centerMap() {
  if (currentPosition.value) {
    map.value.setView([currentPosition.value.lat, currentPosition.value.lng], 16);
  } else {
    alert("No se pudo obtener tu ubicación actual");
  }
}

async function refreshData() {
  await loadMyDeliveries();
  if (currentPosition.value) {
    sendLocationToServer({
      coords: {
        latitude: currentPosition.value.lat,
        longitude: currentPosition.value.lng,
        accuracy: 30,
        speed: speed.value ? speed.value / 3.6 : null,
      }
    });
  }
}

// Carga de datos del usuario
async function loadUserData() {
  try {
    const { data } = await apiClient.get("/user");
    if (data.courier) {
      courierId.value = data.courier.id;
      status.value = data.courier.status || 'offline';
    } else {
      error.value = "Este usuario no está registrado como mensajero";
    }
  } catch (err) {
    console.error("Error cargando datos del usuario:", err);
    error.value = "Error al cargar datos del usuario";
  }
}

// Inicialización
onMounted(async () => {
  await loadUserData();

  // Inicializar mapa
  map.value = L.map("tracker-map", {
    center: [10.9878, -74.7889],
    zoom: 13,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
  }).addTo(map.value);

  // Ajustar tamaño del mapa
  setTimeout(() => map.value.invalidateSize(), 100);

  // Cargar entregas
  await loadMyDeliveries();

  // Iniciar tracking automáticamente si está disponible
  if (status.value !== 'offline') {
    setTimeout(() => startTracking(), 1000);
  }
});

onUnmounted(() => {
  stopTracking();
});
</script>

<style scoped>
.tracker-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1rem;
}

.tracker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
}

.courier-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-label {
  font-weight: 600;
  color: #374151;
}

.status-select {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
  font-size: 1rem;
  font-weight: 600;
  background: white;
  cursor: pointer;
  min-width: 180px;
}

.tracking-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.info-card {
  background: white;
  padding: 1.25rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  border-left: 4px solid #3b82f6;
}

.info-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.info-value {
  font-size: 1.5rem;
  font-weight: bold;
}

.info-value.active { color: #22c55e; }
.info-value.inactive { color: #ef4444; }

.controls {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

.map-container {
  width: 100%;
  height: 500px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  margin-bottom: 2rem;
  overflow: hidden;
}

.deliveries-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-header h3 {
  margin: 0;
  color: #1f2937;
}

.filter-select {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: 1px solid #d1d5db;
  background: white;
}

.empty-deliveries {
  text-align: center;
  padding: 3rem;
  color: #9ca3af;
  font-size: 1.125rem;
}

.deliveries-list {
  display: grid;
  gap: 1rem;
}

.delivery-card {
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.delivery-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
  transform: translateY(-2px);
}

.delivery-card.active {
  border-color: #3b82f6;
  background: #f0f9ff;
}

.delivery-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  gap: 1rem;
}

.delivery-title h4 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 1.125rem;
}

.customer-phone {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
}

.status-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-badge.assigned {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.picked_up {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.in_transit {
  background: #e0e7ff;
  color: #4338ca;
}

.status-badge.delivered {
  background: #d1fae5;
  color: #065f46;
}

.delivery-info {
  margin-bottom: 1rem;
}

.delivery-address {
  margin: 0 0 0.75rem 0;
  color: #374151;
  font-weight: 500;
}

.delivery-meta {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.meta-item {
  font-size: 0.875rem;
  color: #6b7280;
}

.delivery-notes {
  margin: 0;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 6px;
  border-left: 3px solid #f59e0b;
  font-size: 0.875rem;
  color: #4b5563;
}

.delivery-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-action {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
  flex: 1;
  min-width: 120px;
}

.btn-pickup {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fbbf24;
}

.btn-pickup:hover {
  background: #fde68a;
}

.btn-transit {
  background: #e0e7ff;
  color: #4338ca;
  border: 1px solid #a5b4fc;
}

.btn-transit:hover {
  background: #c7d2fe;
}

.btn-delivered {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #34d399;
}

.btn-delivered:hover {
  background: #a7f3d0;
}

.btn-info {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-info:hover {
  background: #e5e7eb;
}

/* Estilos del modal */
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
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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

.delivery-details {
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

.detail-section p {
  margin: 0.5rem 0;
  color: #4b5563;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.error-message {
  background: #fef2f2;
  color: #991b1b;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #fecaca;
  margin-top: 1rem;
}

/* Estilos para los popups del mapa */
:deep(.map-popup) {
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 0.875rem;
  line-height: 1.4;
}

:deep(.map-popup strong) {
  color: #1f2937;
}

/* Responsive */
@media (max-width: 768px) {
  .tracker-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .courier-status {
    justify-content: space-between;
  }

  .controls {
    flex-direction: column;
  }

  .section-header {
    flex-direction: column;
    align-items: stretch;
  }

  .delivery-header {
    flex-direction: column;
  }

  .delivery-actions {
    flex-direction: column;
  }

  .modal-actions {
    flex-direction: column;
  }
}
</style>
