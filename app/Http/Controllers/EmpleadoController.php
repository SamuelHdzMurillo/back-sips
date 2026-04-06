<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
