<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Courier extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'user_id',
        'phone',
        'status',
        'vehicle_type',
        'vehicle_plate',
        'current_location',
        'max_capacity',
        'is_active',
        'last_location_update',
        'speed'
    ];

    protected $casts = [
        'current_location' => Point::class,
        'last_location_update' => 'datetime',
        'is_active' => 'boolean',
        'max_capacity' => 'integer',
        'speed' => 'decimal:2'
    ];

    protected $attributes = [
        'status' => 'offline',
        'is_active' => true,
        'max_capacity' => 5
    ];

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->active();
    }

    public function scopeBusy($query)
    {
        return $query->where('status', 'busy')->active();
    }

    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function locationHistory()
    {
        return $this->hasMany(LocationHistory::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function activeDeliveries()
    {
        return $this->deliveries()->whereIn('status', ['assigned', 'picked_up', 'in_transit']);
    }

    // Métodos de negocio
    public function updateLocation($lat, $lng, $speed = null, $accuracy = null)
    {
        $point = new Point($lat, $lng);

        $this->update([
            'current_location' => $point,
            'last_location_update' => now(),
            'speed' => $speed
        ]);

        // Guardar en historial si existe la tabla
        if (class_exists('LocationHistory')) {
            $this->locationHistory()->create([
                'location' => $point,
                'recorded_at' => now(),
                'speed' => $speed,
                'accuracy' => $accuracy,
            ]);
        }

        return $this;
    }

    public function canAcceptDelivery()
    {
        return $this->status === 'available' &&
               $this->is_active &&
               $this->activeDeliveries()->count() < $this->max_capacity;
    }

    public function getPerformanceStats()
    {
        return [
            'total_deliveries' => $this->deliveries()->count(),
            'completed_today' => $this->deliveries()
                ->where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
            'active_deliveries' => $this->activeDeliveries()->count(),
            'completion_rate' => $this->deliveries()->count() > 0 ?
                round(($this->deliveries()->where('status', 'delivered')->count() / $this->deliveries()->count()) * 100, 2) : 0
        ];
    }

    public static function findNearby($lat, $lng, $radiusKm = 5)
    {
        return self::available()
            ->whereNotNull('current_location')
            ->selectRaw("
                *,
                ST_Distance(
                    current_location::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) / 1000 as distance_km
            ", [$lng, $lat])
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->get();
    }
}
