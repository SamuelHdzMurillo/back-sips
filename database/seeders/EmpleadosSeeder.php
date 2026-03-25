<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpleadosSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/Sabana_Periodo_04_2026.csv');

        $handle = fopen($csvPath, 'r');

        // Leer encabezado y normalizar codificación
        $header = array_map(
            fn($v) => mb_convert_encoding($v, 'UTF-8', 'Windows-1252'),
            fgetcsv($handle)
        );

        $empleadosInsertados = [];
        $empleados           = [];
        $plazas              = [];

        while (($row = fgetcsv($handle)) !== false) {
            // Convertir cada campo de Windows-1252 a UTF-8
            $row  = array_map(fn($v) => mb_convert_encoding($v, 'UTF-8', 'Windows-1252'), $row);
            $data = array_combine($header, $row);

            $no = (int) $data['EMPLEADO_NO'];

            // Acumular datos de empleados (solo insertar una vez por EMPLEADO_NO)
            if (! isset($empleadosInsertados[$no])) {
                $empleadosInsertados[$no] = true;

                $empleados[] = [
                    'EMPLEADO_NO'                 => $no,
                    'EMPLEADO_APELLIDO_PATERNO'   => $data['EMPLEADO_APELLIDO_PATERNO'],
                    'EMPLEADO_APELLIDO_MATERNO'   => $data['EMPLEADO_APELLIDO_MATERNO'],
                    'EMPLEADO_NOMBRE'             => $data['EMPLEADO_NOMBRE'],
                    'EMPLEADO_CURP'               => $this->nullIfPlaceholder($data['EMPLEADO_CURP']),
                    'EMPLEADO_RFC'                => $this->nullIfPlaceholder($data['EMPLEADO_RFC']),
                    'EMPLEADO_NSS'                => $this->nullIfPlaceholder($data['EMPLEADO_NSS']),
                    'EMPLEADO_TIPO_SANGRE'        => $this->nullIfEmpty($data['EMPLEADO_TIPO_SANGRE']),
                    'EMPLEADO_FECHA_INGRESO'      => $this->parseDate($data['EMPLEADO_FECHA_INGRESO']),
                    'EMPLEADO_ANTIGUEDAD'         => $this->nullIfEmpty($data['EMPLEADO_ANTIGUEDAD']) !== null
                                                        ? (int) $data['EMPLEADO_ANTIGUEDAD']
                                                        : null,
                    'EMPLEADO_ACTIVO'             => $this->nullIfPlaceholder($data['EMPLEADO_ACTIVO']),
                    'EMPLEADO_CORREO_ELECTRONICO' => $this->nullIfPlaceholder($data['EMPLEADO_CORREO_ELECTRONICO']),
                    'EMPLEADO_CLAVE_ACCESO'       => $this->nullIfPlaceholder($data['EMPLEADO_CLAVE_ACCESO']),
                    'EMPLEADO_RUTA_FOTO'          => $this->nullIfPlaceholder($data['EMPLEADO_RUTA_FOTO']),
                    'EMPLEADO_RUTA_QR'            => $this->nullIfPlaceholder($data['EMPLEADO_RUTA_QR']),
                    'EMPLEADO_ULTIMO_INGRESO'     => $this->nullIfPlaceholder($data['EMPLEADO_ULTIMO_INGRESO']),
                ];
            }

            // Siempre insertar la plaza (puede haber doble plaza por empleado)
            $plazas[] = [
                'EMPLEADO_NO'         => $no,
                'EMPLEADO_CCT_CLAVE'  => $this->nullIfEmpty($data['EMPLEADO_CCT_CLAVE']),
                'EMPLEADO_CCT_NOMBRE' => $this->nullIfEmpty($data['EMPLEADO_CCT_NOMBRE']),
                'EMPLEADO_PUESTO'     => $this->nullIfEmpty($data['EMPLEADO_PUESTO']),
                'EMPLEADO_CATEGORIA'  => $this->nullIfEmpty($data['EMPLEADO_CATEGORIA']),
                'EMPLEADO_FUNCION'    => $this->nullIfEmpty($data['EMPLEADO_FUNCION']),
                'EMPLEADO_TIPO_PLAZA' => $this->nullIfEmpty($data['EMPLEADO_TIPO_PLAZA']),
                'HORAS'               => isset($data['HORAS']) && $data['HORAS'] !== ''
                                            ? (int) $data['HORAS']
                                            : null,
            ];
        }

        fclose($handle);

        // Insertar en lotes
        foreach (array_chunk($empleados, 100) as $chunk) {
            DB::table('empleados')->insertOrIgnore($chunk);
        }

        foreach (array_chunk($plazas, 100) as $chunk) {
            DB::table('empleado_plazas')->insert($chunk);
        }

        $this->command->info(count($empleados) . ' empleados insertados.');
        $this->command->info(count($plazas) . ' plazas insertadas.');
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === 'X') {
            return null;
        }

        // Formato DD/MM/YYYY → YYYY-MM-DD
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }

    private function nullIfEmpty(string $value): ?string
    {
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    /** Trata "X", "x", "" y "01010101010" (NSS genérico) como null */
    private function nullIfPlaceholder(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || strtoupper($trimmed) === 'X') {
            return null;
        }
        return $trimmed;
    }
}
