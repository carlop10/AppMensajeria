<?php
// app/Http/Controllers/DeliveryController.php
namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Courier;
use Illuminate\Http\Request;
use MatanYadaev\EloquentSpatial\Objects\Point;

class DeliveryController extends Controller
{
    // Listar todas las entregas
    public function index(Request $request)
    {
        $query = Delivery::with('courier.user');

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por mensajero
        if ($request->has('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        $deliveries = $query->latest()->get()->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'customer_name' => $delivery->customer_name,
                'customer_phone' => $delivery->customer_phone,
                'delivery_address' => $delivery->delivery_address,
                'status' => $delivery->status,
                'courier' => $delivery->courier ? [
                    'id' => $delivery->courier->id,
                    'name' => $delivery->courier->name,
                ] : null,
                'pickup_location' => [
                    'lat' => $delivery->pickup_location->latitude,
                    'lng' => $delivery->pickup_location->longitude,
                ],
                'delivery_location' => [
                    'lat' => $delivery->delivery_location->latitude,
                    'lng' => $delivery->delivery_location->longitude,
                ],
                'distance_km' => $delivery->calculateDistance(),
                'assigned_at' => $delivery->assigned_at?->toIso8601String(),
                'picked_up_at' => $delivery->picked_up_at?->toIso8601String(),
                'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                'notes' => $delivery->notes,
                'created_at' => $delivery->created_at->toIso8601String(),
            ];
        });

        return response()->json($deliveries);
    }

    // Crear nueva entrega
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'delivery_lat' => 'required|numeric|between:-90,90',
            'delivery_lng' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string',
            'courier_id' => 'nullable|exists:couriers,id',
        ]);

        $delivery = Delivery::create([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'delivery_address' => $validated['delivery_address'],
            'pickup_location' => new Point($validated['pickup_lat'], $validated['pickup_lng'], 4326),
            'delivery_location' => new Point($validated['delivery_lat'], $validated['delivery_lng'], 4326),
            'notes' => $validated['notes'] ?? null,
            'courier_id' => $validated['courier_id'] ?? null,
            'status' => isset($validated['courier_id']) ? 'assigned' : 'pending',
            'assigned_at' => isset($validated['courier_id']) ? now() : null,
        ]);

        // Si se asignó un mensajero, cambiar su estado a busy
        if ($delivery->courier_id) {
            Courier::find($delivery->courier_id)->update(['status' => 'busy']);
        }

        return response()->json([
            'message' => 'Entrega creada exitosamente',
            'delivery' => $delivery->fresh(['courier.user']),
        ], 201);
    }

    // Ver una entrega específica
    public function show(Delivery $delivery)
    {
        $delivery->load('courier.user');

        return response()->json([
            'id' => $delivery->id,
            'customer_name' => $delivery->customer_name,
            'customer_phone' => $delivery->customer_phone,
            'delivery_address' => $delivery->delivery_address,
            'status' => $delivery->status,
            'courier' => $delivery->courier ? [
                'id' => $delivery->courier->id,
                'name' => $delivery->courier->name,
                'phone' => $delivery->courier->phone,
            ] : null,
            'pickup_location' => [
                'lat' => $delivery->pickup_location->latitude,
                'lng' => $delivery->pickup_location->longitude,
            ],
            'delivery_location' => [
                'lat' => $delivery->delivery_location->latitude,
                'lng' => $delivery->delivery_location->longitude,
            ],
            'distance_km' => $delivery->calculateDistance(),
            'assigned_at' => $delivery->assigned_at?->toIso8601String(),
            'picked_up_at' => $delivery->picked_up_at?->toIso8601String(),
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'notes' => $delivery->notes,
            'created_at' => $delivery->created_at->toIso8601String(),
        ]);
    }

    // Actualizar entrega
    public function update(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'customer_name' => 'sometimes|string|max:255',
            'customer_phone' => 'sometimes|string|max:20',
            'delivery_address' => 'sometimes|string',
            'notes' => 'nullable|string',
            'courier_id' => 'nullable|exists:couriers,id',
        ]);

        // Si se cambia el mensajero
        if (isset($validated['courier_id']) && $validated['courier_id'] !== $delivery->courier_id) {
            // Liberar mensajero anterior
            if ($delivery->courier_id) {
                $oldCourier = Courier::find($delivery->courier_id);
                $hasOtherDeliveries = Delivery::where('courier_id', $oldCourier->id)
                    ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                    ->where('id', '!=', $delivery->id)
                    ->exists();

                if (!$hasOtherDeliveries) {
                    $oldCourier->update(['status' => 'available']);
                }
            }

            // Asignar nuevo mensajero
            if ($validated['courier_id']) {
                Courier::find($validated['courier_id'])->update(['status' => 'busy']);
                $validated['assigned_at'] = now();
                $validated['status'] = 'assigned';
            }
        }

        $delivery->update($validated);

        return response()->json([
            'message' => 'Entrega actualizada exitosamente',
            'delivery' => $delivery->fresh(['courier.user']),
        ]);
    }

    // Eliminar entrega
    public function destroy(Delivery $delivery)
    {
        // Liberar mensajero si estaba asignado
        if ($delivery->courier_id) {
            $courier = $delivery->courier;
            $hasOtherDeliveries = Delivery::where('courier_id', $courier->id)
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->where('id', '!=', $delivery->id)
                ->exists();

            if (!$hasOtherDeliveries) {
                $courier->update(['status' => 'available']);
            }
        }

        $delivery->delete();

        return response()->json([
            'message' => 'Entrega eliminada exitosamente',
        ]);
    }

    // Cambiar estado de la entrega (usado por mensajeros)
    public function updateStatus(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'status' => 'required|in:assigned,picked_up,in_transit,delivered,cancelled',
        ]);

        $updates = ['status' => $validated['status']];

        // Actualizar timestamps según el estado
        switch ($validated['status']) {
            case 'picked_up':
                $updates['picked_up_at'] = now();
                break;
            case 'delivered':
                $updates['delivered_at'] = now();

                // Liberar mensajero si no tiene más entregas activas
                $courier = $delivery->courier;
                if ($courier) {
                    $hasOtherDeliveries = Delivery::where('courier_id', $courier->id)
                        ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                        ->where('id', '!=', $delivery->id)
                        ->exists();

                    if (!$hasOtherDeliveries) {
                        $courier->update(['status' => 'available']);
                    }
                }
                break;
        }

        $delivery->update($updates);

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'delivery' => $delivery->fresh(['courier.user']),
        ]);
    }

    // Obtener entregas del mensajero autenticado
    public function myDeliveries(Request $request)
    {
        $user = $request->user();

        if (!$user->courier) {
            return response()->json(['error' => 'Usuario no es un mensajero'], 403);
        }

        $deliveries = Delivery::where('courier_id', $user->courier->id)
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->latest()
            ->get()
            ->map(function ($delivery) {
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
                    'distance_km' => $delivery->calculateDistance(),
                    'assigned_at' => $delivery->assigned_at?->toIso8601String(),
                    'picked_up_at' => $delivery->picked_up_at?->toIso8601String(),
                    'notes' => $delivery->notes,
                ];
            });

        return response()->json($deliveries);
    }
}
