<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->nullable()->constrained();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('delivery_address');
            $table->geometry('delivery_location', subtype: 'point', srid: 4326);
            $table->geometry('pickup_location', subtype: 'point', srid: 4326);
            $table->enum('status', [
                'pending',
                'assigned',
                'picked_up',
                'in_transit',
                'delivered',
                'cancelled'
            ])->default('pending');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            // Agregar campos para mejor tracking
            $table->decimal('estimated_distance', 8, 2)->nullable(); // km
            $table->decimal('actual_distance', 8, 2)->nullable(); // km
            $table->integer('priority')->default(1); // 1-5, mayor número = mayor prioridad

            $table->timestamps();


            // Índices para mejor performance
            $table->index(['status', 'courier_id']);
            $table->index('priority');
            $table->index('created_at');
        });

        DB::statement('CREATE INDEX deliveries_delivery_location_idx ON deliveries USING GIST (delivery_location)');
        DB::statement('CREATE INDEX deliveries_pickup_location_idx ON deliveries USING GIST (pickup_location)');
    }

    public function down()
    {
        Schema::dropIfExists('deliveries');
    }
};
