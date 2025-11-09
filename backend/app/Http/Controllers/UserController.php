<?php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function me()
    {
        $user = auth()->user()->load(['courier' => function($query) {
            $query->withCount(['deliveries', 'activeDeliveries']);
        }]);

        return response()->json([
            'user' => $user,
            'permissions' => $this->getUserPermissions($user)
        ]);
    }

    public function index(Request $request)
    {
        $query = User::with('courier');

        // Filtros
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_display' => $user->role_display,
                'is_active' => $user->is_active ?? true,
                'last_login' => $user->last_login,
                'courier' => $user->courier ? [
                    'id' => $user->courier->id,
                    'phone' => $user->courier->phone,
                    'status' => $user->courier->status,
                    'vehicle_type' => $user->courier->vehicle_type,
                    'vehicle_plate' => $user->courier->vehicle_plate,
                ] : null,
                'created_at' => $user->created_at->toISOString(),
            ];
        });

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,courier,user',
            'phone' => 'required_if:role,courier|string|max:20',
            'vehicle_type' => 'required_if:role,courier|in:motocicleta,bicicleta,carro,caminando',
            'vehicle_plate' => 'nullable|string|max:20',
        ]);

        return DB::transaction(function () use ($validated) {
            // Crear usuario
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            // Si es mensajero, crear registro en couriers
            if ($validated['role'] === 'courier') {
                $user->courier()->create([
                    'phone' => $validated['phone'],
                    'vehicle_type' => $validated['vehicle_type'],
                    'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                    'status' => 'offline',
                    'is_active' => true,
                    'max_capacity' => 5,
                ]);
            }

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'user' => $user->load('courier')
            ], 201);
        });
    }

    public function show($id)
    {
        $user = User::with(['courier', 'courier.deliveries'])->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::with('courier')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'sometimes|in:admin,courier,user',
            'is_active' => 'sometimes|boolean',
            'phone' => 'sometimes|string|max:20',
            'vehicle_type' => 'sometimes|in:motocicleta,bicicleta,carro,caminando',
            'vehicle_plate' => 'nullable|string|max:20',
        ]);

        return DB::transaction(function () use ($user, $validated) {
            // Actualizar usuario
            $userData = array_filter([
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
                'role' => $validated['role'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
                'password' => isset($validated['password']) ?
                    Hash::make($validated['password']) : null,
            ]);

            $user->update($userData);

            // Si el rol cambió a courier y no tiene registro
            if (($validated['role'] ?? $user->role) === 'courier' && !$user->courier) {
                $user->courier()->create([
                    'phone' => $validated['phone'] ?? '',
                    'vehicle_type' => $validated['vehicle_type'] ?? 'motocicleta',
                    'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                    'status' => 'offline',
                    'is_active' => true,
                    'max_capacity' => 5,
                ]);
            }

            // Si tiene courier y se actualizó información
            if ($user->courier && (isset($validated['phone']) || isset($validated['vehicle_type']))) {
                $courierData = array_filter([
                    'phone' => $validated['phone'] ?? null,
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                    'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                ]);

                if (!empty($courierData)) {
                    $user->courier->update($courierData);
                }
            }

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'user' => $user->fresh(['courier'])
            ]);
        });
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // No permitir eliminar el propio usuario
        if ($user->id === auth()->id()) {
            return response()->json([
                'error' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }

    private function getUserPermissions($user)
    {
        if ($user->isAdmin()) {
            return ['users.read', 'users.write', 'couriers.read', 'couriers.write', 'deliveries.read', 'deliveries.write'];
        } elseif ($user->isCourier()) {
            return ['deliveries.read', 'deliveries.update_status', 'location.update'];
        } else {
            return ['profile.read', 'profile.update'];
        }
    }
}
