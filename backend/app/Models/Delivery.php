<?php

// app/Models/Delivery.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Delivery extends Model
{
    use HasSpatial;

    protected $fillable = [
        'courier_id', 'customer_name', 'customer_phone',
        'delivery_address', 'delivery_location', 'pickup_location',
        'status', 'assigned_at', 'picked_up_at', 'delivered_at', 'notes'
    ];

    protected $casts = [
        'delivery_location' => Point::class,
        'pickup_location' => Point::class,
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    // Calcular distancia entre pickup y delivery
    public function calculateDistance()
    {
        $pickup = $this->pickup_location;
        $delivery = $this->delivery_location;

        $result = \Illuminate\Support\Facades\DB::selectOne("
            SELECT ST_Distance(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) / 1000 as distance_km
        ", [$pickup->longitude, $pickup->latitude, $delivery->longitude, $delivery->latitude]);

        return round($result->distance_km, 2);
    }
}
