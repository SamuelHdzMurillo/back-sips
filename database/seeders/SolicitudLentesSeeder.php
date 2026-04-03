<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SolicitudLentesSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Empleados/plazas disponibles ──────────────────────────────────
        $plazas = DB::table('empleado_plazas')
            ->select('ID', 'EMPLEADO_NO')
            ->limit(30)
            ->get();

        if ($plazas->isEmpty()) {
            $this->command->warn('No hay plazas registradas. Ejecuta EmpleadosSeeder primero.');
            return;
        }

        // ── 2. Crear lotes ───────────────────────────────────────────────────
        $lotes = [
            [
                'NOMBRE'      => 'Lote Enero 2026',
                'DESCRIPCION' => 'Solicitudes recibidas durante enero 2026',
                'FECHA_INICIO' => '2026-01-01',
                'FECHA_FIN'   => '2026-01-31',
                'ESTATUS'     => 'CERRADO',
                'created_at'  => '2026-01-01 00:00:00',
                'updated_at'  => '2026-02-01 00:00:00',
            ],
            [
                'NOMBRE'      => 'Lote Febrero 2026',
                'DESCRIPCION' => 'Solicitudes recibidas durante febrero 2026',
                'FECHA_INICIO' => '2026-02-01',
                'FECHA_FIN'   => '2026-02-28',
                'ESTATUS'     => 'CERRADO',
                'created_at'  => '2026-02-01 00:00:00',
                'updated_at'  => '2026-03-01 00:00:00',
            ],
            [
                'NOMBRE'      => 'Lote Marzo 2026',
                'DESCRIPCION' => 'Solicitudes recibidas durante marzo 2026',
                'FECHA_INICIO' => '2026-03-01',
                'FECHA_FIN'   => '2026-03-31',
                'ESTATUS'     => 'ABIERTO',
                'created_at'  => '2026-03-01 00:00:00',
                'updated_at'  => '2026-03-01 00:00:00',
            ],
        ];

        DB::table('lotes_lentes')->insert($lotes);

        // Recuperar los IDs recién insertados indexados por nombre de mes
        $loteEnero  = DB::table('lotes_lentes')->where('NOMBRE', 'Lote Enero 2026')->value('ID');
        $loteFebrero = DB::table('lotes_lentes')->where('NOMBRE', 'Lote Febrero 2026')->value('ID');
        $loteMarzo  = DB::table('lotes_lentes')->where('NOMBRE', 'Lote Marzo 2026')->value('ID');

        $this->command->info('3 lotes de lentes creados.');

        // ── 3. Datos variados para las solicitudes ────────────────────────────
        $recetas = [
            ['REC-2026-001', null],
            ['REC-2026-002', 'recetas/rec_002.pdf'],
            [null,           'recetas/rec_003.pdf'],
            ['REC-2026-004', null],
            [null,           null],
        ];

        $observaciones = [
            'Solicitud de graduación alta',
            'Requiere lentes bifocales',
            'Segunda solicitud del año',
            'Lentes de seguridad para laboratorio',
            null,
            'Actualización de receta',
            null,
        ];

        /*
         * Cada entrada: [fecha, lote_id, estatus]
         * - Lotes CERRADO (enero/febrero) → solicitudes ya resueltas (APROBADA / RECHAZADA)
         * - Lote  ABIERTO (marzo)         → solicitudes en proceso   (PENDIENTE / APROBADA)
         */
        $config = [
            ['2026-01-10 09:00:00', $loteEnero,   'APROBADA'],
            ['2026-01-15 10:30:00', $loteEnero,   'RECHAZADA'],
            ['2026-01-22 08:45:00', $loteEnero,   'APROBADA'],
            ['2026-02-03 11:00:00', $loteFebrero,  'APROBADA'],
            ['2026-02-14 09:20:00', $loteFebrero,  'RECHAZADA'],
            ['2026-02-20 14:00:00', $loteFebrero,  'APROBADA'],
            ['2026-03-05 08:00:00', $loteMarzo,   'PENDIENTE'],
            ['2026-03-12 10:15:00', $loteMarzo,   'PENDIENTE'],
            ['2026-03-18 13:30:00', $loteMarzo,   'APROBADA'],
            ['2026-03-25 09:45:00', $loteMarzo,   'PENDIENTE'],
            ['2026-03-28 11:00:00', $loteMarzo,   'PENDIENTE'],
            ['2026-03-30 08:30:00', $loteMarzo,   'PENDIENTE'],
        ];

        // ── 4. Construir registros ────────────────────────────────────────────
        $registros = [];

        foreach ($plazas as $index => $plaza) {
            [$fecha, $loteId, $estatus] = $config[$index % count($config)];
            $receta = $recetas[$index % count($recetas)];
            $obs    = $observaciones[$index % count($observaciones)];

            $registros[] = [
                'LOTE_ID'             => $loteId,
                'EMPLEADO_NO'         => $plaza->EMPLEADO_NO,
                'PLAZA_ID'            => $plaza->ID,
                'FAMILIAR_ID'         => null,
                'RECETA_ISTE_NUMERO'       => $receta[0],
                'RECETA_ISTE_ARCHIVO'      => $receta[1],
                'OPTICA_NOMBRE'            => null,
                'FACTURA_COMPRA_ARCHIVO'   => null,
                'ESTATUS'                  => $estatus,
                'OBSERVACIONES'       => $obs,
                'CREATED_AT'          => $fecha,
            ];
        }

        foreach (array_chunk($registros, 50) as $chunk) {
            DB::table('solicitud_lentes')->insert($chunk);
        }

        $this->command->info(count($registros) . ' solicitudes de lentes insertadas.');

        // ── 5. Resumen por lote ───────────────────────────────────────────────
        $resumenLote = collect($registros)
            ->groupBy('LOTE_ID')
            ->map->count();

        $nombreLotes = [
            $loteEnero   => 'Lote Enero 2026',
            $loteFebrero => 'Lote Febrero 2026',
            $loteMarzo   => 'Lote Marzo 2026',
        ];

        foreach ($resumenLote as $id => $total) {
            $nombre = $nombreLotes[$id] ?? "Lote #{$id}";
            $this->command->line("  - {$nombre}: {$total} solicitudes");
        }

        $resumenEstatus = collect($registros)->groupBy('ESTATUS')->map->count();
        $this->command->line('');
        $this->command->line('  Por estatus:');
        foreach ($resumenEstatus as $est => $total) {
            $this->command->line("    · {$est}: {$total}");
        }
    }
}
