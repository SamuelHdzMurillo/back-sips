<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\ConvocatoriaPostulacion;
use App\Models\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConvocatoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Convocatoria::sincronizarEstatusVencidas();

        $query = Convocatoria::query();

        if ($request->filled('estatus')) {
            $query->where('ESTATUS', strtoupper($request->string('estatus')));
        }

        if ($request->filled('localidad')) {
            $query->where('LOCALIDAD', strtoupper($request->string('localidad')));
        }

        if ($request->boolean('abiertas')) {
            $query->abiertas();
        }

        $convocatorias = $query->orderByDesc('FECHA_INICIO')->get();

        return response()->json($convocatorias);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateConvocatoria($request);
        $data['ESTATUS'] = $data['ESTATUS']
            ?? Convocatoria::resolverEstatusPorFecha($data['FECHA_FIN']);
        $data = $this->applyConvocatoriaFoto($request, $data);

        $convocatoria = Convocatoria::create($data);

        return response()->json($convocatoria, 201);
    }

    public function show(int $id): JsonResponse
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $convocatoria->sincronizarEstatus();

        return response()->json($convocatoria->fresh());
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $data = $this->validateConvocatoria($request);
        $data = $this->applyConvocatoriaFoto($request, $data, $convocatoria);

        if (isset($data['FECHA_FIN']) && ! isset($data['ESTATUS'])) {
            $data['ESTATUS'] = Convocatoria::resolverEstatusPorFecha($data['FECHA_FIN']);
        }

        $convocatoria->update($data);
        $convocatoria->sincronizarEstatus();

        return response()->json($convocatoria);
    }

    public function destroy(int $id): JsonResponse
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $this->deleteConvocatoriaFoto($convocatoria);
        $convocatoria->delete();

        return response()->json(['message' => 'Convocatoria eliminada correctamente.']);
    }

    public function abiertas(): JsonResponse
    {
        Convocatoria::sincronizarEstatusVencidas();

        $convocatorias = Convocatoria::query()
            ->abiertas()
            ->orderBy('FECHA_FIN')
            ->get();

        return response()->json($convocatorias);
    }

    public function misPostulaciones(): JsonResponse
    {
        $empleadoNo = $this->empleadoNoAutenticado();

        $postulaciones = ConvocatoriaPostulacion::query()
            ->with('convocatoria')
            ->where('EMPLEADO_NO', $empleadoNo)
            ->orderByDesc('FECHA_POSTULACION')
            ->get();

        return response()->json($postulaciones);
    }

    public function postular(Request $request, int $id): JsonResponse
    {
        $empleadoNo = $this->empleadoNoAutenticado();
        $empleado = Empleado::findOrFail($empleadoNo);

        if (! $empleado->estaActivo()) {
            return response()->json([
                'message' => 'Solo los empleados activos pueden postularse a una convocatoria.',
            ], 422);
        }

        $convocatoria = Convocatoria::findOrFail($id);

        if (! $convocatoria->estaAbierta()) {
            return response()->json([
                'message' => 'La convocatoria no está abierta para postulaciones (cerrada o fuera del periodo).',
            ], 422);
        }

        if (ConvocatoriaPostulacion::where('CONVOCATORIA_ID', $id)
            ->where('EMPLEADO_NO', $empleadoNo)
            ->exists()) {
            return response()->json([
                'message' => 'Ya te has postulado a esta convocatoria.',
            ], 422);
        }

        $data = $request->validate([
            'OBSERVACIONES' => 'nullable|string',
        ]);

        $postulacion = ConvocatoriaPostulacion::create([
            'CONVOCATORIA_ID' => $id,
            'EMPLEADO_NO'     => $empleadoNo,
            'OBSERVACIONES'   => $data['OBSERVACIONES'] ?? null,
        ]);

        return response()->json(
            $postulacion->load(['convocatoria', 'empleado']),
            201
        );
    }

    public function postulaciones(int $id): JsonResponse
    {
        Convocatoria::findOrFail($id);

        $postulaciones = ConvocatoriaPostulacion::query()
            ->with(['empleado.perfil.estudios', 'empleado.familiares', 'empleado.plazas'])
            ->where('CONVOCATORIA_ID', $id)
            ->get()
            ->sortByDesc(fn (ConvocatoriaPostulacion $p) => $this->antiguedadAnios($p->empleado))
            ->values()
            ->map(fn (ConvocatoriaPostulacion $p) => [
                'id'                => $p->ID,
                'fecha_postulacion' => $p->FECHA_POSTULACION,
                'observaciones'     => $p->OBSERVACIONES,
                'antiguedad_anios'  => $this->antiguedadAnios($p->empleado),
                'empleado'          => $p->empleado,
            ]);

        return response()->json([
            'convocatoria_id' => $id,
            'total'           => $postulaciones->count(),
            'postulaciones'   => $postulaciones,
        ]);
    }

    private function validateConvocatoria(Request $request): array
    {
        return $request->validate([
            'FECHA_INICIO'      => 'required|date',
            'FECHA_FIN'         => 'required|date|after_or_equal:FECHA_INICIO',
            'DENOMINACION'      => 'required|string|max:255',
            'NIVEL_SALARIAL'    => 'nullable|string|max:100',
            'ESTATUS_PLAZA'     => 'required|string|max:50',
            'SUELDO'            => 'nullable|numeric|min:0',
            'LUGAR_ADSCRIPCION' => 'nullable|string|max:255',
            'TURNO'                  => 'nullable|string|max:50',
            'LOCALIDAD'              => [
                'required',
                'string',
                Rule::in([
                    Convocatoria::LOCALIDAD_ESTATAL,
                    Convocatoria::LOCALIDAD_LOCAL,
                    Convocatoria::LOCALIDAD_DESIERTA,
                ]),
            ],
            'ESTATUS'                => [
                'nullable',
                'string',
                Rule::in([
                    Convocatoria::ESTATUS_ABIERTA,
                    Convocatoria::ESTATUS_CERRADA,
                ]),
            ],
            'CONVOCATORIA_RUTA_FOTO' => Rule::when(
                $request->hasFile('CONVOCATORIA_RUTA_FOTO'),
                ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                ['nullable', 'string', 'max:255']
            ),
        ], [], [
            'FECHA_INICIO' => 'fecha de inicio',
            'FECHA_FIN'    => 'fecha de fin',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyConvocatoriaFoto(Request $request, array $data, ?Convocatoria $existente = null): array
    {
        if ($request->hasFile('CONVOCATORIA_RUTA_FOTO')) {
            if ($existente?->CONVOCATORIA_RUTA_FOTO) {
                $this->deletePublicStoredFile($existente->CONVOCATORIA_RUTA_FOTO);
            }

            $path = $request->file('CONVOCATORIA_RUTA_FOTO')->store('fotos-convocatorias', 'public');
            $data['CONVOCATORIA_RUTA_FOTO'] = Storage::disk('public')->url($path);
        }

        return $data;
    }

    private function deleteConvocatoriaFoto(Convocatoria $convocatoria): void
    {
        if ($convocatoria->CONVOCATORIA_RUTA_FOTO) {
            $this->deletePublicStoredFile($convocatoria->CONVOCATORIA_RUTA_FOTO);
        }
    }

    private function deletePublicStoredFile(string $url): void
    {
        $oldPath = str_replace(Storage::disk('public')->url(''), '', $url);
        if ($oldPath !== '') {
            Storage::disk('public')->delete($oldPath);
        }
    }

    private function empleadoNoAutenticado(): int
    {
        $user = Auth::user();

        if (!$user?->empleado_no) {
            abort(404, 'Tu cuenta no está vinculada a ningún empleado.');
        }

        return (int) $user->empleado_no;
    }

    private function antiguedadAnios(?Empleado $empleado): ?int
    {
        if (!$empleado) {
            return null;
        }

        $hoy = Carbon::now();

        if ($empleado->EMPLEADO_FECHA_INGRESO) {
            return (int) $empleado->EMPLEADO_FECHA_INGRESO->diffInYears($hoy);
        }

        return is_numeric($empleado->EMPLEADO_ANTIGUEDAD)
            ? (int) $empleado->EMPLEADO_ANTIGUEDAD
            : null;
    }
}
