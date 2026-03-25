<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoPlaza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpleadoPlazaController extends Controller
{
    public function indexByEmpleado(int $no): JsonResponse
    {
        Empleado::findOrFail($no);
        $plazas = EmpleadoPlaza::where('EMPLEADO_NO', $no)->get();

        return response()->json($plazas);
    }

    public function storeByEmpleado(Request $request, int $no): JsonResponse
    {
        Empleado::findOrFail($no);

        $data = $request->validate([
            'EMPLEADO_CCT_CLAVE'   => 'nullable|string|max:20',
            'EMPLEADO_CCT_NOMBRE'  => 'nullable|string|max:150',
            'EMPLEADO_PUESTO'      => 'nullable|string|max:150',
            'EMPLEADO_CATEGORIA'   => 'nullable|string|max:100',
            'EMPLEADO_FUNCION'     => 'nullable|string|max:100',
            'EMPLEADO_TIPO_PLAZA'  => 'nullable|string|size:1',
            'HORAS'                => 'nullable|integer',
        ]);

        $data['EMPLEADO_NO'] = $no;

        $plaza = EmpleadoPlaza::create($data);

        return response()->json($plaza, 201);
    }

    public function show(int $id): JsonResponse
    {
        $plaza = EmpleadoPlaza::findOrFail($id);
        return response()->json($plaza);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plaza = EmpleadoPlaza::findOrFail($id);

        $data = $request->validate([
            'EMPLEADO_CCT_CLAVE'   => 'nullable|string|max:20',
            'EMPLEADO_CCT_NOMBRE'  => 'nullable|string|max:150',
            'EMPLEADO_PUESTO'      => 'nullable|string|max:150',
            'EMPLEADO_CATEGORIA'   => 'nullable|string|max:100',
            'EMPLEADO_FUNCION'     => 'nullable|string|max:100',
            'EMPLEADO_TIPO_PLAZA'  => 'nullable|string|size:1',
            'HORAS'                => 'nullable|integer',
        ]);

        $plaza->update($data);

        return response()->json($plaza);
    }

    public function destroy(int $id): JsonResponse
    {
        $plaza = EmpleadoPlaza::findOrFail($id);
        $plaza->delete();

        return response()->json(['message' => 'Plaza eliminada correctamente']);
    }
}
