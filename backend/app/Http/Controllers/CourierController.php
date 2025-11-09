<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Courier;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourierController extends Controller
{
    public function index(Request $request)
    {
        $query = Courier::with(['user', 'activeDeliveries']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $couriers = $query->get()->map(function ($courier) {
            $stats = $courier->getPerformanceStats();

            return [
                'id' => $courier->id,
                'name' => $courier->user->name,
                'email' => $courier->user->email,
                'phone' => $courier->phone,
                'vehicle_type' => $courier->vehicle_type,
                'vehicle_plate' => $courier->vehicle_plate,
                'status' => $courier->status,
                'is_active' => $courier->is_active,
                'max_capacity' => $courier->max_capacity,
                'current_load' => $courier->activeDeliveries->count(),
                'performance' => $stats,
                'current_location' => $courier->current_location ? [
                    'lat' => $courier->current_location->latitude,
                    'lng' => $courier->current_location->longitude,
                ] : null,
                'last_location_update' => $courier->last_location_update?->toISOString(),
                'speed' => $courier->speed,
                'created_at' => $courier->created_at->toISOString(),
            ];
        });

        return response()->json($couriers);
    }

    public function show($id)
    {
        $courier = Courier::with(['user', 'deliveries' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }])->findOrFail($id);

        $stats = $courier->getPerformanceStats();

        return response()->json([
            'courier' => $courier,
            'performance_stats' => $stats,
            'current_location' => $courier->current_location ? [
                'lat' => $courier->current_location->latitude,
                'lng' => $courier->current_location->longitude,
            ] : null
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'vehicle_type' => 'required|in:motocicleta,bicicleta,carro,caminando',
            'vehicle_plate' => 'nullable|string|max:20',
            'max_capacity' => 'nullable|integer|min:1|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        return DB::transaction(function () use ($validated) {
            // Crear usuario
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'courier',
            ]);

            // Crear mensajero
            $courier = Courier::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'],
                'vehicle_type' => $validated['vehicle_type'],
                'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                'max_capacity' => $validated['max_capacity'] ?? 5,
                'status' => 'offline',
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Mensajero creado exitosamente',
                'courier' => $courier->load('user'),
            ], 201);
        });
    }

    public function update(Request $request, $id)
    {
        $courier = Courier::with('user')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($courier->user_id)
            ],
            'phone' => 'sometimes|string|max:20',
            'vehicle_type' => 'sometimes|in:motocicleta,bicicleta,carro,caminando',
            'vehicle_plate' => 'nullable|string|max:20',
            'max_capacity' => 'sometimes|integer|min:1|max:20',
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|in:available,busy,offline',
        ]);

        return DB::transaction(function () use ($courier, $validated) {
            // Actualizar usuario
            if (isset($validated['name']) || isset($validated['email'])) {
                $userData = [];
                if (isset($validated['name'])) $userData['name'] = $validated['name'];
                if (isset($validated['email'])) $userData['email'] = $validated['email'];

                $courier->user->update($userData);
            }

            // Actualizar mensajero
            $courierData = array_filter([
                'phone' => $validated['phone'] ?? null,
                'vehicle_type' => $validated['vehicle_type'] ?? null,
                'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                'max_capacity' => $validated['max_capacity'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
                'status' => $validated['status'] ?? null,
            ]);

            if (!empty($courierData)) {
                $courier->update($courierData);
            }

            return response()->json([
                'message' => 'Mensajero actualizado exitosamente',
                'courier' => $courier->fresh(['user', 'activeDeliveries']),
            ]);
        });
    }

    public function destroy($id)
    {
        $courier = Courier::with('user')->findOrFail($id);

        DB::transaction(function () use ($courier) {
            // Soft delete del mensajero
            $courier->update(['is_active' => false]);

            // Opcional: también desactivar el usuario
            $courier->user->update(['is_active' => false]);
        });

        return response()->json([
            'message' => 'Mensajero desactivado exitosamente'
        ]);
    }

    public function stats()
    {
        $stats = [
            'total' => Courier::count(),
            'active' => Courier::active()->count(),
            'available' => Courier::available()->count(),
            'busy' => Courier::busy()->count(),
            'offline' => Courier::where('status', 'offline')->active()->count(),
        ];

        return response()->json($stats);
    }

    public function getCouriersWithLocation(Request $request)
    {
        $query = Courier::with(['user', 'activeDeliveries'])
            ->where('is_active', true)
            ->whereNotNull('current_location');

        // Filtrar por estado si se especifica
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtrar por tipo de vehículo si se especifica
        if ($request->has('vehicle_type') && $request->vehicle_type !== 'all') {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        $couriers = $query->get()->map(function ($courier) {
            return [
                'id' => $courier->id,
                'user' => [
                    'id' => $courier->user->id,
                    'name' => $courier->user->name,
                    'email' => $courier->user->email,
                ],
                'phone' => $courier->phone,
                'vehicle_type' => $courier->vehicle_type,
                'vehicle_plate' => $courier->vehicle_plate,
                'status' => $courier->status,
                'is_active' => $courier->is_active,
                'current_location' => $courier->current_location ? [
                    'lat' => $courier->current_location->latitude,
                    'lng' => $courier->current_location->longitude,
                ] : null,
                'last_location_update' => $courier->last_location_update?->toISOString(),
                'speed' => $courier->speed,
                'active_deliveries_count' => $courier->activeDeliveries->count(),
                'max_capacity' => $courier->max_capacity,
            ];
        });

        return response()->json($couriers);
    }
}
