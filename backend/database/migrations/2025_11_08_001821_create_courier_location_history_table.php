<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('courier_location_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained()->onDelete('cascade');
            $table->geometry('location', subtype: 'point', srid: 4326);
            $table->timestamp('recorded_at');
            $table->decimal('speed', 5, 2)->nullable(); // km/h
            $table->decimal('accuracy', 8, 2)->nullable(); // metros
            $table->timestamps();


            $table->index(['courier_id', 'recorded_at']);
            $table->index('recorded_at');
        });

        // Índice espacial para búsquedas geográficas
        DB::statement('CREATE INDEX courier_location_history_location_idx ON courier_location_history USING GIST (location)');
    }

    public function down()
    {
        Schema::dropIfExists('courier_location_history');
    }
};
