<?php
// app/Console/Commands/UpdateCourierLocations.php
namespace App\Console\Commands;

use App\Models\Courier;
use Illuminate\Console\Command;
use MatanYadaev\EloquentSpatial\Objects\Point;

class UpdateCourierLocations extends Command
{
    protected $signature = 'couriers:update-locations';
    protected $description = 'Actualiza las ubicaciones de los mensajeros activos';

    public function handle()
    {
        $activeCouriers = Courier::where('is_active', true)
            ->whereIn('status', ['available', 'busy'])
            ->get();

        $barranquillaLocations = [
            // Centro de Barranquilla y alrededores
            new Point(10.9878, -74.7889),  // Centro
            new Point(11.0075, -74.8240),  // Riomar
            new Point(10.9589, -74.7973),  // Suroeste
            new Point(10.9812, -74.8336),  // Metropolitano
            new Point(10.9945, -74.7956),  // El Prado
            new Point(11.0123, -74.8156),  // Villa Carolina
            new Point(10.9521, -74.8023),  // Las Nieves
            new Point(10.9756, -74.8289),  // La Paz
        ];

        $updated = 0;

        foreach ($activeCouriers as $courier) {
            // Pequeña variación en la ubicación actual o nueva ubicación aleatoria
            if ($courier->current_location) {
                // Mover ligeramente desde la ubicación actual
                $lat = $courier->current_location->latitude + (rand(-20, 20) / 10000);
                $lng = $courier->current_location->longitude + (rand(-20, 20) / 10000);
            } else {
                // Ubicación aleatoria
                $randomLocation = $barranquillaLocations[array_rand($barranquillaLocations)];
                $lat = $randomLocation->latitude + (rand(-10, 10) / 10000);
                $lng = $randomLocation->longitude + (rand(-10, 10) / 10000);
            }

            // Asegurar que las coordenadas estén dentro de Barranquilla
            $lat = max(10.90, min(11.02, $lat));
            $lng = max(-74.85, min(-74.75, $lng));

            $courier->update([
                'current_location' => new Point($lat, $lng),
                'last_location_update' => now(),
                'speed' => rand(0, 40), // km/h
            ]);

            $updated++;

            // Guardar en historial
            if (class_exists('LocationHistory')) {
                $courier->locationHistory()->create([
                    'location' => new Point($lat, $lng),
                    'recorded_at' => now(),
                    'speed' => rand(0, 40),
                    'accuracy' => rand(5, 25),
                ]);
            }
        }

        $this->info("✅ {$updated} ubicaciones de mensajeros actualizadas");
        return 0;
    }
}
