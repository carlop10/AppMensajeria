<!-- frontend/src/views/CourierDashboard.vue -->
<template>
  <div class="dashboard-wrapper">
    <div class="header">
      <h2>Dashboard de Mensajeros</h2>
      <div class="stats">
        <div class="stat-card">
          <span class="stat-label">Activos</span>
          <span class="stat-value">{{ activeCouriers.length }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">En entrega</span>
          <span class="stat-value">{{ busyCouriers }}</span>
        </div>
      </div>
    </div>

    <div id="dashboard-map"></div>

    <div class="courier-list">
      <h3>Mensajeros Activos</h3>
      <div
        v-for="courier in activeCouriers"
        :key="courier.id"
        class="courier-item"
        @click="focusOnCourier(courier)"
      >
        <div class="courier-info">
          <strong>{{ courier.name }}</strong>
          <span :class="`status-badge ${courier.status}`">
            {{ courier.status }}
          </span>
        </div>
        <div class="courier-details">
          <span>Entregas activas: {{ courier.active_deliveries }}</span>
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

const map = ref(null);
const activeCouriers = ref([]);
const courierMarkers = ref({});
let updateInterval = null;

const busyCouriers = computed(() => {
  return activeCouriers.value.filter(c => c.status === 'busy').length;
});

// Iconos personalizados para mensajeros
const courierIcon = (status) => {
  const color = status === 'available' ? '#22c55e' : '#f59e0b';
  return L.divIcon({
    className: 'custom-courier-icon',
    html: `
      <div style="
        background: ${color};
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
      ">🏍️</div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 16],
  });
};

async function loadActiveCouriers() {
  try {
    const { data } = await apiClient.get("/couriers/active");
    activeCouriers.value = data;
    updateMarkers();
  } catch (error) {
    console.error("Error cargando mensajeros:", error);
  }
}

function updateMarkers() {
  // Remover marcadores de mensajeros que ya no están activos
  Object.keys(courierMarkers.value).forEach((courierId) => {
    const exists = activeCouriers.value.find(c => c.id == courierId);
    if (!exists) {
      courierMarkers.value[courierId].remove();
      delete courierMarkers.value[courierId];
    }
  });

  // Actualizar o crear marcadores
  activeCouriers.value.forEach((courier) => {
    const { lat, lng } = courier.location;

    if (courierMarkers.value[courier.id]) {
      // Actualizar posición existente
      courierMarkers.value[courier.id].setLatLng([lat, lng]);
      courierMarkers.value[courier.id].setIcon(courierIcon(courier.status));
    } else {
      // Crear nuevo marcador
      const marker = L.marker([lat, lng], {
        icon: courierIcon(courier.status)
      })
        .addTo(map.value)
        .bindPopup(`
          <strong>${courier.name}</strong><br>
          Estado: ${courier.status}<br>
          Entregas: ${courier.active_deliveries}
        `);

      courierMarkers.value[courier.id] = marker;
    }
  });

  // Ajustar vista del mapa para mostrar todos los mensajeros
  if (activeCouriers.value.length > 0) {
    const bounds = L.latLngBounds(
      activeCouriers.value.map(c => [c.location.lat, c.location.lng])
    );
    map.value.fitBounds(bounds, { padding: [50, 50] });
  }
}

function focusOnCourier(courier) {
  map.value.setView([courier.location.lat, courier.location.lng], 15);
  courierMarkers.value[courier.id].openPopup();
}

onMounted(async () => {
  // Inicializar mapa centrado en Barranquilla
  map.value = L.map("dashboard-map", {
    center: [10.9878, -74.7889], // Barranquilla
    zoom: 13,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map.value);

  setTimeout(() => map.value.invalidateSize(), 100);

  // Cargar mensajeros inicialmente
  await loadActiveCouriers();

  // Actualizar cada 10 segundos
  updateInterval = setInterval(loadActiveCouriers, 10000);
});

onUnmounted(() => {
  if (updateInterval) clearInterval(updateInterval);
});
</script>

<style scoped>
.dashboard-wrapper {
  display: grid;
  grid-template-columns: 1fr 300px;
  grid-template-rows: auto 1fr;
  gap: 1rem;
  height: calc(100vh - 120px);
}

.header {
  grid-column: 1 / -1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stats {
  display: flex;
  gap: 1rem;
}

.stat-card {
  background: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-label {
  font-size: 0.875rem;
  color: #666;
}

.stat-value {
  font-size: 2rem;
  font-weight: bold;
  color: #007bff;
}

#dashboard-map {
  width: 100%;
  height: 100%;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.courier-list {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow-y: auto;
}

.courier-list h3 {
  margin-bottom: 1rem;
  font-size: 1.125rem;
}

.courier-item {
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.courier-item:hover {
  background: #f9fafb;
  border-color: #007bff;
}

.courier-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.available {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.busy {
  background: #fef3c7;
  color: #92400e;
}

.courier-details {
  font-size: 0.875rem;
  color: #666;
}
</style>
