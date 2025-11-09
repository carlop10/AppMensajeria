<?php
// database/seeders/UserSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Usuarios administradores
        User::create([
            'name' => 'Administrador Principal',
            'email' => 'admin@deliveryapp.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@deliveryapp.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Usuarios regulares (no mensajeros)
        User::create([
            'name' => 'Ana García',
            'email' => 'ana@ejemplo.com',
            'password' => Hash::make('12345678'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Luis Fernández',
            'email' => 'luis@ejemplo.com',
            'password' => Hash::make('12345678'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Crear usuarios que serán mensajeros (se crearán en CourierSeeder)
        $courierUsers = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan@deliveryapp.com',
                'password' => Hash::make('12345678'),
                'role' => 'courier',
            ],
            [
                'name' => 'María Rodríguez',
                'email' => 'maria@deliveryapp.com',
                'password' => Hash::make('12345678'),
                'role' => 'courier',
            ],
            [
                'name' => 'Pedro López',
                'email' => 'pedro@deliveryapp.com',
                'password' => Hash::make('12345678'),
                'role' => 'courier',
            ],
            [
                'name' => 'Laura Martínez',
                'email' => 'laura@deliveryapp.com',
                'password' => Hash::make('12345678'),
                'role' => 'courier',
            ],
            [
                'name' => 'Diego Silva',
                'email' => 'diego@deliveryapp.com',
                'password' => Hash::make('12345678'),
                'role' => 'courier',
            ],
        ];

        foreach ($courierUsers as $userData) {
            User::create(array_merge($userData, [
                'email_verified_at' => now(),
            ]));
        }

        $this->command->info('✅ Usuarios creados exitosamente');
        $this->command->info('👑 Administradores: admin@deliveryapp.com / 12345678');
        $this->command->info('🏍️ Mensajeros: juan@deliveryapp.com / 12345678');
        $this->command->info('👤 Usuarios regulares: ana@ejemplo.com / 12345678');
    }
}
