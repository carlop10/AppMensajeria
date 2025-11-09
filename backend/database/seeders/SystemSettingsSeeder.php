<?php
// database/seeders/SystemSettingsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // Configuración general de la app
            [
                'key' => 'app_name',
                'value' => 'Mi App Delivery',
                'type' => 'string',
                'description' => 'Nombre de la aplicación',
            ],
            [
                'key' => 'app_timezone',
                'value' => 'America/Bogota',
                'type' => 'string',
                'description' => 'Zona horaria de la aplicación',
            ],
            [
                'key' => 'app_currency',
                'value' => 'COP',
                'type' => 'string',
                'description' => 'Moneda por defecto',
            ],

            // Configuración de mensajeros
            [
                'key' => 'max_deliveries_per_courier',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Número máximo de entregas por mensajero',
            ],
            [
                'key' => 'default_courier_capacity_motorcycle',
                'value' => '4',
                'type' => 'integer',
                'description' => 'Capacidad por defecto para motocicletas',
            ],
            [
                'key' => 'default_courier_capacity_car',
                'value' => '8',
                'type' => 'integer',
                'description' => 'Capacidad por defecto para carros',
            ],
            [
                'key' => 'default_courier_capacity_bicycle',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Capacidad por defecto para bicicletas',
            ],

            // Configuración de geolocalización
            [
                'key' => 'default_search_radius_km',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Radio de búsqueda por defecto en kilómetros',
            ],
            [
                'key' => 'location_update_interval',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Intervalo de actualización de ubicación en segundos',
            ],
            [
                'key' => 'max_location_history_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Días máximos para mantener historial de ubicaciones',
            ],

            // Configuración de entregas
            [
                'key' => 'auto_assign_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Habilitar asignación automática de entregas',
            ],
            [
                'key' => 'default_delivery_priority',
                'value' => '1',
                'type' => 'integer',
                'description' => 'Prioridad por defecto para nuevas entregas',
            ],
            [
                'key' => 'delivery_timeout_minutes',
                'value' => '120',
                'type' => 'integer',
                'description' => 'Tiempo máximo en minutos para completar una entrega',
            ],

            // Configuración de notificaciones
            [
                'key' => 'notify_new_delivery',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Notificar nuevas entregas a mensajeros',
            ],
            [
                'key' => 'notify_delivery_status',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Notificar cambios de estado en entregas',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('✅ Configuraciones del sistema creadas exitosamente');
        $this->command->info('⚙️ 15 configuraciones del sistema establecidas');
    }
}
