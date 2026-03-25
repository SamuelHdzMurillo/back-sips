<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\PerfilProfesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioController extends Controller
{
    public function indexByPerfil(int $perfilId): JsonResponse
    {
        PerfilProfesional::findOrFail($perfilId);
        $estudios = Estudio::where('PERFIL_ID', $perfilId)->get();

        return response()->json($estudios);
    }

    public function storeByPerfil(Request $request, int $perfilId): JsonResponse
    {
        PerfilProfesional::findOrFail($perfilId);

        $data = $request->validate([
            'NIVEL'       => 'nullable|string|max:50',
            'CARRERA'     => 'nullable|string|max:150',
            'INSTITUCION' => 'nullable|string|max:150',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN'    => 'nullable|date|after_or_equal:FECHA_INICIO',
            'DOCUMENTO'   => 'nullable|string|max:255',
        ]);

        $data['PERFIL_ID'] = $perfilId;

        $estudio = Estudio::create($data);

        return response()->json($estudio, 201);
    }

    public function show(int $id): JsonResponse
    {
        $estudio = Estudio::findOrFail($id);
        return response()->json($estudio);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $estudio = Estudio::findOrFail($id);

        $data = $request->validate([
            'NIVEL'        => 'nullable|string|max:50',
            'CARRERA'      => 'nullable|string|max:150',
            'INSTITUCION'  => 'nullable|string|max:150',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN'    => 'nullable|date',
            'DOCUMENTO'    => 'nullable|string|max:255',
        ]);

        $estudio->update($data);

        return response()->json($estudio);
    }

    public function destroy(int $id): JsonResponse
    {
        $estudio = Estudio::findOrFail($id);
        $estudio->delete();

        return response()->json(['message' => 'Estudio eliminado correctamente']);
    }
}
