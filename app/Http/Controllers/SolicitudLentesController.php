<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\SolicitudLentes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
            'EMPLEADO_NO'              => 'required|integer|exists:empleados,EMPLEADO_NO',
            'PLAZA_ID'                 => 'nullable|integer|exists:empleado_plazas,ID',
            'FAMILIAR_ID'              => 'nullable|integer|exists:familiares,ID',
            'RECETA_ISTE_NUMERO'       => 'nullable|string|max:100',
            'RECETA_ISTE_ARCHIVO'      => Rule::when(
                $request->hasFile('RECETA_ISTE_ARCHIVO'),
                ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                ['nullable', 'string', 'max:255']
            ),
            'OPTICA_NOMBRE'            => 'nullable|string|max:255',
            'FACTURA_COMPRA_ARCHIVO'   => Rule::when(
                $request->hasFile('FACTURA_COMPRA_ARCHIVO'),
                ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                ['nullable', 'string', 'max:255']
            ),
            'ESTATUS'                  => 'nullable|string|max:50',
            'OBSERVACIONES'            => 'nullable|string',
        ]);

        $data = $this->applySolicitudLentesArchivos($request, $data);

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
            'PLAZA_ID'               => 'nullable|integer|exists:empleado_plazas,ID',
            'FAMILIAR_ID'            => 'nullable|integer|exists:familiares,ID',
            'RECETA_ISTE_NUMERO'     => 'nullable|string|max:100',
            'RECETA_ISTE_ARCHIVO'    => Rule::when(
                $request->hasFile('RECETA_ISTE_ARCHIVO'),
                ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                ['nullable', 'string', 'max:255']
            ),
            'OPTICA_NOMBRE'          => 'nullable|string|max:255',
            'FACTURA_COMPRA_ARCHIVO' => Rule::when(
                $request->hasFile('FACTURA_COMPRA_ARCHIVO'),
                ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                ['nullable', 'string', 'max:255']
            ),
            'ESTATUS'                => 'nullable|string|max:50',
            'OBSERVACIONES'          => 'nullable|string',
        ]);

        $data = $this->applySolicitudLentesArchivos($request, $data, $solicitud);

        $solicitud->update($data);

        return response()->json($solicitud->load(['empleado', 'familiar', 'plaza']));
    }

    public function destroy(int $id): JsonResponse
    {
        $solicitud = SolicitudLentes::findOrFail($id);
        $this->deleteSolicitudLentesArchivos($solicitud);
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applySolicitudLentesArchivos(Request $request, array $data, ?SolicitudLentes $existente = null): array
    {
        if ($request->hasFile('RECETA_ISTE_ARCHIVO')) {
            if ($existente?->RECETA_ISTE_ARCHIVO) {
                $this->deletePublicStoredFile($existente->RECETA_ISTE_ARCHIVO);
            }
            $path = $request->file('RECETA_ISTE_ARCHIVO')->store('recetas-iste-lentes', 'public');
            $data['RECETA_ISTE_ARCHIVO'] = Storage::disk('public')->url($path);
        }

        if ($request->hasFile('FACTURA_COMPRA_ARCHIVO')) {
            if ($existente?->FACTURA_COMPRA_ARCHIVO) {
                $this->deletePublicStoredFile($existente->FACTURA_COMPRA_ARCHIVO);
            }
            $path = $request->file('FACTURA_COMPRA_ARCHIVO')->store('facturas-compra-lentes', 'public');
            $data['FACTURA_COMPRA_ARCHIVO'] = Storage::disk('public')->url($path);
        }

        return $data;
    }

    private function deletePublicStoredFile(string $url): void
    {
        $oldPath = str_replace(Storage::disk('public')->url(''), '', $url);
        if ($oldPath !== '') {
            Storage::disk('public')->delete($oldPath);
        }
    }

    private function deleteSolicitudLentesArchivos(SolicitudLentes $solicitud): void
    {
        if ($solicitud->RECETA_ISTE_ARCHIVO) {
            $this->deletePublicStoredFile($solicitud->RECETA_ISTE_ARCHIVO);
        }
        if ($solicitud->FACTURA_COMPRA_ARCHIVO) {
            $this->deletePublicStoredFile($solicitud->FACTURA_COMPRA_ARCHIVO);
        }
    }
}
