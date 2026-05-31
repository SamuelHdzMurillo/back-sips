<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\ConvocatoriaPostulacion;
use App\Models\Empleado;
use App\Services\PdfCompressor;
use App\Services\PdfToImageConverter;
use App\Support\StorageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConvocatoriaController extends Controller
{
    public function __construct(
        private readonly PdfCompressor $pdfCompressor,
        private readonly PdfToImageConverter $pdfToImage,
    ) {}
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

        return response()->json($convocatorias->map(fn (Convocatoria $c) => $this->formatConvocatoria($c)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateConvocatoria($request);
        $data['ESTATUS'] = $data['ESTATUS']
            ?? Convocatoria::resolverEstatusPorFecha($data['FECHA_FIN']);
        $data = $this->applyConvocatoriaFoto($request, $data);

        $convocatoria = Convocatoria::create($data);
        $this->ensurePreviewImagen($convocatoria);

        return response()->json($this->formatConvocatoria($convocatoria->fresh(), true), 201);
    }

    public function show(int $id): JsonResponse
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $convocatoria->sincronizarEstatus();
        $convocatoria->refresh();
        $this->ensurePreviewImagen($convocatoria);

        $payload = $this->formatConvocatoria($convocatoria->fresh(), true);

        $user = Auth::user();

        if ($user?->empleado_no) {
            $empleadoNo = (int) $user->empleado_no;
            $empleado = Empleado::find($empleadoNo);

            $payload['ya_postulado'] = ConvocatoriaPostulacion::where('CONVOCATORIA_ID', $id)
                ->where('EMPLEADO_NO', $empleadoNo)
                ->exists();

            $payload['puede_postular'] = $empleado?->estaActivo() === true
                && $convocatoria->estaAbierta()
                && ! $payload['ya_postulado'];
        }

        return response()->json($payload);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $data = $this->validateConvocatoria($request, partial: true, existente: $convocatoria);
        $data = $this->applyConvocatoriaFoto($request, $data, $convocatoria);

        if (isset($data['FECHA_FIN']) && ! isset($data['ESTATUS'])) {
            $data['ESTATUS'] = Convocatoria::resolverEstatusPorFecha($data['FECHA_FIN']);
        }

        $convocatoria->update($data);
        $convocatoria->sincronizarEstatus();
        $this->ensurePreviewImagen($convocatoria);

        return response()->json($this->formatConvocatoria($convocatoria->fresh(), true));
    }

    public function documento(int $id): Response
    {
        return $this->serveDocumento($id);
    }

    public function documentoBase64(int $id): JsonResponse
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $this->ensurePreviewImagen($convocatoria);
        $convocatoria->refresh();

        if (! $convocatoria->documento_existe) {
            return response()->json([
                'message'          => 'No hay documento disponible para esta convocatoria.',
                'documento_existe' => false,
            ], 404);
        }

        $imagenPath = $convocatoria->documento_imagen_ruta_storage;

        if ($imagenPath !== null) {
            $mime = StorageUrl::mimeTypeFromPath($imagenPath);
            $contents = Storage::disk('public')->get($imagenPath);

            return response()->json([
                'convocatoria_id'              => $id,
                'documento_existe'             => true,
                'documento_tipo'               => $convocatoria->documento_tipo,
                'documento_imagen_existe'      => true,
                'documento_imagen_url'         => $convocatoria->documento_imagen_url,
                'documento_imagen_api_url'     => $convocatoria->documento_imagen_api_url,
                'documento_imagen_mime'        => $mime,
                'documento_imagen_base64'      => base64_encode($contents),
                'documento_imagen_data_url'    => 'data:'.$mime.';base64,'.base64_encode($contents),
                'documento_preview_data_url'   => 'data:'.$mime.';base64,'.base64_encode($contents),
            ]);
        }

        $path = $convocatoria->documento_ruta_storage;
        $mime = StorageUrl::mimeTypeFromPath($path);
        $contents = Storage::disk('public')->get($path);

        return response()->json([
            'convocatoria_id'            => $id,
            'documento_existe'           => true,
            'documento_tipo'             => $convocatoria->documento_tipo,
            'documento_imagen_existe'    => false,
            'documento_url'              => $convocatoria->documento_url,
            'documento_base64'           => base64_encode($contents),
            'documento_preview_data_url'   => 'data:'.$mime.';base64,'.base64_encode($contents),
        ]);
    }

    public function imagen(int $id): Response
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $this->ensurePreviewImagen($convocatoria);

        $path = $convocatoria->documento_imagen_ruta_storage;

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return response()->json([
                'message' => 'No hay imagen de vista previa disponible.',
            ], 404);
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mime = StorageUrl::mimeTypeFromPath($path);

        /** @var BinaryFileResponse $response */
        $response = response()->file($absolutePath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);

        $response->headers->set('Content-Security-Policy', 'frame-ancestors *');
        $response->headers->remove('X-Frame-Options');

        return $response;
    }

    private function serveDocumento(int $id): Response
    {
        $convocatoria = Convocatoria::findOrFail($id);
        $path = $convocatoria->documento_ruta_storage;

        if (! $convocatoria->documento_existe || $path === null) {
            return response()->json([
                'message'          => 'No hay documento disponible para esta convocatoria.',
                'documento_existe' => false,
            ], 404);
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mime = StorageUrl::mimeTypeFromPath($path);
        $filename = basename($path);

        /** @var BinaryFileResponse $response */
        $response = response()->file($absolutePath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);

        $response->headers->set('Content-Security-Policy', 'frame-ancestors *');
        $response->headers->remove('X-Frame-Options');

        return $response;
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

        return response()->json($convocatorias->map(fn (Convocatoria $c) => $this->formatConvocatoria($c)));
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

    private function validateConvocatoria(
        Request $request,
        bool $partial = false,
        ?Convocatoria $existente = null,
    ): array {
        $required = $partial ? 'sometimes' : 'required';

        $rules = [
            'FECHA_INICIO'      => "{$required}|date",
            'FECHA_FIN'         => "{$required}|date",
            'DENOMINACION'      => "{$required}|string|max:255",
            'NIVEL_SALARIAL'    => 'nullable|string|max:100',
            'ESTATUS_PLAZA'     => "{$required}|string|max:50",
            'SUELDO'            => 'nullable|numeric|min:0',
            'LUGAR_ADSCRIPCION' => 'nullable|string|max:255',
            'TURNO'             => 'nullable|string|max:50',
            'LOCALIDAD'         => [
                $required,
                'string',
                Rule::in([
                    Convocatoria::LOCALIDAD_ESTATAL,
                    Convocatoria::LOCALIDAD_LOCAL,
                    Convocatoria::LOCALIDAD_DESIERTA,
                ]),
            ],
            'ESTATUS' => [
                'nullable',
                'string',
                Rule::in([
                    Convocatoria::ESTATUS_ABIERTA,
                    Convocatoria::ESTATUS_CERRADA,
                ]),
            ],
            'CONVOCATORIA_RUTA_FOTO' => Rule::when(
                $this->resolveUploadedDocumento($request) !== null,
                ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'],
                ['nullable', 'string', 'max:255']
            ),
        ];

        if (! $partial) {
            $rules['FECHA_FIN'] = 'required|date|after_or_equal:FECHA_INICIO';
        }

        $data = $request->validate($rules, [], [
            'FECHA_INICIO'           => 'fecha de inicio',
            'FECHA_FIN'              => 'fecha de fin',
            'DENOMINACION'           => 'denominación',
            'NIVEL_SALARIAL'         => 'nivel salarial',
            'ESTATUS_PLAZA'          => 'estatus de plaza',
            'SUELDO'                 => 'sueldo',
            'LUGAR_ADSCRIPCION'      => 'lugar de adscripción',
            'TURNO'                  => 'turno',
            'LOCALIDAD'              => 'localidad',
            'ESTATUS'                => 'estatus',
            'CONVOCATORIA_RUTA_FOTO' => 'documento de convocatoria',
        ]);

        if ($partial && (isset($data['FECHA_INICIO']) || isset($data['FECHA_FIN']))) {
            $fechaInicio = $data['FECHA_INICIO'] ?? $existente?->FECHA_INICIO;
            $fechaFin = $data['FECHA_FIN'] ?? $existente?->FECHA_FIN;

            if ($fechaInicio && $fechaFin && Carbon::parse($fechaFin)->lt(Carbon::parse($fechaInicio))) {
                throw ValidationException::withMessages([
                    'FECHA_FIN' => ['La fecha de fin debe ser posterior o igual a la fecha de inicio.'],
                ]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyConvocatoriaFoto(Request $request, array $data, ?Convocatoria $existente = null): array
    {
        $file = $this->resolveUploadedDocumento($request);

        if ($file === null) {
            return $data;
        }

        if ($existente?->getRawOriginal('CONVOCATORIA_RUTA_FOTO')) {
            $this->deletePublicStoredFile($existente->getRawOriginal('CONVOCATORIA_RUTA_FOTO'));
        }

        if ($existente?->getRawOriginal('CONVOCATORIA_RUTA_PREVIEW')) {
            $this->deletePublicStoredFile($existente->getRawOriginal('CONVOCATORIA_RUTA_PREVIEW'));
        }

        $extension = strtolower($file->getClientOriginalExtension() ?? '');

        if ($extension === 'pdf') {
            $pdfPath = $this->storeConvocatoriaPdf($file);
            $data['CONVOCATORIA_RUTA_FOTO'] = $pdfPath;
            $data['CONVOCATORIA_RUTA_PREVIEW'] = $this->generatePdfPreview($pdfPath);
        } else {
            $path = $file->store('archivos-convocatorias', 'public');
            $data['CONVOCATORIA_RUTA_FOTO'] = $path;
            $data['CONVOCATORIA_RUTA_PREVIEW'] = null;
        }

        return $data;
    }

    private function resolveUploadedDocumento(Request $request): ?UploadedFile
    {
        foreach (['CONVOCATORIA_RUTA_FOTO', 'documento', 'archivo', 'file'] as $field) {
            if ($request->hasFile($field)) {
                return $request->file($field);
            }
        }

        return null;
    }

    private function storeConvocatoriaPdf(UploadedFile $file): string
    {
        $tempRelative = $file->store('temp-convocatorias', 'local');
        $sourcePath = Storage::disk('local')->path($tempRelative);

        $compressedPath = $this->pdfCompressor->compress(
            $sourcePath,
            env('PDF_COMPRESS_QUALITY', 'ebook')
        );
        $filename = Str::uuid().'.pdf';
        $publicPath = 'archivos-convocatorias/'.$filename;

        if ($compressedPath !== null) {
            Storage::disk('public')->put($publicPath, file_get_contents($compressedPath));
            @unlink($compressedPath);
        } else {
            Storage::disk('public')->putFileAs('archivos-convocatorias', $file, $filename);
        }

        Storage::disk('local')->delete($tempRelative);

        return $publicPath;
    }

    private function generatePdfPreview(string $pdfRelativePath): ?string
    {
        $previewPath = $this->pdfToImage->makePreviewRelativePath();
        $pdfAbsolute = Storage::disk('public')->path($pdfRelativePath);

        if ($this->pdfToImage->convertPdfFile($pdfAbsolute, $previewPath)) {
            return $previewPath;
        }

        return null;
    }

    private function ensurePreviewImagen(Convocatoria $convocatoria): void
    {
        if ($convocatoria->documento_tipo !== 'pdf' || ! $convocatoria->documento_existe) {
            return;
        }

        $preview = $convocatoria->getRawOriginal('CONVOCATORIA_RUTA_PREVIEW');

        if ($preview && Storage::disk('public')->exists($preview)) {
            return;
        }

        $generated = $this->generatePdfPreview($convocatoria->documento_ruta_storage);

        if ($generated !== null) {
            $convocatoria->update(['CONVOCATORIA_RUTA_PREVIEW' => $generated]);
        }
    }

    private function deleteConvocatoriaFoto(Convocatoria $convocatoria): void
    {
        $raw = $convocatoria->getRawOriginal('CONVOCATORIA_RUTA_FOTO');

        if ($raw) {
            $this->deletePublicStoredFile($raw);
        }

        $preview = $convocatoria->getRawOriginal('CONVOCATORIA_RUTA_PREVIEW');

        if ($preview) {
            $this->deletePublicStoredFile($preview);
        }
    }

    private function deletePublicStoredFile(string $url): void
    {
        $path = StorageUrl::relativePath($url);

        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatConvocatoria(Convocatoria $convocatoria, bool $withPreview = false): array
    {
        $data = $convocatoria->toArray();
        $existe = (bool) ($data['documento_existe'] ?? false);

        if (! $existe) {
            $data['CONVOCATORIA_RUTA_FOTO'] = null;
            $data['documento_url'] = null;
            $data['documento_api_url'] = null;
            $data['documento_url_acceso'] = null;
            $data['documento_preview_data_url'] = null;
        }

        $data['urls_documento'] = [
            'storage'      => $data['documento_url'] ?? null,
            'api'          => $data['documento_api_url'] ?? null,
            'acceso'       => $data['documento_url_acceso'] ?? null,
            'imagen'       => $data['documento_imagen_url'] ?? null,
            'imagen_api'   => $data['documento_imagen_api_url'] ?? null,
            'ruta_interna' => $data['documento_ruta_storage'] ?? null,
            'preview'      => null,
        ];

        if ($withPreview && ($data['documento_imagen_existe'] ?? false)) {
            $data = $this->appendDocumentoPreview($data, $convocatoria);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function appendDocumentoPreview(array $data, Convocatoria $convocatoria): array
    {
        $data['documento_preview_data_url'] = null;
        $data['documento_imagen_data_url'] = null;

        $path = $convocatoria->documento_imagen_ruta_storage;

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return $data;
        }

        $size = Storage::disk('public')->size($path);
        $mime = StorageUrl::mimeTypeFromPath($path);

        if ($size <= 3 * 1024 * 1024) {
            $contents = Storage::disk('public')->get($path);
            $dataUrl = 'data:'.$mime.';base64,'.base64_encode($contents);
            $data['documento_imagen_data_url'] = $dataUrl;
            $data['documento_preview_data_url'] = $dataUrl;
        }

        $data['urls_documento']['preview'] = $data['documento_imagen_data_url'];

        return $data;
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
