<?php

namespace App\Http\Controllers;

use App\Models\LoteLentes;
use App\Models\SolicitudLentes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoteLentesController extends Controller
{
    /**
     * Lista todos los lotes. Acepta filtro opcional por estatus.
     * GET /lotes-lentes?estatus=ABIERTO
     */
    public function index(Request $request): JsonResponse
    {
        $query = LoteLentes::withCount('solicitudes');

        if ($request->filled('estatus')) {
            $query->where('ESTATUS', strtoupper($request->estatus));
        }

        $lotes = $query->orderByDesc('FECHA_INICIO')->get();

        return response()->json($lotes);
    }

    /**
     * Crea un nuevo lote y preselecciona las solicitudes PENDIENTES del periodo.
     * POST /lotes-lentes
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'NOMBRE'       => 'required|string|max:150',
            'DESCRIPCION'  => 'nullable|string',
            'FECHA_INICIO' => 'required|date',
            'FECHA_FIN'    => 'required|date|after_or_equal:FECHA_INICIO',
        ]);

        $data['ESTATUS'] = 'ABIERTO';

        $lote = LoteLentes::create($data);

        $sugeridas = SolicitudLentes::with(['empleado', 'familiar', 'plaza'])
            ->whereNull('LOTE_ID')
            ->where('ESTATUS', 'PENDIENTE')
            ->whereBetween('CREATED_AT', [$lote->FECHA_INICIO, $lote->FECHA_FIN->endOfDay()])
            ->get();

        return response()->json([
            'lote'      => $lote,
            'sugeridas' => $sugeridas,
        ], 201);
    }

    /**
     * Detalle del lote con todas sus solicitudes.
     * GET /lotes-lentes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $lote = LoteLentes::with([
            'solicitudes.empleado',
            'solicitudes.familiar',
            'solicitudes.plaza',
        ])->findOrFail($id);

        return response()->json($lote);
    }

    /**
     * Actualiza datos o estatus del lote.
     * PUT /lotes-lentes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $lote = LoteLentes::findOrFail($id);

        $estatusValidos = ['ABIERTO', 'CERRADO', 'ENVIADO', 'CONCLUIDO'];

        $data = $request->validate([
            'NOMBRE'       => 'sometimes|string|max:150',
            'DESCRIPCION'  => 'nullable|string',
            'FECHA_INICIO' => 'sometimes|date',
            'FECHA_FIN'    => 'sometimes|date|after_or_equal:FECHA_INICIO',
            'ESTATUS'      => 'sometimes|string|in:' . implode(',', $estatusValidos),
        ]);

        $lote->update($data);

        return response()->json($lote->loadCount('solicitudes'));
    }

    /**
     * Elimina el lote y desvincula sus solicitudes (no las elimina).
     * DELETE /lotes-lentes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $lote = LoteLentes::findOrFail($id);

        $lote->solicitudes()->update(['LOTE_ID' => null]);
        $lote->delete();

        return response()->json(['message' => 'Lote eliminado correctamente']);
    }

    /**
     * Agrega una solicitud al lote.
     * POST /lotes-lentes/{id}/solicitudes/{solicitudId}
     */
    public function addSolicitud(int $id, int $solicitudId): JsonResponse
    {
        $lote = LoteLentes::findOrFail($id);
        $solicitud = SolicitudLentes::findOrFail($solicitudId);

        $solicitud->update(['LOTE_ID' => $lote->ID]);

        return response()->json($solicitud->load(['empleado', 'familiar', 'plaza']));
    }

    /**
     * Quita una solicitud del lote (la desvincula, no la elimina).
     * DELETE /lotes-lentes/{id}/solicitudes/{solicitudId}
     */
    public function removeSolicitud(int $id, int $solicitudId): JsonResponse
    {
        $lote = LoteLentes::findOrFail($id);

        $solicitud = SolicitudLentes::where('ID', $solicitudId)
            ->where('LOTE_ID', $lote->ID)
            ->firstOrFail();

        $solicitud->update(['LOTE_ID' => null]);

        return response()->json(['message' => 'Solicitud removida del lote']);
    }

    /**
     * Consulta solicitudes PENDIENTES sin lote en un periodo dado.
     * GET /lotes-lentes/periodo?fecha_inicio=YYYY-MM-DD&fecha_fin=YYYY-MM-DD
     */
    public function solicitudesByPeriodo(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $solicitudes = SolicitudLentes::with(['empleado', 'familiar', 'plaza'])
            ->whereNull('LOTE_ID')
            ->where('ESTATUS', 'PENDIENTE')
            ->whereBetween('CREATED_AT', [
                $request->fecha_inicio,
                $request->fecha_fin . ' 23:59:59',
            ])
            ->get();

        return response()->json($solicitudes);
    }
}
