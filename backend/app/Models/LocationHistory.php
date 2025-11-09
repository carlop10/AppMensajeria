<?php

// app/Models/LocationHistory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class LocationHistory extends Model
{
    use HasSpatial;

    protected $table = 'location_history';
    public $timestamps = false;

    protected $fillable = ['courier_id', 'location', 'recorded_at', 'speed', 'accuracy'];

    protected $casts = [
        'location' => Point::class,
        'recorded_at' => 'datetime',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
