# Sistema de Gestión de Entregas - Documentación Completa

## 📋 Descripción General

Sistema completo de gestión de entregas desarrollado con **Laravel (Backend)** y **Vue.js + Leaflet (Frontend)**. Permite la administración de mensajeros, seguimiento en tiempo real, gestión de entregas y usuarios.

## 🚀 Características Principales

### 🗺️ Módulo de Mapa en Tiempo Real
- **Seguimiento activo** de mensajeros con ubicación GPS
- **Iconos personalizados** por estado y tipo de vehículo
- **Filtros avanzados** por estado y vehículo
- **Actualización automática** cada 30 segundos
- **Panel lateral** con información detallada de mensajeros

### 👥 Gestión de Usuarios
- **Tres roles**: Administrador, Mensajero, Usuario regular
- **CRUD completo** de usuarios
- **Conversión** de usuarios a mensajeros
- **Estadísticas** en tiempo real
- **Exportación** a CSV

### 🏍️ Gestión de Mensajeros
- **Registro completo** con información de vehículos
- **Seguimiento de capacidad** de entregas
- **Métricas de rendimiento**
- **Control de estados** (Disponible, Ocupado, Desconectado)

### 📦 Gestión de Entregas
- **Creación con mapa interactivo**
- **Asignación manual/automática** de mensajeros
- **Seguimiento de estados** (Pendiente, Asignada, Recogida, En tránsito, Entregada)
- **Cálculo automático** de distancias
- **Notas y detalles** específicos por entrega

### 📱 App del Mensajero
- **Interfaz móvil optimizada**
- **Actualización de ubicación** en tiempo real
- **Gestión de entregas asignadas**
- **Cambio de estados** en tiempo real
- **Mapa integrado** con rutas

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 10+** - Framework PHP
- **PostgreSQL/MySQL** - Base de datos
- **PostGIS** - Extensiones geoespaciales
- **JWT** - Autenticación
- **Eloquent Spatial** - Manejo de datos geoespaciales

### Frontend
- **Vue.js 3** - Framework JavaScript
- **Vue Router** - Navegación
- **Leaflet** - Mapas interactivos
- **Axios** - Cliente HTTP
- **CSS3** - Estilos y responsive design

## 📁 Estructura del Proyecto

```
delivery-system/
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── CourierController.php
│   │   │   ├── UserController.php
│   │   │   ├── DeliveryController.php
│   │   │   └── CourierTrackingController.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Courier.php
│   │   │   └── Delivery.php
│   │   └── ...
│   └── database/
│       ├── migrations/
│       └── ...
├── frontend/
│   ├── src/
│   │   ├── views/
│   │   │   ├── MapView.vue
│   │   │   ├── UserManagement.vue
│   │   │   ├── CourierManagement.vue
│   │   │   ├── DeliveryManagement.vue
│   │   │   ├── CourierTracker.vue
│   │   │   └── LoginView.vue
│   │   ├── api/
│   │   │   └── axios.js
│   │   └── ...
│   └── ...
└── README.md
```

## 🗃️ Modelos de Base de Datos

### Users
```php
- id, name, email, password, role, is_active, last_login, created_at, updated_at
```

### Couriers
```php
- id, user_id, phone, vehicle_type, vehicle_plate, status, is_active, 
  max_capacity, current_location (Point), last_location_update, speed, created_at
```

### Deliveries
```php
- id, customer_name, customer_phone, delivery_address, 
  pickup_location (Point), delivery_location (Point),
  status, courier_id, notes, assigned_at, picked_up_at, delivered_at
```

## 🔌 API Endpoints Principales

### Autenticación
- `POST /login` - Iniciar sesión
- `GET /user` - Obtener usuario actual

### Usuarios
- `GET /users` - Listar usuarios
- `POST /users` - Crear usuario
- `PUT /users/{id}` - Actualizar usuario
- `DELETE /users/{id}` - Eliminar usuario

### Mensajeros
- `GET /couriers` - Listar mensajeros
- `POST /couriers` - Crear mensajero
- `PUT /couriers/{id}` - Actualizar mensajero
- `POST /couriers/{id}/location` - Actualizar ubicación
- `GET /couriers/stats` - Estadísticas

### Entregas
- `GET /deliveries` - Listar entregas
- `POST /deliveries` - Crear entrega
- `PUT /deliveries/{id}` - Actualizar entrega
- `PATCH /deliveries/{id}/status` - Cambiar estado
- `GET /my-deliveries` - Entregas del mensajero

## 🚀 Instalación y Configuración

### Prerrequisitos
- PHP 8.1+
- Composer
- Node.js 16+
- PostgreSQL/MySQL con PostGIS
- Servidor web (Apache/Nginx)

### Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=delivery_system
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

php artisan migrate
php artisan db:seed
php artisan serve
```

### Frontend (Vue.js)
```bash
cd frontend
npm install
cp .env.example .env

# Configurar API endpoint
VITE_API_URL=http://localhost:8000/api

npm run dev
```

## 🎨 Características de la Interfaz

### Diseño Responsive
- **Mobile-first** approach
- **Grid systems** flexibles
- **Componentes modulares**
- **Iconografía consistente**

### Experiencia de Usuario
- **Loading states** en todas las operaciones
- **Manejo de errores** contextual
- **Confirmaciones** para acciones destructivas
- **Notificaciones** de éxito/error

### Mapas Interactivos
- **OpenStreetMap** como base
- **Marcadores personalizados** con colores por estado
- **Líneas de ruta** entre puntos
- **Popups informativos**
- **Geolocalización** del navegador

## 🔒 Seguridad

### Autenticación
- **JWT Tokens** para autenticación
- **Middleware de auth** en rutas protegidas
- **Protección por roles** (Admin, Courier, User)

### Validación
- **Validación de datos** en backend
- **Sanitización** de inputs
- **Protección SQL injection** con Eloquent

## 📊 Métricas y Estadísticas

### En Tiempo Real
- **Total de mensajeros** por estado
- **Entregas activas** vs completadas
- **Rendimiento** por mensajero
- **Tiempos promedio** de entrega

### Históricas
- **Entregas por período** (día, semana, mes)
- **Mensajeros más eficientes**
- **Zonas de mayor actividad**

## 🚨 Manejo de Errores

### Frontend
- **Interceptores de Axios** para errores globales
- **Mensajes contextuales** al usuario
- **Reintentos automáticos** para peticiones fallidas

### Backend
- **Try-catch blocks** en controladores
- **Respuestas estandarizadas** de error
- **Logging** de excepciones

## 🔄 Flujos de Trabajo

### Asignación de Entrega
1. Administrador crea entrega con ubicaciones
2. Sistema calcula distancia automáticamente
3. Asignación manual o automática a mensajero disponible
4. Mensajero recibe notificación y actualiza estados

### Seguimiento de Mensajero
1. Mensajero inicia sesión en app móvil
2. GPS envía ubicación periodica
3. Administrador ve ubicación en tiempo real en mapa
4. Estados se actualizan automáticamente

## 📱 Responsive Design

### Breakpoints
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

### Características Mobile
- **Menús colapsables**
- **Mapas a pantalla completa**
- **Gestos táctiles** en mapas
- **Formularios optimizados**

## 🛠️ Desarrollo y Contribución

### Convenciones de Código
- **Vue 3 Composition API**
- **Laravel Eloquent best practices**
- **PSR-12** para PHP
- **ESLint** para JavaScript

### Pruebas
```bash
# Backend tests
php artisan test

# Frontend tests
npm run test
```

## 📈 Escalabilidad

### Arquitectura
- **Separación clara** frontend/backend
- **APIs RESTful** stateless
- **Base de datos** optimizada para consultas geoespaciales

### Optimizaciones
- **Paginación** en listas largas
- **Caching** de datos estáticos
- **Lazy loading** de componentes
- **Compresión** de assets

## 🌟 Características Únicas

### Para Administradores
- **Vista 360°** de toda la operación
- **Asignación inteligente** de mensajeros
- **Reportes exportables**
- **Gestión multi-usuario**

### Para Mensajeros
- **Interfaz simplificada** y móvil
- **Navegación integrada**
- **Actualización sin esfuerzo** de estados
- **Historial personal**

---

## 📞 Soporte y Contacto

Para issues, preguntas o contribuciones, por favor contactar al equipo de desarrollo.

---

