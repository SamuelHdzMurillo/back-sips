<?php

namespace App\Http\Controllers;

use App\Models\LoteLentes;
use App\Models\SolicitudLentes;
use App\Models\SolicitudLentesTablaPublica;
use App\Support\StorageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudLentesTablaPublicaController extends Controller
{
    /**
     * Configuración de la tabla pública (admin).
     * GET /solicitud-lentes/tabla-publica
     */
    public function config(): JsonResponse
    {
        $config = SolicitudLentesTablaPublica::configuracion()->load('lotes');

        $lotesDisponibles = LoteLentes::withCount('solicitudes')
            ->orderByDesc('FECHA_INICIO')
            ->get()
            ->map(fn (LoteLentes $lote) => $this->formatLoteResumen($lote));

        return response()->json([
            ...$this->formatConfig($config),
            'lotes_seleccionados' => $config->lotes->pluck('ID')->values(),
            'lotes'               => $config->lotes
                ->sortByDesc('FECHA_INICIO')
                ->values()
                ->map(fn (LoteLentes $lote) => $this->formatLoteResumen($lote)),
            'lotes_disponibles'   => $lotesDisponibles,
        ]);
    }

    /**
     * Actualiza compartir, título y lotes visibles públicamente.
     * PATCH /solicitud-lentes/tabla-publica
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'COMPARTIR'   => 'sometimes|boolean',
            'TITULO'      => 'nullable|string|max:150',
            'LOTES_IDS'   => 'sometimes|array',
            'LOTES_IDS.*' => 'integer|exists:lotes_lentes,ID',
        ]);

        $config = SolicitudLentesTablaPublica::configuracion();

        $lotesIds = $data['LOTES_IDS'] ?? null;
        unset($data['LOTES_IDS']);

        if ($data !== []) {
            $config->update($data);
        }

        if ($lotesIds !== null) {
            $config->lotes()->sync($lotesIds);
        }

        return $this->config();
    }

    /**
     * Tabla pública de solicitudes por lotes seleccionados (sin autenticación).
     * GET /solicitud-lentes/public/tabla/{token}
     */
    public function show(string $token): JsonResponse
    {
        $config = SolicitudLentesTablaPublica::query()
            ->where('TOKEN', $token)
            ->with('lotes')
            ->first();

        if (! $config || ! $config->COMPARTIR) {
            return response()->json([
                'compartir' => false,
                'message'   => 'Esta tabla no está disponible para compartir.',
            ], 403);
        }

        $lotesIds = $config->lotes->pluck('ID');

        if ($lotesIds->isEmpty()) {
            return response()->json([
                'compartir'         => true,
                'titulo'            => $config->TITULO,
                'lotes_seleccionados' => [],
                'total_solicitudes' => 0,
                'lotes'             => [],
                'message'           => 'No hay lotes seleccionados para mostrar.',
            ]);
        }

        $solicitudes = SolicitudLentes::with(['empleado', 'familiar', 'plaza', 'lote'])
            ->whereIn('LOTE_ID', $lotesIds)
            ->orderBy('LOTE_ID')
            ->orderByDesc('CREATED_AT')
            ->get();

        $lotes = $config->lotes
            ->sortByDesc('FECHA_INICIO')
            ->values()
            ->map(function (LoteLentes $lote) use ($solicitudes) {
                $solicitudesLote = $solicitudes
                    ->where('LOTE_ID', $lote->ID)
                    ->values()
                    ->map(fn (SolicitudLentes $s) => $this->formatSolicitudPublica($s));

                return [
                    ...$this->formatLoteResumen($lote),
                    'total_solicitudes' => $solicitudesLote->count(),
                    'solicitudes'       => $solicitudesLote,
                ];
            });

        return response()->json([
            'compartir'           => true,
            'titulo'              => $config->TITULO,
            'lotes_seleccionados' => $lotesIds->values(),
            'total_solicitudes'   => $solicitudes->count(),
            'lotes'               => $lotes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatConfig(SolicitudLentesTablaPublica $config): array
    {
        return [
            'compartir'        => $config->COMPARTIR,
            'titulo'           => $config->TITULO,
            'token'            => $config->TOKEN,
            'enlace_compartir' => StorageUrl::apiUrl('solicitud-lentes/public/tabla/'.$config->TOKEN),
            'actualizado_en'   => $config->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLoteResumen(LoteLentes $lote): array
    {
        return [
            'ID'                 => $lote->ID,
            'NOMBRE'             => $lote->NOMBRE,
            'DESCRIPCION'        => $lote->DESCRIPCION,
            'FECHA_INICIO'       => $lote->FECHA_INICIO?->toDateString(),
            'FECHA_FIN'          => $lote->FECHA_FIN?->toDateString(),
            'ESTATUS'            => $lote->ESTATUS,
            'total_solicitudes'  => $lote->solicitudes_count ?? $lote->solicitudes()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSolicitudPublica(SolicitudLentes $solicitud): array
    {
        return [
            'ID'                     => $solicitud->ID,
            'LOTE_ID'                => $solicitud->LOTE_ID,
            'EMPLEADO_NO'            => $solicitud->EMPLEADO_NO,
            'PLAZA_ID'               => $solicitud->PLAZA_ID,
            'FAMILIAR_ID'            => $solicitud->FAMILIAR_ID,
            'RECETA_ISTE_NUMERO'     => $solicitud->RECETA_ISTE_NUMERO,
            'RECETA_ISTE_ARCHIVO'    => StorageUrl::normalize($solicitud->RECETA_ISTE_ARCHIVO),
            'OPTICA_NOMBRE'          => $solicitud->OPTICA_NOMBRE,
            'FACTURA_COMPRA_ARCHIVO' => StorageUrl::normalize($solicitud->FACTURA_COMPRA_ARCHIVO),
            'ESTATUS'                => $solicitud->ESTATUS,
            'OBSERVACIONES'          => $solicitud->OBSERVACIONES,
            'CREATED_AT'             => $solicitud->CREATED_AT?->toIso8601String(),
            'empleado'               => $this->formatEmpleadoPublico($solicitud),
            'familiar'               => $this->formatFamiliarPublico($solicitud),
            'plaza'                  => $this->formatPlazaPublica($solicitud),
            'lote'                   => $solicitud->lote ? $this->formatLoteResumen($solicitud->lote) : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatEmpleadoPublico(SolicitudLentes $solicitud): ?array
    {
        $empleado = $solicitud->empleado;

        if (! $empleado) {
            return null;
        }

        return [
            'EMPLEADO_NO'               => $empleado->EMPLEADO_NO,
            'EMPLEADO_NOMBRE'           => $empleado->EMPLEADO_NOMBRE,
            'EMPLEADO_APELLIDO_PATERNO' => $empleado->EMPLEADO_APELLIDO_PATERNO,
            'EMPLEADO_APELLIDO_MATERNO' => $empleado->EMPLEADO_APELLIDO_MATERNO,
            'nombre_completo'           => trim(
                "{$empleado->EMPLEADO_APELLIDO_PATERNO} {$empleado->EMPLEADO_APELLIDO_MATERNO} {$empleado->EMPLEADO_NOMBRE}"
            ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatFamiliarPublico(SolicitudLentes $solicitud): ?array
    {
        $familiar = $solicitud->familiar;

        if (! $familiar) {
            return null;
        }

        return [
            'ID'                   => $familiar->ID,
            'NOMBRE'               => $familiar->NOMBRE,
            'APELLIDO_PATERNO'     => $familiar->APELLIDO_PATERNO,
            'APELLIDO_MATERNO'     => $familiar->APELLIDO_MATERNO,
            'PARENTESCO'           => $familiar->PARENTESCO,
            'DOCUMENTO_PARENTESCO' => StorageUrl::normalize($familiar->DOCUMENTO_PARENTESCO),
            'nombre_completo'      => trim(
                "{$familiar->NOMBRE} {$familiar->APELLIDO_PATERNO} {$familiar->APELLIDO_MATERNO}"
            ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatPlazaPublica(SolicitudLentes $solicitud): ?array
    {
        $plaza = $solicitud->plaza;

        if (! $plaza) {
            return null;
        }

        return [
            'ID'                  => $plaza->ID,
            'EMPLEADO_CCT_CLAVE'  => $plaza->EMPLEADO_CCT_CLAVE,
            'EMPLEADO_CCT_NOMBRE' => $plaza->EMPLEADO_CCT_NOMBRE,
            'EMPLEADO_PUESTO'     => $plaza->EMPLEADO_PUESTO,
            'EMPLEADO_CATEGORIA'  => $plaza->EMPLEADO_CATEGORIA,
            'EMPLEADO_FUNCION'    => $plaza->EMPLEADO_FUNCION,
            'EMPLEADO_TIPO_PLAZA' => $plaza->EMPLEADO_TIPO_PLAZA,
            'HORAS'               => $plaza->HORAS,
        ];
    }
}
