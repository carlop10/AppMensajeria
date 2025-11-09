<?php
// database/seeders/DeliverySeeder.php
namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\Courier;
use Illuminate\Database\Seeder;
use MatanYadaev\EloquentSpatial\Objects\Point;

class DeliverySeeder extends Seeder
{
    public function run()
    {
        // Puntos de referencia en Barranquilla para entregas
        $barranquillaPoints = [
            // Puntos de recogida (almacenes, tiendas)
            'pickup' => [
                new Point(10.9878, -74.7889),  // Centro
                new Point(11.0075, -74.8240),  // Riomar
                new Point(10.9589, -74.7973),  // Suroeste
                new Point(10.9812, -74.8336),  // Metropolitano
            ],
            // Puntos de entrega (residencias, oficinas)
            'delivery' => [
                new Point(10.9910, -74.7821),  // Barrio Abajo
                new Point(11.0123, -74.8156),  // Villa Carolina
                new Point(10.9521, -74.8023),  // Las Nieves
                new Point(10.9756, -74.8289),  // La Paz
                new Point(10.9945, -74.7956),  // El Prado
                new Point(11.0034, -74.8098),  // Altos del Prado
            ]
        ];

        $customerNames = [
            'María González', 'Carlos Rodríguez', 'Ana Martínez', 'Luis Hernández',
            'Laura Díaz', 'Pedro Sánchez', 'Carmen López', 'Javier Torres',
            'Isabel Ramírez', 'Miguel Flores', 'Elena Castro', 'Roberto Reyes',
            'Sofia Ortega', 'Daniel Mendoza', 'Patricia Vargas', 'Fernando Rojas'
        ];

        $customerPhones = [
            '+57 300 123 4567', '+57 301 234 5678', '+57 302 345 6789', '+57 303 456 7890',
            '+57 304 567 8901', '+57 305 678 9012', '+57 306 789 0123', '+57 307 890 1234'
        ];

        $deliveryAddresses = [
            'Calle 84 #45-65, Barrio El Prado',
            'Carrera 52 #72-123, Centro',
            'Calle 95 #44-32, Riomar',
            'Carrera 43 #76-89, Villa Carolina',
            'Calle 72 #33-21, Las Nieves',
            'Carrera 38 #65-12, La Paz',
            'Calle 88 #54-76, Altos del Prado',
            'Carrera 46 #82-34, Barrio Abajo'
        ];

        $statuses = ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled'];

        // Obtener mensajeros disponibles
        $couriers = Courier::where('is_active', true)->get();
        $availableCouriers = $couriers->where('status', 'available');
        $busyCouriers = $couriers->where('status', 'busy');

        // Crear entregas con diferentes estados
        $this->createDeliveries('pending', 8, $barranquillaPoints, $customerNames, $customerPhones, $deliveryAddresses);
        $this->createDeliveries('assigned', 6, $barranquillaPoints, $customerNames, $customerPhones, $deliveryAddresses, $availableCouriers);
        $this->createDeliveries('picked_up', 4, $barranquillaPoints, $customerNames, $customerPhones, $deliveryAddresses, $busyCouriers);
        $this->createDeliveries('in_transit', 3, $barranquillaPoints, $customerNames, $customerPhones, $deliveryAddresses, $busyCouriers);
        $this->createDeliveries('delivered', 12, $barranquillaPoints, $customerNames, $customerPhones, $deliveryAddresses, $couriers);
        $this->createDeliveries('cancelled', 2, $barranquillaPoints, $customerNames, $customerPhones, $deliveryAddresses);

        $this->command->info('✅ Entregas creadas exitosamente');
        $this->command->info('📦 35 entregas con diferentes estados');
        $this->command->info('📍 Ubicaciones realistas en Barranquilla');
    }

    private function createDeliveries($status, $count, $points, $names, $phones, $addresses, $couriers = null)
    {
        $notes = [
            null,
            'Entregar en portería',
            'Llamar antes de llegar',
            'Producto frágil',
            'No tocar timbre, solo llamar',
            'Entregar a nombre de recepción',
            'Paquete con documentos importantes'
        ];

        for ($i = 0; $i < $count; $i++) {
            $pickupLocation = $points['pickup'][array_rand($points['pickup'])];
            $deliveryLocation = $points['delivery'][array_rand($points['delivery'])];

            $deliveryData = [
                'customer_name' => $names[array_rand($names)],
                'customer_phone' => $phones[array_rand($phones)],
                'delivery_address' => $addresses[array_rand($addresses)],
                'pickup_location' => $pickupLocation,
                'delivery_location' => $deliveryLocation,
                'status' => $status,
                'notes' => $notes[array_rand($notes)],
                'estimated_distance' => $this->calculateDistance($pickupLocation, $deliveryLocation),
                'priority' => rand(1, 5),
                'created_at' => $this->getCreatedAtForStatus($status),
            ];

            // Asignar mensajero si corresponde
            if ($couriers && $couriers->count() > 0 && in_array($status, ['assigned', 'picked_up', 'in_transit', 'delivered'])) {
                $courier = $couriers->random();
                $deliveryData['courier_id'] = $courier->id;
                $deliveryData['assigned_at'] = now()->subHours(rand(1, 24));
            }

            // Agregar timestamps según el estado
            if (in_array($status, ['picked_up', 'in_transit', 'delivered'])) {
                $deliveryData['picked_up_at'] = now()->subHours(rand(1, 12));
            }

            if ($status === 'in_transit') {
                $deliveryData['actual_distance'] = $deliveryData['estimated_distance'] * (0.8 + (rand(0, 40) / 100));
            }

            if ($status === 'delivered') {
                $deliveryData['picked_up_at'] = now()->subHours(rand(2, 8));
                $deliveryData['delivered_at'] = now()->subHours(rand(1, 4));
                $deliveryData['actual_distance'] = $deliveryData['estimated_distance'] * (0.9 + (rand(0, 20) / 100));
            }

            Delivery::create($deliveryData);
        }
    }

    private function calculateDistance(Point $point1, Point $point2)
    {
        // Fórmula Haversine simplificada para distancia aproximada
        $lat1 = $point1->latitude;
        $lon1 = $point1->longitude;
        $lat2 = $point2->latitude;
        $lon2 = $point2->longitude;

        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return round($earthRadius * $c, 2);
    }

    private function getCreatedAtForStatus($status)
    {
        return match($status) {
            'pending' => now()->subHours(rand(1, 6)),
            'assigned' => now()->subHours(rand(2, 12)),
            'picked_up' => now()->subHours(rand(3, 24)),
            'in_transit' => now()->subHours(rand(4, 36)),
            'delivered' => now()->subDays(rand(1, 7)),
            'cancelled' => now()->subHours(rand(1, 48)),
            default => now()
        };
    }
}
