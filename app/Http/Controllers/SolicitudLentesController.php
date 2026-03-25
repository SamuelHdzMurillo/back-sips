<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\SolicitudLentes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudLentesController extends Controller
{
    public function index(): JsonResponse
    {
        $solicitudes = SolicitudLentes::with(['empleado', 'familiar', 'plaza'])->get();
        return response()->json($solicitudes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'EMPLEADO_NO'         => 'required|integer|exists:empleados,EMPLEADO_NO',
            'PLAZA_ID'            => 'nullable|integer|exists:empleado_plazas,ID',
            'FAMILIAR_ID'         => 'nullable|integer|exists:familiares,ID',
            'RECETA_ISTE_NUMERO'  => 'nullable|string|max:100',
            'RECETA_ISTE_ARCHIVO' => 'nullable|string|max:255',
            'ESTATUS'             => 'nullable|string|max:50',
            'OBSERVACIONES'       => 'nullable|string',
        ]);

        $solicitud = SolicitudLentes::create($data);

        return response()->json($solicitud->load(['empleado', 'familiar', 'plaza']), 201);
    }

    public function show(int $id): JsonResponse
    {
        $solicitud = SolicitudLentes::with(['empleado', 'familiar', 'plaza'])->findOrFail($id);
        return response()->json($solicitud);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $solicitud = SolicitudLentes::findOrFail($id);

        $data = $request->validate([
            'PLAZA_ID'            => 'nullable|integer|exists:empleado_plazas,ID',
            'FAMILIAR_ID'         => 'nullable|integer|exists:familiares,ID',
            'RECETA_ISTE_NUMERO'  => 'nullable|string|max:100',
            'RECETA_ISTE_ARCHIVO' => 'nullable|string|max:255',
            'ESTATUS'             => 'nullable|string|max:50',
            'OBSERVACIONES'       => 'nullable|string',
        ]);

        $solicitud->update($data);

        return response()->json($solicitud->load(['empleado', 'familiar', 'plaza']));
    }

    public function destroy(int $id): JsonResponse
    {
        $solicitud = SolicitudLentes::findOrFail($id);
        $solicitud->delete();

        return response()->json(['message' => 'Solicitud eliminada correctamente']);
    }

    public function indexByEmpleado(int $no): JsonResponse
    {
        Empleado::findOrFail($no);
        $solicitudes = SolicitudLentes::with(['familiar', 'plaza'])
            ->where('EMPLEADO_NO', $no)
            ->get();

        return response()->json($solicitudes);
    }
}
