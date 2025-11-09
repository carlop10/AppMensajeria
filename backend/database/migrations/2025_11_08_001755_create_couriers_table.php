<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone');
            $table->string('vehicle_type')->nullable(); // motocicleta, bicicleta, carro
            $table->string('vehicle_plate')->nullable();
            $table->enum('status', ['available', 'busy', 'offline'])->default('offline');
            $table->boolean('is_active')->default(true);
            $table->integer('max_capacity')->default(5);
            $table->timestamp('last_location_update')->nullable();
            $table->decimal('speed', 5, 2)->nullable(); // velocidad en km/h
            $table->geometry('current_location', subtype: 'point', srid: 4326)->nullable();
            $table->timestamps();

            $table->index(['status', 'is_active']);
            $table->index('last_location_update');
        });

        // Índice espacial para búsquedas rápidas
        DB::statement('CREATE INDEX couriers_location_idx ON couriers USING GIST (current_location)');
    }

    public function down()
    {
        Schema::dropIfExists('couriers');
    }
};
