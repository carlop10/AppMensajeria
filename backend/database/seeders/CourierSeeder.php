<?php
// database/seeders/CourierSeeder.php
namespace Database\Seeders;

use App\Models\Courier;
use App\Models\User;
use Illuminate\Database\Seeder;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\DB;

class CourierSeeder extends Seeder
{
    public function run()
    {
        // Ubicaciones en Barranquilla, Colombia para pruebas
        $barranquillaLocations = [
            // Centro de Barranquilla
            new Point(10.9878, -74.7889),
            // Norte: Riomar
            new Point(11.0075, -74.8240),
            // Sur: Surieste
            new Point(10.9589, -74.7973),
            // Oeste: Metropolitano
            new Point(10.9812, -74.8336),
            // Este: Centro Histórico
            new Point(10.9846, -74.7765),
        ];

        $vehicleTypes = ['motocicleta', 'bicicleta', 'carro', 'caminando'];
        $statuses = ['available', 'busy', 'offline'];
        $vehiclePlates = ['ABC123', 'XYZ789', 'DEF456', 'GHI012', 'JKL345'];

        // Obtener usuarios con rol courier
        $courierUsers = User::where('role', 'courier')->get();

        foreach ($courierUsers as $index => $user) {
            $locationIndex = $index % count($barranquillaLocations);
            $vehicleType = $vehicleTypes[$index % count($vehicleTypes)];
            $status = $statuses[$index % count($statuses)];

            Courier::create([
                'user_id' => $user->id,
                'phone' => '+57 3' . rand(10, 99) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999),
                'vehicle_type' => $vehicleType,
                'vehicle_plate' => $vehiclePlates[$index] ?? null,
                'status' => $status,
                'current_location' => $barranquillaLocations[$locationIndex],
                'max_capacity' => $vehicleType === 'carro' ? 8 : ($vehicleType === 'motocicleta' ? 4 : 2),
                'is_active' => true,
                'last_location_update' => now()->subMinutes(rand(5, 120)),
                'speed' => $status === 'available' ? rand(0, 20) : ($status === 'busy' ? rand(10, 40) : 0),
            ]);
        }

        // Crear algunos datos de historial de ubicaciones
        $this->createLocationHistory();

        $this->command->info('✅ Mensajeros creados exitosamente');
        $this->command->info('🏍️ 5 mensajeros con diferentes estados y vehículos');
        $this->command->info('📍 Ubicaciones en Barranquilla, Colombia');
    }

    private function createLocationHistory()
    {
        $couriers = Courier::all();

        foreach ($couriers as $courier) {
            if (!$courier->current_location) continue;

            $baseLat = $courier->current_location->latitude;
            $baseLng = $courier->current_location->longitude;

            // Crear historial de las últimas 2 horas (1 punto cada 15 minutos)
            for ($i = 8; $i >= 0; $i--) {
                $time = now()->subMinutes($i * 15);

                // Pequeña variación en la ubicación para simular movimiento
                $lat = $baseLat + (rand(-50, 50) / 10000); // ±0.005 grados
                $lng = $baseLng + (rand(-50, 50) / 10000); // ±0.005 grados

                // Insertar directamente en la tabla
                \DB::table('courier_location_history')->insert([
                    'courier_id' => $courier->id,
                    'location' => \DB::raw("ST_SetSRID(ST_MakePoint($lng, $lat), 4326)"),
                    'recorded_at' => $time,
                    'speed' => rand(0, 40),
                    'accuracy' => rand(5, 25),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
