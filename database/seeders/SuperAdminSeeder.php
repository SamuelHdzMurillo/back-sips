<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@sips.com'],
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@sips.com',
                'password' => bcrypt('Admin@1234!'),
                'role'     => 'superadmin',
            ]
        );

        $this->command->info('SuperAdmin creado: superadmin@sips.com / Admin@1234!');
        $this->command->warn('IMPORTANTE: Cambia la contraseña del superadmin en producción.');
    }
}
