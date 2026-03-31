<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'empleados',       'label' => 'Empleados',           'description' => 'Gestión del catálogo de empleados'],
            ['name' => 'perfil',          'label' => 'Perfil Profesional',  'description' => 'Perfil y datos profesionales del empleado'],
            ['name' => 'familiares',      'label' => 'Familiares',          'description' => 'Gestión de familiares del empleado'],
            ['name' => 'plazas',          'label' => 'Plazas',              'description' => 'Plazas asignadas al empleado'],
            ['name' => 'estudios',        'label' => 'Estudios',            'description' => 'Estudios académicos del empleado'],
            ['name' => 'solicitud-lentes','label' => 'Solicitud de Lentes', 'description' => 'Solicitudes de lentes de los empleados'],
            ['name' => 'lotes-lentes',    'label' => 'Lotes de Lentes',     'description' => 'Gestión de lotes de lentes'],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['name' => $module['name']], $module);
        }
    }
}
