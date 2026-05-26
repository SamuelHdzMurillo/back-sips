<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    public function index(): JsonResponse
    {
        $empleados = Empleado::all();
        return response()->json($empleados);
    }

    /**
     * Total de registros en el catálogo (evita GET /empleados/{no} con no="count" → error).
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'total' => Empleado::query()->count(),
        ]);
    }

    public function showMe(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->empleado_no) {
            return response()->json(['message' => 'Tu cuenta no está vinculada a ningún empleado.'], 404);
        }

        $empleado = Empleado::with(['perfil.estudios', 'familiares', 'plazas'])->findOrFail($user->empleado_no);

        return response()->json($empleado);
    }

    /**
     * Tabla de antigüedad por plantel(es) del empleado autenticado.
     * Si el usuario está adscrito a varios planteles, devuelve una tabla por cada uno.
     */
    public function tablaAntiguedadMiPlantel(): JsonResponse
    {
        $user = Auth::user();

        if (!$user?->empleado_no) {
            return response()->json([
                'message' => 'Tu cuenta no está vinculada a ningún empleado.',
            ], 404);
        }

        $plantelesDelUsuario = DB::table('empleado_plazas')
            ->select('EMPLEADO_CCT_CLAVE', 'EMPLEADO_CCT_NOMBRE')
            ->where('EMPLEADO_NO', $user->empleado_no)
            ->whereNotNull('EMPLEADO_CCT_CLAVE')
            ->distinct()
            ->get();

        if ($plantelesDelUsuario->isEmpty()) {
            return response()->json([
                'message' => 'No se encontró adscripción de plantel para el empleado autenticado.',
                'planteles' => [],
            ], 404);
        }

        $hoy = Carbon::now();
        $tablas = $plantelesDelUsuario->map(function ($plantel) use ($hoy) {
            $personas = DB::table('empleados as e')
                ->join('empleado_plazas as ep', 'ep.EMPLEADO_NO', '=', 'e.EMPLEADO_NO')
                ->where('ep.EMPLEADO_CCT_CLAVE', $plantel->EMPLEADO_CCT_CLAVE)
                ->select(
                    'e.EMPLEADO_NO',
                    'e.EMPLEADO_APELLIDO_PATERNO',
                    'e.EMPLEADO_APELLIDO_MATERNO',
                    'e.EMPLEADO_NOMBRE',
                    'e.EMPLEADO_FECHA_INGRESO',
                    'e.EMPLEADO_ANTIGUEDAD',
                    'ep.EMPLEADO_PUESTO',
                    'ep.EMPLEADO_CATEGORIA',
                    'ep.EMPLEADO_FUNCION'
                )
                ->distinct('e.EMPLEADO_NO')
                ->orderBy('e.EMPLEADO_APELLIDO_PATERNO')
                ->orderBy('e.EMPLEADO_APELLIDO_MATERNO')
                ->orderBy('e.EMPLEADO_NOMBRE')
                ->get()
                ->map(function ($persona) use ($hoy) {
                    $fechaIngreso = $persona->EMPLEADO_FECHA_INGRESO
                        ? Carbon::parse($persona->EMPLEADO_FECHA_INGRESO)
                        : null;

                    $antiguedadAnios = $fechaIngreso
                        ? $fechaIngreso->diffInYears($hoy)
                        : (is_numeric($persona->EMPLEADO_ANTIGUEDAD) ? (int) $persona->EMPLEADO_ANTIGUEDAD : null);

                    return [
                        'empleado_no' => (int) $persona->EMPLEADO_NO,
                        'nombre_completo' => trim(
                            "{$persona->EMPLEADO_APELLIDO_PATERNO} {$persona->EMPLEADO_APELLIDO_MATERNO} {$persona->EMPLEADO_NOMBRE}"
                        ),
                        'fecha_ingreso' => $fechaIngreso?->toDateString(),
                        'antiguedad_anios' => $antiguedadAnios,
                        'puesto' => $persona->EMPLEADO_PUESTO,
                        'categoria' => $persona->EMPLEADO_CATEGORIA,
                        'funcion' => $persona->EMPLEADO_FUNCION,
                    ];
                })
                ->sortByDesc('antiguedad_anios')
                ->values();

            return [
                'clave_plantel' => $plantel->EMPLEADO_CCT_CLAVE,
                'nombre_plantel' => $plantel->EMPLEADO_CCT_NOMBRE,
                'total_personas' => $personas->count(),
                'tabla_antiguedad' => $personas,
            ];
        })->values();

        return response()->json([
            'empleado_no_autenticado' => (int) $user->empleado_no,
            'planteles' => $tablas,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'EMPLEADO_NO'                  => 'required|integer|unique:empleados,EMPLEADO_NO',
            'EMPLEADO_APELLIDO_PATERNO'    => 'required|string|max:100',
            'EMPLEADO_APELLIDO_MATERNO'    => 'required|string|max:100',
            'EMPLEADO_NOMBRE'              => 'required|string|max:100',
            'EMPLEADO_CURP'                => 'nullable|string|max:20',
            'EMPLEADO_RFC'                 => 'nullable|string|max:20',
            'EMPLEADO_NSS'                 => 'nullable|string|max:20',
            'EMPLEADO_TIPO_SANGRE'         => 'nullable|string|max:5',
            'EMPLEADO_FECHA_INGRESO'       => 'nullable|date',
            'EMPLEADO_ANTIGUEDAD'          => 'nullable|integer',
            'EMPLEADO_ACTIVO'              => 'nullable|string|size:1',
            'EMPLEADO_CORREO_ELECTRONICO'  => 'nullable|email|max:150',
            'EMPLEADO_CLAVE_ACCESO'        => 'nullable|string|max:255',
            'EMPLEADO_RUTA_FOTO'           => 'nullable|string|max:255',
            'EMPLEADO_RUTA_QR'             => 'nullable|string|max:255',
            'EMPLEADO_ULTIMO_INGRESO'      => 'nullable|date',
        ]);

        if (isset($data['EMPLEADO_CLAVE_ACCESO'])) {
            $data['EMPLEADO_CLAVE_ACCESO'] = Hash::make($data['EMPLEADO_CLAVE_ACCESO']);
        }

        $empleado = Empleado::create($data);

        return response()->json($empleado, 201);
    }

    /**
     * El segmento de ruta llega como string; (int) de '' o 'undefined' da 0 y rompe findOrFail.
     */
    private function empleadoNoDesdeRuta(int|string $no): ?int
    {
        if (is_int($no)) {
            return $no > 0 ? $no : null;
        }
        $s = trim((string) $no);
        if ($s === '' || !ctype_digit($s)) {
            return null;
        }
        $value = (int) $s;

        return $value > 0 ? $value : null;
    }

    public function show(int|string $no): JsonResponse
    {
        $no = $this->empleadoNoDesdeRuta($no);
        if ($no === null) {
            return response()->json([
                'message' => 'Número de empleado inválido. Usa un entero mayor que cero o GET /empleados/me para el empleado vinculado a tu cuenta.',
            ], 422);
        }
        $empleado = Empleado::with(['perfil.estudios', 'familiares', 'plazas'])->findOrFail($no);
        return response()->json($empleado);
    }

    public function update(Request $request, int|string $no): JsonResponse
    {
        $no = $this->empleadoNoDesdeRuta($no);
        if ($no === null) {
            return response()->json([
                'message' => 'Número de empleado inválido. Usa un entero mayor que cero.',
            ], 422);
        }
        $empleado = Empleado::findOrFail($no);

        $data = $request->validate([
            'EMPLEADO_APELLIDO_PATERNO'    => 'sometimes|string|max:100',
            'EMPLEADO_APELLIDO_MATERNO'    => 'sometimes|string|max:100',
            'EMPLEADO_NOMBRE'              => 'sometimes|string|max:100',
            'EMPLEADO_CURP'                => 'nullable|string|max:20',
            'EMPLEADO_RFC'                 => 'nullable|string|max:20',
            'EMPLEADO_NSS'                 => 'nullable|string|max:20',
            'EMPLEADO_TIPO_SANGRE'         => 'nullable|string|max:5',
            'EMPLEADO_FECHA_INGRESO'       => 'nullable|date',
            'EMPLEADO_ANTIGUEDAD'          => 'nullable|integer',
            'EMPLEADO_ACTIVO'              => 'nullable|string|size:1',
            'EMPLEADO_CORREO_ELECTRONICO'  => 'nullable|email|max:150',
            'EMPLEADO_CLAVE_ACCESO'        => 'nullable|string|max:255',
            'EMPLEADO_RUTA_FOTO'           => 'nullable|string|max:255',
            'EMPLEADO_RUTA_QR'             => 'nullable|string|max:255',
            'EMPLEADO_ULTIMO_INGRESO'      => 'nullable|date',
        ]);

        if (isset($data['EMPLEADO_CLAVE_ACCESO'])) {
            $data['EMPLEADO_CLAVE_ACCESO'] = Hash::make($data['EMPLEADO_CLAVE_ACCESO']);
        }

        $empleado->update($data);

        return response()->json($empleado);
    }

    public function destroy(int|string $no): JsonResponse
    {
        $no = $this->empleadoNoDesdeRuta($no);
        if ($no === null) {
            return response()->json([
                'message' => 'Número de empleado inválido. Usa un entero mayor que cero.',
            ], 422);
        }
        $empleado = Empleado::findOrFail($no);
        $empleado->delete();

        return response()->json(['message' => 'Empleado eliminado correctamente']);
    }
}
