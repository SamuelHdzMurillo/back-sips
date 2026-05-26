<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConvocatoriasSeeder extends Seeder
{
    public function run(): void
    {
        $hoy = now()->toDateString();

        $convocatorias = [
            [
                'FECHA_INICIO'           => '2026-05-01',
                'FECHA_FIN'              => '2026-06-30',
                'DENOMINACION'           => 'Docente de Educación Secundaria — Matemáticas',
                'NIVEL_SALARIAL'         => 'Nivel 3',
                'ESTATUS_PLAZA'          => 'VACANTE',
                'SUELDO'                 => 18500.00,
                'LUGAR_ADSCRIPCION'      => 'Secundaria Técnica No. 12, Xalapa',
                'TURNO'                  => 'MATUTINO',
                'LOCALIDAD'              => 'ESTATAL',
                'CONVOCATORIA_RUTA_FOTO' => null,
                'ESTATUS'                => 'ABIERTA',
                'CREATED_AT'             => now(),
                'UPDATED_AT'             => now(),
            ],
            [
                'FECHA_INICIO'           => '2026-05-15',
                'FECHA_FIN'              => '2026-07-15',
                'DENOMINACION'           => 'Administrativo — Control Escolar',
                'NIVEL_SALARIAL'         => 'Nivel 2',
                'ESTATUS_PLAZA'          => 'VACANTE',
                'SUELDO'                 => 14200.00,
                'LUGAR_ADSCRIPCION'      => 'Telesecundaria 456, Coatepec',
                'TURNO'                  => 'VESPERTINO',
                'LOCALIDAD'              => 'LOCAL',
                'CONVOCATORIA_RUTA_FOTO' => null,
                'ESTATUS'                => 'ABIERTA',
                'CREATED_AT'             => now(),
                'UPDATED_AT'             => now(),
            ],
            [
                'FECHA_INICIO'           => '2026-04-01',
                'FECHA_FIN'              => '2026-04-30',
                'DENOMINACION'           => 'Docente de Primaria — Campo',
                'NIVEL_SALARIAL'         => 'Nivel 2',
                'ESTATUS_PLAZA'          => 'VACANTE',
                'SUELDO'                 => 12800.00,
                'LUGAR_ADSCRIPCION'      => 'Primaria Rural, Zongolica',
                'TURNO'                  => 'MATUTINO',
                'LOCALIDAD'              => 'DESIERTA',
                'CONVOCATORIA_RUTA_FOTO' => null,
                'ESTATUS'                => 'CERRADA',
                'CREATED_AT'             => '2026-04-01 08:00:00',
                'UPDATED_AT'             => '2026-05-01 08:00:00',
            ],
            [
                'FECHA_INICIO'           => '2026-03-01',
                'FECHA_FIN'              => '2026-03-31',
                'DENOMINACION'           => 'Director de Plantel',
                'NIVEL_SALARIAL'         => 'Nivel 4',
                'ESTATUS_PLAZA'          => 'RESERVADA',
                'SUELDO'                 => 22000.00,
                'LUGAR_ADSCRIPCION'      => 'Secundaria General No. 8, Veracruz',
                'TURNO'                  => 'MIXTO',
                'LOCALIDAD'              => 'ESTATAL',
                'CONVOCATORIA_RUTA_FOTO' => null,
                'ESTATUS'                => 'CERRADA',
                'CREATED_AT'             => '2026-03-01 08:00:00',
                'UPDATED_AT'             => '2026-04-01 08:00:00',
            ],
            [
                'FECHA_INICIO'           => '2026-06-01',
                'FECHA_FIN'              => '2026-08-31',
                'DENOMINACION'           => 'Orientador Educativo',
                'NIVEL_SALARIAL'         => 'Nivel 3',
                'ESTATUS_PLAZA'          => 'VACANTE',
                'SUELDO'                 => 16500.00,
                'LUGAR_ADSCRIPCION'      => 'Preparatoria Estatal No. 3, Orizaba',
                'TURNO'                  => 'MATUTINO',
                'LOCALIDAD'              => 'LOCAL',
                'CONVOCATORIA_RUTA_FOTO' => null,
                'ESTATUS'                => 'ABIERTA',
                'CREATED_AT'             => now(),
                'UPDATED_AT'             => now(),
            ],
        ];

        foreach ($convocatorias as &$convocatoria) {
            if ($convocatoria['FECHA_FIN'] < $hoy) {
                $convocatoria['ESTATUS'] = 'CERRADA';
            }
        }
        unset($convocatoria);

        DB::table('convocatorias')->insert($convocatorias);

        $this->command->info('Convocatorias de ejemplo insertadas: ' . count($convocatorias));
    }
}
