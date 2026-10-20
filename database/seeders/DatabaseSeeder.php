<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Cuentas por defecto del sistema.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@restauranager.com'],
            [
                'name' => 'Administrador',
                'password' => 'admin123',
                'role' => 'admin',
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'mesero@restauranager.com'],
            [
                'name' => 'Mesero Demo',
                'password' => 'mesero123',
                'role' => 'user',
                'active' => true,
            ]
        );

        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
