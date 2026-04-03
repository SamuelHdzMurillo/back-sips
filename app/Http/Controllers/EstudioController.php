<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\PerfilProfesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'NIVEL'        => 'nullable|string|max:50',
            'CARRERA'      => 'nullable|string|max:150',
            'INSTITUCION'  => 'nullable|string|max:150',
            'FECHA_INICIO' => 'nullable|date',
            'FECHA_FIN'    => 'nullable|date|after_or_equal:FECHA_INICIO',
            'DOCUMENTO'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $data['PERFIL_ID'] = $perfilId;

        if ($request->hasFile('DOCUMENTO')) {
            $path = $request->file('DOCUMENTO')
                ->store('documentos-estudios', 'public');
            $data['DOCUMENTO'] = Storage::disk('public')->url($path);
        }

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
            'DOCUMENTO'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('DOCUMENTO')) {
            if ($estudio->DOCUMENTO) {
                $oldPath = str_replace(Storage::disk('public')->url(''), '', $estudio->DOCUMENTO);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('DOCUMENTO')
                ->store('documentos-estudios', 'public');
            $data['DOCUMENTO'] = Storage::disk('public')->url($path);
        }

        $estudio->update($data);

        return response()->json($estudio);
    }

    public function destroy(int $id): JsonResponse
    {
        $estudio = Estudio::findOrFail($id);

        if ($estudio->DOCUMENTO) {
            $oldPath = str_replace(Storage::disk('public')->url(''), '', $estudio->DOCUMENTO);
            Storage::disk('public')->delete($oldPath);
        }

        $estudio->delete();

        return response()->json(['message' => 'Estudio eliminado correctamente']);
    }
}
