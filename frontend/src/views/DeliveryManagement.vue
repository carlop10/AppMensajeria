<!-- frontend/src/views/DeliveryManagement.vue -->
<template>
  <div class="delivery-wrapper">
    <div class="header-section">
      <h2>Gestión de Entregas</h2>
      <button @click="openCreateModal" class="btn-primary">
        ➕ Nueva Entrega
      </button>
    </div>

    <!-- Filtros -->
    <div class="filters">
      <div class="filter-group">
        <label>Estado:</label>
        <select v-model="filterStatus" @change="loadDeliveries">
          <option value="">Todos los estados</option>
          <option value="pending">Pendiente</option>
          <option value="assigned">Asignada</option>
          <option value="picked_up">Recogida</option>
          <option value="in_transit">En tránsito</option>
          <option value="delivered">Entregada</option>
          <option value="cancelled">Cancelada</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Mensajero:</label>
        <select v-model="filterCourier" @change="loadDeliveries">
          <option value="">Todos los mensajeros</option>
          <option v-for="courier in couriers" :key="courier.id" :value="courier.id">
            {{ courier.name }} - {{ courier.status }}
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label>Mostrar:</label>
        <select v-model="itemsPerPage" @change="loadDeliveries">
          <option value="10">10 items</option>
          <option value="25">25 items</option>
          <option value="50">50 items</option>
          <option value="100">100 items</option>
        </select>
      </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.total }}</div>
          <div class="stat-label">Total</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.pending }}</div>
          <div class="stat-label">Pendientes</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🚗</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.assigned }}</div>
          <div class="stat-label">Asignadas</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.delivered }}</div>
          <div class="stat-label">Entregadas</div>
        </div>
      </div>
    </div>

    <!-- Tabla de entregas -->
    <div class="table-container">
      <div class="table-header">
        <h3>Lista de Entregas ({{ deliveries.length }})</h3>
        <div class="table-actions">
          <button @click="exportToCSV" class="btn-secondary" :disabled="deliveries.length === 0">
            📊 Exportar CSV
          </button>
          <button @click="loadDeliveries" class="btn-secondary">
            🔄 Actualizar
          </button>
        </div>
      </div>

      <table v-if="deliveries.length > 0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Mensajero</th>
            <th>Estado</th>
            <th>Distancia</th>
            <th>Asignada</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="delivery in deliveries" :key="delivery.id"
              :class="`status-${delivery.status}`">
            <td class="delivery-id">#{{ delivery.id }}</td>
            <td class="customer-info">
              <strong>{{ delivery.customer_name }}</strong>
              <div class="customer-phone">{{ delivery.customer_phone }}</div>
            </td>
            <td class="delivery-address">
              {{ delivery.delivery_address }}
              <div v-if="delivery.notes" class="delivery-notes" :title="delivery.notes">
                📝 Notas
              </div>
            </td>
            <td class="courier-info">
              <span v-if="delivery.courier" class="courier-assigned">
                <strong>{{ delivery.courier.name }}</strong>
                <div class="courier-status" :class="delivery.courier.status">
                  {{ delivery.courier.status }}
                </div>
              </span>
              <span v-else class="no-courier">
                <span class="text-muted">Sin asignar</span>
              </span>
            </td>
            <td class="status-cell">
              <span :class="`status-badge ${delivery.status}`">
                {{ statusText(delivery.status) }}
              </span>
            </td>
            <td class="distance">
              {{ delivery.distance_km }} km
            </td>
            <td class="date-info">
              <div v-if="delivery.assigned_at" class="date-assigned">
                {{ formatDate(delivery.assigned_at) }}
              </div>
              <div v-else class="text-muted">-</div>
            </td>
            <td class="actions">
              <div class="action-buttons">
                <button @click="viewOnMap(delivery)" class="btn-icon" title="Ver en mapa">
                  🗺️
                </button>
                <button @click="editDelivery(delivery)" class="btn-icon" title="Editar">
                  ✏️
                </button>
                <button v-if="!delivery.courier && delivery.status === 'pending'"
                        @click="assignCourier(delivery)" class="btn-icon" title="Asignar mensajero">
                  👤
                </button>
                <button @click="deleteDelivery(delivery.id)" class="btn-icon btn-danger" title="Eliminar">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>No hay entregas registradas</h3>
        <p>Comienza creando tu primera entrega</p>
        <button @click="openCreateModal" class="btn-primary">
          Crear primera entrega
        </button>
      </div>
    </div>

    <!-- Modal de Crear/Editar Entrega -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h3>{{ editingDelivery ? 'Editar Entrega' : 'Nueva Entrega' }}</h3>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>

        <form @submit.prevent="saveDelivery" class="delivery-form">
          <div class="form-section">
            <h4>Información del Cliente</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Nombre del cliente *</label>
                <input v-model="form.customer_name" type="text" required
                       placeholder="Ej: Juan Pérez" />
              </div>

              <div class="form-group">
                <label>Teléfono *</label>
                <input v-model="form.customer_phone" type="tel" required
                       placeholder="Ej: +57 300 123 4567" />
              </div>
            </div>

            <div class="form-group">
              <label>Dirección de entrega *</label>
              <textarea v-model="form.delivery_address" rows="2" required
                        placeholder="Dirección completa para la entrega"></textarea>
            </div>
          </div>

          <div class="form-section">
            <h4>Asignación</h4>
            <div class="form-group">
              <label>Asignar mensajero</label>
              <select v-model="form.courier_id" class="courier-select">
                <option value="">Seleccionar mensajero...</option>
                <optgroup label="Mensajeros Disponibles">
                  <option v-for="courier in availableCouriers" :key="courier.id" :value="courier.id">
                    {{ courier.name }} - {{ courier.phone }} ({{ courier.status }})
                  </option>
                </optgroup>
                <optgroup label="Mensajeros Ocupados" v-if="busyCouriers.length > 0">
                  <option v-for="courier in busyCouriers" :key="courier.id" :value="courier.id">
                    {{ courier.name }} - {{ courier.phone }} ({{ courier.status }})
                  </option>
                </optgroup>
              </select>
              <div v-if="form.courier_id" class="selected-courier-info">
                Mensajero seleccionado: {{ getCourierName(form.courier_id) }}
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4>Ubicaciones</h4>
            <p class="help-text">
              Haz clic en el mapa para establecer los puntos de recogida y entrega.
              <span v-if="!form.pickup_lat" class="text-primary">📍 Primero: Punto de recogida</span>
              <span v-else-if="!form.delivery_lat" class="text-success">📍 Ahora: Punto de entrega</span>
              <span v-else class="text-success">✅ Ubicaciones establecidas</span>
            </p>

            <div class="location-controls">
              <button type="button" @click="clearLocations" class="btn-secondary btn-sm">
                🗑️ Limpiar ubicaciones
              </button>
              <button type="button" @click="useCurrentLocation('pickup')" class="btn-secondary btn-sm">
                📍 Usar mi ubicación (Recogida)
              </button>
              <button type="button" @click="useCurrentLocation('delivery')" class="btn-secondary btn-sm">
                📍 Usar mi ubicación (Entrega)
              </button>
            </div>

            <div id="delivery-map" class="creation-map"></div>

            <div v-if="form.pickup_lat && form.delivery_lat" class="distance-info">
              <strong>Distancia estimada:</strong> {{ calculateDistance() }} km
            </div>
          </div>

          <div class="form-section">
            <h4>Información Adicional</h4>
            <div class="form-group">
              <label>Notas adicionales</label>
              <textarea v-model="form.notes" rows="3"
                        placeholder="Instrucciones especiales, referencias, etc."></textarea>
            </div>
          </div>

          <div class="form-errors" v-if="Object.keys(formErrors).length > 0">
            <h4>Errores:</h4>
            <ul>
              <li v-for="(error, field) in formErrors" :key="field">{{ error[0] }}</li>
            </ul>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-secondary">
              Cancelar
            </button>
            <button type="submit" class="btn-primary" :disabled="!canSave || saving">
              {{ saving ? 'Guardando...' : (editingDelivery ? 'Actualizar' : 'Crear Entrega') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Asignación de Mensajero -->
    <div v-if="showAssignModal" class="modal-overlay" @click.self="showAssignModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Asignar Mensajero - Entrega #{{ assigningDelivery?.id }}</h3>
          <button @click="showAssignModal = false" class="btn-close">✕</button>
        </div>

        <div class="assign-courier-list">
          <div v-for="courier in availableCouriers" :key="courier.id"
               class="courier-item" @click="assignToCourier(courier.id)">
            <div class="courier-avatar">{{ courier.name.charAt(0) }}</div>
            <div class="courier-details">
              <strong>{{ courier.name }}</strong>
              <div class="courier-meta">
                <span class="courier-phone">{{ courier.phone }}</span>
                <span class="courier-status available">Disponible</span>
              </div>
            </div>
            <div class="courier-action">➡️</div>
          </div>

          <div v-if="availableCouriers.length === 0" class="no-couriers">
            <p>No hay mensajeros disponibles en este momento</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Vista en Mapa -->
    <div v-if="viewingDelivery" class="modal-overlay" @click.self="viewingDelivery = null">
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h3>Entrega #{{ viewingDelivery.id }} - {{ viewingDelivery.customer_name }}</h3>
          <button @click="viewingDelivery = null" class="btn-close">✕</button>
        </div>
        <div class="delivery-map-info">
          <div class="delivery-details">
            <p><strong>Cliente:</strong> {{ viewingDelivery.customer_name }}</p>
            <p><strong>Teléfono:</strong> {{ viewingDelivery.customer_phone }}</p>
            <p><strong>Dirección:</strong> {{ viewingDelivery.delivery_address }}</p>
            <p><strong>Estado:</strong> <span :class="`status-badge ${viewingDelivery.status}`">
              {{ statusText(viewingDelivery.status) }}
            </span></p>
            <p><strong>Distancia:</strong> {{ viewingDelivery.distance_km }} km</p>
          </div>
          <div id="view-map" class="view-map"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import apiClient from "@/api/axios";

// Estado reactivo
const deliveries = ref([]);
const couriers = ref([]);
const showCreateModal = ref(false);
const showAssignModal = ref(false);
const editingDelivery = ref(null);
const assigningDelivery = ref(null);
const viewingDelivery = ref(null);
const filterStatus = ref("");
const filterCourier = ref("");
const itemsPerPage = ref("25");
const saving = ref(false);
const formErrors = ref({});

// Estadísticas
const stats = ref({
  total: 0,
  pending: 0,
  assigned: 0,
  delivered: 0
});

// Formulario
const form = ref({
  customer_name: "",
  customer_phone: "",
  delivery_address: "",
  pickup_lat: null,
  pickup_lng: null,
  delivery_lat: null,
  delivery_lng: null,
  courier_id: "",
  notes: "",
});

// Mapas
let deliveryMap = null;
let viewMap = null;
let pickupMarker = null;
let deliveryMarker = null;
let routeLine = null;

// Computed
const availableCouriers = computed(() => {
  return couriers.value.filter(c => c.status === 'available');
});

const busyCouriers = computed(() => {
  return couriers.value.filter(c => c.status === 'busy');
});

const canSave = computed(() => {
  return form.value.customer_name &&
         form.value.customer_phone &&
         form.value.delivery_address &&
         form.value.pickup_lat &&
         form.value.delivery_lat;
});

// Watchers
watch(showCreateModal, (newVal) => {
  if (newVal) {
    nextTick(() => {
      setTimeout(() => {
        initDeliveryMap();
      }, 300);
    });
  }
});

// Funciones de utilidad
const statusText = (status) => {
  const texts = {
    pending: "Pendiente",
    assigned: "Asignada",
    picked_up: "Recogida",
    in_transit: "En tránsito",
    delivered: "Entregada",
    cancelled: "Cancelada",
  };
  return texts[status] || status;
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('es-CO');
};

const getCourierName = (courierId) => {
  const courier = couriers.value.find(c => c.id === courierId);
  return courier ? courier.name : 'Desconocido';
};

const calculateDistance = () => {
  if (!form.value.pickup_lat || !form.value.delivery_lat) return 0;

  const R = 6371; // Radio de la Tierra en km
  const dLat = (form.value.delivery_lat - form.value.pickup_lat) * Math.PI / 180;
  const dLon = (form.value.delivery_lng - form.value.pickup_lng) * Math.PI / 180;
  const a =
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(form.value.pickup_lat * Math.PI / 180) *
    Math.cos(form.value.delivery_lat * Math.PI / 180) *
    Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return (R * c).toFixed(2);
};

// Funciones de datos
async function loadDeliveries() {
  try {
    const params = {};
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterCourier.value) params.courier_id = filterCourier.value;
    if (itemsPerPage.value) params.per_page = itemsPerPage.value;

    const { data } = await apiClient.get("/deliveries", { params });
    deliveries.value = data;
    updateStats();
  } catch (err) {
    console.error("Error cargando entregas:", err);
    alert("Error al cargar entregas");
  }
}

function updateStats() {
  stats.value.total = deliveries.value.length;
  stats.value.pending = deliveries.value.filter(d => d.status === 'pending').length;
  stats.value.assigned = deliveries.value.filter(d => d.status === 'assigned').length;
  stats.value.delivered = deliveries.value.filter(d => d.status === 'delivered').length;
}

async function loadCouriers() {
  try {
    const { data } = await apiClient.get("/couriers");
    couriers.value = data;
  } catch (err) {
    console.error("Error cargando mensajeros:", err);
  }
}

// Funciones del mapa
function initDeliveryMap() {
  const mapElement = document.getElementById("delivery-map");
  if (!mapElement) {
    console.error("Elemento del mapa no encontrado");
    return;
  }

  // Limpiar mapa existente
  if (deliveryMap) {
    deliveryMap.remove();
  }

  deliveryMap = L.map("delivery-map", {
    center: [10.9878, -74.7889],
    zoom: 13,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
  }).addTo(deliveryMap);

  // Ajustar tamaño
  setTimeout(() => deliveryMap.invalidateSize(), 100);

  // Si estamos editando, cargar ubicaciones existentes
  if (editingDelivery.value && form.value.pickup_lat) {
    addExistingMarkers();
  }

  // Configurar click handler
  deliveryMap.on("click", handleMapClick);
}

function addExistingMarkers() {
  clearMapMarkers();

  // Marcador de recogida
  if (form.value.pickup_lat) {
    pickupMarker = L.marker([form.value.pickup_lat, form.value.pickup_lng], {
      icon: L.divIcon({
        html: '<div style="background: #3b82f6; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">📦</div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
      })
    }).addTo(deliveryMap).bindPopup("Punto de recogida");
  }

  // Marcador de entrega
  if (form.value.delivery_lat) {
    deliveryMarker = L.marker([form.value.delivery_lat, form.value.delivery_lng], {
      icon: L.divIcon({
        html: '<div style="background: #22c55e; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">🏠</div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
      })
    }).addTo(deliveryMap).bindPopup("Punto de entrega");
  }

  // Dibujar línea si ambos puntos existen
  if (form.value.pickup_lat && form.value.delivery_lat) {
    drawRouteLine();
    fitMapToBounds();
  }
}

function handleMapClick(e) {
  const { lat, lng } = e.latlng;

  if (!form.value.pickup_lat) {
    setPickupLocation(lat, lng);
  } else if (!form.value.delivery_lat) {
    setDeliveryLocation(lat, lng);
  } else {
    // Si ambos están establecidos, preguntar qué hacer
    if (confirm('¿Qué deseas hacer?\n\nAceptar: Reiniciar ubicaciones\nCancelar: Mantener actuales')) {
      clearLocations();
      setPickupLocation(lat, lng);
    }
  }
}

function setPickupLocation(lat, lng) {
  form.value.pickup_lat = lat;
  form.value.pickup_lng = lng;

  if (pickupMarker) deliveryMap.removeLayer(pickupMarker);

  pickupMarker = L.marker([lat, lng], {
    icon: L.divIcon({
      html: '<div style="background: #3b82f6; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">📦</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    })
  }).addTo(deliveryMap).bindPopup("Punto de recogida");
}

function setDeliveryLocation(lat, lng) {
  form.value.delivery_lat = lat;
  form.value.delivery_lng = lng;

  if (deliveryMarker) deliveryMap.removeLayer(deliveryMarker);

  deliveryMarker = L.marker([lat, lng], {
    icon: L.divIcon({
      html: '<div style="background: #22c55e; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">🏠</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    })
  }).addTo(deliveryMap).bindPopup("Punto de entrega");

  drawRouteLine();
  fitMapToBounds();
}

function drawRouteLine() {
  if (routeLine) deliveryMap.removeLayer(routeLine);

  routeLine = L.polyline([
    [form.value.pickup_lat, form.value.pickup_lng],
    [form.value.delivery_lat, form.value.delivery_lng]
  ], {
    color: '#3b82f6',
    weight: 4,
    opacity: 0.7,
    dashArray: '5, 5'
  }).addTo(deliveryMap);
}

function fitMapToBounds() {
  const bounds = L.latLngBounds([
    [form.value.pickup_lat, form.value.pickup_lng],
    [form.value.delivery_lat, form.value.delivery_lng]
  ]);
  deliveryMap.fitBounds(bounds, { padding: [20, 20] });
}

function clearMapMarkers() {
  if (pickupMarker) {
    deliveryMap.removeLayer(pickupMarker);
    pickupMarker = null;
  }
  if (deliveryMarker) {
    deliveryMap.removeLayer(deliveryMarker);
    deliveryMarker = null;
  }
  if (routeLine) {
    deliveryMap.removeLayer(routeLine);
    routeLine = null;
  }
}

function clearLocations() {
  form.value.pickup_lat = null;
  form.value.pickup_lng = null;
  form.value.delivery_lat = null;
  form.value.delivery_lng = null;
  clearMapMarkers();
}

function useCurrentLocation(type) {
  if (!navigator.geolocation) {
    alert("Tu navegador no soporta geolocalización");
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;

      if (type === 'pickup') {
        setPickupLocation(lat, lng);
      } else {
        setDeliveryLocation(lat, lng);
      }

      // Centrar mapa en la ubicación
      deliveryMap.setView([lat, lng], 15);
    },
    (error) => {
      alert("Error obteniendo ubicación: " + error.message);
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// Funciones de negocio
function openCreateModal() {
  editingDelivery.value = null;
  showCreateModal.value = true;
  resetForm();
}

function editDelivery(delivery) {
  editingDelivery.value = delivery;
  form.value = {
    customer_name: delivery.customer_name,
    customer_phone: delivery.customer_phone,
    delivery_address: delivery.delivery_address,
    pickup_lat: delivery.pickup_location.lat,
    pickup_lng: delivery.pickup_location.lng,
    delivery_lat: delivery.delivery_location.lat,
    delivery_lng: delivery.delivery_location.lng,
    courier_id: delivery.courier?.id || "",
    notes: delivery.notes || "",
  };
  showCreateModal.value = true;
}

function assignCourier(delivery) {
  assigningDelivery.value = delivery;
  showAssignModal.value = true;
}

async function assignToCourier(courierId) {
  try {
    await apiClient.put(`/deliveries/${assigningDelivery.value.id}`, {
      courier_id: courierId
    });

    await loadDeliveries();
    showAssignModal.value = false;
    assigningDelivery.value = null;
    alert("Mensajero asignado correctamente");
  } catch (err) {
    console.error("Error asignando mensajero:", err);
    alert("Error al asignar mensajero");
  }
}

async function saveDelivery() {
  saving.value = true;
  formErrors.value = {};

  try {
    if (editingDelivery.value) {
      await apiClient.put(`/deliveries/${editingDelivery.value.id}`, form.value);
    } else {
      await apiClient.post("/deliveries", form.value);
    }

    await loadDeliveries();
    closeModal();
    alert(editingDelivery.value ? "Entrega actualizada" : "Entrega creada exitosamente");
  } catch (err) {
    console.error("Error guardando entrega:", err);
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors;
    } else {
      alert(err.response?.data?.message || "Error al guardar entrega");
    }
  } finally {
    saving.value = false;
  }
}

async function deleteDelivery(id) {
  if (!confirm("¿Estás seguro de eliminar esta entrega? Esta acción no se puede deshacer.")) return;

  try {
    await apiClient.delete(`/deliveries/${id}`);
    await loadDeliveries();
    alert("Entrega eliminada correctamente");
  } catch (err) {
    console.error("Error eliminando:", err);
    alert("Error al eliminar entrega");
  }
}

function viewOnMap(delivery) {
  viewingDelivery.value = delivery;
  nextTick(() => {
    initViewMap();
  });
}

function initViewMap() {
  const mapElement = document.getElementById("view-map");
  if (!mapElement) return;

  if (viewMap) {
    viewMap.remove();
  }

  viewMap = L.map("view-map", {
    center: [viewingDelivery.value.pickup_location.lat, viewingDelivery.value.pickup_location.lng],
    zoom: 13,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
  }).addTo(viewMap);

  // Marcadores
  L.marker([viewingDelivery.value.pickup_location.lat, viewingDelivery.value.pickup_location.lng], {
    icon: L.divIcon({
      html: '<div style="background: #3b82f6; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">📦</div>',
      iconSize: [36, 36],
      iconAnchor: [18, 18],
    })
  }).addTo(viewMap).bindPopup("Punto de recogida");

  L.marker([viewingDelivery.value.delivery_location.lat, viewingDelivery.value.delivery_location.lng], {
    icon: L.divIcon({
      html: '<div style="background: #22c55e; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">🏠</div>',
      iconSize: [36, 36],
      iconAnchor: [18, 18],
    })
  }).addTo(viewMap).bindPopup("Punto de entrega");

  // Línea
  L.polyline([
    [viewingDelivery.value.pickup_location.lat, viewingDelivery.value.pickup_location.lng],
    [viewingDelivery.value.delivery_location.lat, viewingDelivery.value.delivery_location.lng]
  ], {
    color: '#3b82f6',
    weight: 4,
    opacity: 0.7
  }).addTo(viewMap);

  // Ajustar vista
  const bounds = L.latLngBounds([
    [viewingDelivery.value.pickup_location.lat, viewingDelivery.value.pickup_location.lng],
    [viewingDelivery.value.delivery_location.lat, viewingDelivery.value.delivery_location.lng]
  ]);
  viewMap.fitBounds(bounds, { padding: [30, 30] });

  setTimeout(() => viewMap.invalidateSize(), 100);
}

function resetForm() {
  form.value = {
    customer_name: "",
    customer_phone: "",
    delivery_address: "",
    pickup_lat: null,
    pickup_lng: null,
    delivery_lat: null,
    delivery_lng: null,
    courier_id: "",
    notes: "",
  };
  formErrors.value = {};
  clearMapMarkers();
}

function closeModal() {
  showCreateModal.value = false;
  editingDelivery.value = null;
  resetForm();

  if (deliveryMap) {
    deliveryMap.remove();
    deliveryMap = null;
  }
}

function exportToCSV() {
  const headers = ['ID', 'Cliente', 'Teléfono', 'Dirección', 'Mensajero', 'Estado', 'Distancia (km)', 'Asignada'];
  const csvData = deliveries.value.map(delivery => [
    delivery.id,
    delivery.customer_name,
    delivery.customer_phone,
    delivery.delivery_address,
    delivery.courier?.name || 'Sin asignar',
    statusText(delivery.status),
    delivery.distance_km,
    delivery.assigned_at ? formatDate(delivery.assigned_at) : ''
  ]);

  const csvContent = [headers, ...csvData]
    .map(row => row.map(field => `"${field}"`).join(','))
    .join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `entregas_${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Inicialización
onMounted(() => {
  loadDeliveries();
  loadCouriers();
});
</script>

<style scoped>
.delivery-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1rem;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
}

.header-section h2 {
  margin: 0;
  color: #1f2937;
  font-size: 1.75rem;
}

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

.delivery-id {
  font-weight: 600;
  color: #3b82f6;
}

.customer-info strong {
  display: block;
  margin-bottom: 0.25rem;
}

.customer-phone {
  font-size: 0.875rem;
  color: #6b7280;
}

.delivery-address {
  max-width: 250px;
}

.delivery-notes {
  font-size: 0.75rem;
  color: #f59e0b;
  background: #fef3c7;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  margin-top: 0.25rem;
  display: inline-block;
  cursor: help;
}

.courier-assigned strong {
  display: block;
  margin-bottom: 0.25rem;
}

.courier-status {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 8px;
  display: inline-block;
}

.courier-status.available {
  background: #d1fae5;
  color: #065f46;
}

.courier-status.busy {
  background: #fef3c7;
  color: #92400e;
}

.no-courier {
  color: #9ca3af;
  font-style: italic;
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

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.assigned {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.picked_up {
  background: #f3e8ff;
  color: #6b21a8;
}

.status-badge.in_transit {
  background: #e0e7ff;
  color: #4338ca;
}

.status-badge.delivered {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.distance {
  text-align: center;
  font-weight: 600;
  color: #1f2937;
}

.date-info {
  text-align: center;
}

.date-assigned {
  font-size: 0.875rem;
  color: #374151;
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

.btn-icon:hover {
  background: #f3f4f6;
  transform: scale(1.1);
}

.btn-icon.btn-danger:hover {
  background: #fee2e2;
  color: #dc2626;
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

.modal-large {
  max-width: 900px;
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
.delivery-form {
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
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.courier-select {
  width: 100%;
}

.selected-courier-info {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #f0f9ff;
  border-radius: 4px;
  border-left: 3px solid #3b82f6;
  font-size: 0.875rem;
}

.help-text {
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.text-primary {
  color: #3b82f6;
  font-weight: 600;
}

.text-success {
  color: #22c55e;
  font-weight: 600;
}

.text-muted {
  color: #9ca3af;
}

.location-controls {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.creation-map {
  height: 400px;
  border-radius: 8px;
  margin-bottom: 1rem;
  border: 1px solid #e5e7eb;
}

.view-map {
  height: 400px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.distance-info {
  padding: 0.75rem;
  background: #f0f9ff;
  border-radius: 6px;
  border-left: 3px solid #3b82f6;
  font-size: 0.875rem;
}

.delivery-map-info {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1.5rem;
}

.delivery-details {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.delivery-details p {
  margin: 0.5rem 0;
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

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

/* Assign Courier Modal */
.assign-courier-list {
  max-height: 400px;
  overflow-y: auto;
}

.courier-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.courier-item:hover {
  border-color: #3b82f6;
  background: #f0f9ff;
}

.courier-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #3b82f6;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.courier-details {
  flex: 1;
}

.courier-details strong {
  display: block;
  margin-bottom: 0.25rem;
}

.courier-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.courier-status.available {
  color: #065f46;
  font-weight: 600;
}

.courier-action {
  font-size: 1.25rem;
}

.no-couriers {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

/* Button Styles */
.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

  .delivery-map-info {
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
}
</style>
