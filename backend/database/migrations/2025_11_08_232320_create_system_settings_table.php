<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSystemSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // // Insertar configuraciones por defecto
        // DB::table('system_settings')->insert([
        //     [
        //         'key' => 'app_name',
        //         'value' => 'Mi App Delivery',
        //         'type' => 'string',
        //         'description' => 'Nombre de la aplicación',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'key' => 'max_deliveries_per_courier',
        //         'value' => '5',
        //         'type' => 'integer',
        //         'description' => 'Número máximo de entregas por mensajero',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'key' => 'default_search_radius_km',
        //         'value' => '10',
        //         'type' => 'integer',
        //         'description' => 'Radio de búsqueda por defecto en kilómetros',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'key' => 'location_update_interval',
        //         'value' => '30',
        //         'type' => 'integer',
        //         'description' => 'Intervalo de actualización de ubicación en segundos',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]
        // ]);
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
}
