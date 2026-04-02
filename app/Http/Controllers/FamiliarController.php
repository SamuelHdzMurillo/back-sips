<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Familiar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FamiliarController extends Controller
{
    public function indexByEmpleado(int $no): JsonResponse
    {
        Empleado::findOrFail($no);
        $familiares = Familiar::where('EMPLEADO_NO', $no)->get();

        return response()->json($familiares);
    }

    public function storeByEmpleado(Request $request, int $no): JsonResponse
    {
        Empleado::findOrFail($no);

        $data = $request->validate([
            'NOMBRE'               => 'required|string|max:100',
            'APELLIDO_PATERNO'     => 'required|string|max:100',
            'APELLIDO_MATERNO'     => 'required|string|max:100',
            'PARENTESCO'           => 'required|string|max:50',
            'DOCUMENTO_PARENTESCO' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $data['EMPLEADO_NO'] = $no;

        if ($request->hasFile('DOCUMENTO_PARENTESCO')) {
            $path = $request->file('DOCUMENTO_PARENTESCO')
                ->store('documentos-parentesco', 'public');
            $data['DOCUMENTO_PARENTESCO'] = Storage::disk('public')->url($path);
        }

        $familiar = Familiar::create($data);

        return response()->json($familiar, 201);
    }

    public function show(int $id): JsonResponse
    {
        $familiar = Familiar::findOrFail($id);
        return response()->json($familiar);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $familiar = Familiar::findOrFail($id);

        $data = $request->validate([
            'NOMBRE'               => 'sometimes|string|max:100',
            'APELLIDO_PATERNO'     => 'sometimes|string|max:100',
            'APELLIDO_MATERNO'     => 'sometimes|string|max:100',
            'PARENTESCO'           => 'sometimes|string|max:50',
            'DOCUMENTO_PARENTESCO' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('DOCUMENTO_PARENTESCO')) {
            if ($familiar->DOCUMENTO_PARENTESCO) {
                $oldPath = str_replace(Storage::disk('public')->url(''), '', $familiar->DOCUMENTO_PARENTESCO);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('DOCUMENTO_PARENTESCO')
                ->store('documentos-parentesco', 'public');
            $data['DOCUMENTO_PARENTESCO'] = Storage::disk('public')->url($path);
        }

        $familiar->update($data);

        return response()->json($familiar);
    }

    public function destroy(int $id): JsonResponse
    {
        $familiar = Familiar::findOrFail($id);

        if ($familiar->DOCUMENTO_PARENTESCO) {
            $oldPath = str_replace(Storage::disk('public')->url(''), '', $familiar->DOCUMENTO_PARENTESCO);
            Storage::disk('public')->delete($oldPath);
        }

        $familiar->delete();

        return response()->json(['message' => 'Familiar eliminado correctamente']);
    }
}
