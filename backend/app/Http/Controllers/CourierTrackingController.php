<?php
// app/Http/Controllers/CourierTrackingController.php
namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourierTrackingController extends Controller
{
    // Actualizar ubicación del mensajero
    public function updateLocation(Request $request, Courier $courier)
    {
        // Verificar que el mensajero esté autenticado
        if ($request->user()->id !== $courier->user_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($courier, $validated) {
                // Actualizar ubicación actual
                $courier->update([
                    'current_location' => DB::raw("ST_SetSRID(ST_MakePoint({$validated['lng']}, {$validated['lat']}), 4326)"),
                    'last_location_update' => now(),
                    'speed' => $validated['speed'] ?? null,
                ]);

                // Guardar en historial (si tienes la tabla location_history)
                if (Schema::hasTable('courier_location_history')) {
                    DB::table('courier_location_history')->insert([
                        'courier_id' => $courier->id,
                        'location' => DB::raw("ST_SetSRID(ST_MakePoint({$validated['lng']}, {$validated['lat']}), 4326)"),
                        'speed' => $validated['speed'] ?? null,
                        'accuracy' => $validated['accuracy'] ?? null,
                        'recorded_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            return response()->json([
                'message' => 'Ubicación actualizada correctamente',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar ubicación',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Obtener entregas activas del mensajero con información de distancia
    public function getActiveDeliveries(Courier $courier)
    {
        $deliveries = Delivery::where('courier_id', $courier->id)
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->with(['customer'])
            ->get()
            ->map(function ($delivery) use ($courier) {
                $distanceToPickup = null;
                $distanceToDelivery = null;

                if ($courier->current_location) {
                    // Calcular distancia a punto de recogida
                    $distanceToPickup = DB::selectOne("
                        SELECT ST_Distance(
                            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                            pickup_location::geography
                        ) as distance
                        FROM deliveries
                        WHERE id = ?
                    ", [
                        $courier->current_location->longitude,
                        $courier->current_location->latitude,
                        $delivery->id
                    ])->distance / 1000; // Convertir a km

                    // Calcular distancia a punto de entrega
                    $distanceToDelivery = DB::selectOne("
                        SELECT ST_Distance(
                            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                            delivery_location::geography
                        ) as distance
                        FROM deliveries
                        WHERE id = ?
                    ", [
                        $courier->current_location->longitude,
                        $courier->current_location->latitude,
                        $delivery->id
                    ])->distance / 1000;
                }

                return [
                    'id' => $delivery->id,
                    'customer_name' => $delivery->customer_name,
                    'customer_phone' => $delivery->customer_phone,
                    'delivery_address' => $delivery->delivery_address,
                    'status' => $delivery->status,
                    'pickup_location' => [
                        'lat' => $delivery->pickup_location->latitude,
                        'lng' => $delivery->pickup_location->longitude,
                    ],
                    'delivery_location' => [
                        'lat' => $delivery->delivery_location->latitude,
                        'lng' => $delivery->delivery_location->longitude,
                    ],
                    'distance_to_pickup_km' => round($distanceToPickup, 2),
                    'distance_to_delivery_km' => round($distanceToDelivery, 2),
                    'total_distance_km' => $delivery->calculateDistance(),
                    'notes' => $delivery->notes,
                    'assigned_at' => $delivery->assigned_at?->toISOString(),
                    'picked_up_at' => $delivery->picked_up_at?->toISOString(),
                ];
            });

        return response()->json($deliveries);
    }

    // Obtener estadísticas del mensajero
    public function getCourierStats(Courier $courier)
    {
        $stats = [
            'today' => [
                'delivered' => Delivery::where('courier_id', $courier->id)
                    ->where('status', 'delivered')
                    ->whereDate('delivered_at', today())
                    ->count(),
                'in_progress' => Delivery::where('courier_id', $courier->id)
                    ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                    ->count(),
                'total_distance' => Delivery::where('courier_id', $courier->id)
                    ->whereDate('created_at', today())
                    ->get()
                    ->sum('calculateDistance'),
            ],
            'weekly' => [
                'delivered' => Delivery::where('courier_id', $courier->id)
                    ->where('status', 'delivered')
                    ->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ]
        ];

        return response()->json($stats);
    }
}
