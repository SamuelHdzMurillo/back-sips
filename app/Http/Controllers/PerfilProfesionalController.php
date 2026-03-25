<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\PerfilProfesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerfilProfesionalController extends Controller
{
    public function showByEmpleado(int $no): JsonResponse
    {
        $empleado = Empleado::findOrFail($no);
        $perfil = $empleado->perfil()->with('estudios')->firstOrFail();

        return response()->json($perfil);
    }

    public function storeByEmpleado(Request $request, int $no): JsonResponse
    {
        Empleado::findOrFail($no);

        $data = $request->validate([
            'PERFIL_DESCRIPCION' => 'nullable|string',
        ]);

        $data['EMPLEADO_NO'] = $no;

        $perfil = PerfilProfesional::create($data);

        return response()->json($perfil, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $perfil = PerfilProfesional::findOrFail($id);

        $data = $request->validate([
            'PERFIL_DESCRIPCION' => 'nullable|string',
        ]);

        $perfil->update($data);

        return response()->json($perfil);
    }

    public function destroy(int $id): JsonResponse
    {
        $perfil = PerfilProfesional::findOrFail($id);
        $perfil->delete();

        return response()->json(['message' => 'Perfil profesional eliminado correctamente']);
    }
}
