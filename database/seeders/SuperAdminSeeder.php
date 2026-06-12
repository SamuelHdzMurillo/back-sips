<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SUPERADMIN_PASSWORD');

        if (empty($password)) {
            $this->command->error('SUPERADMIN_PASSWORD no está definida en .env');
            return;
        }

        User::updateOrCreate(
            ['email' => 'superadmin@sips.com'],
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@sips.com',
                'password' => bcrypt($password),
                'role'     => 'superadmin',
            ]
        );

        $this->command->info('SuperAdmin creado/actualizado: superadmin@sips.com');
    }
}
