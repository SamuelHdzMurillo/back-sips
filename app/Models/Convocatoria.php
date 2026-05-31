<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Support\StorageUrl;

class Convocatoria extends Model
{
    protected $table = 'convocatorias';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';

    public const ESTATUS_ABIERTA = 'ABIERTA';
    public const ESTATUS_CERRADA = 'CERRADA';

    public const LOCALIDAD_ESTATAL = 'ESTATAL';
    public const LOCALIDAD_LOCAL = 'LOCAL';
    public const LOCALIDAD_DESIERTA = 'DESIERTA';

    protected $appends = [
        'documento_url',
        'documento_api_url',
        'documento_url_acceso',
        'documento_ruta_storage',
        'documento_tipo',
        'documento_existe',
        'documento_imagen_url',
        'documento_imagen_api_url',
        'documento_imagen_existe',
    ];

    protected $fillable = [
        'FECHA_INICIO',
        'FECHA_FIN',
        'DENOMINACION',
        'NIVEL_SALARIAL',
        'ESTATUS_PLAZA',
        'SUELDO',
        'LUGAR_ADSCRIPCION',
        'TURNO',
        'LOCALIDAD',
        'CONVOCATORIA_RUTA_FOTO',
        'CONVOCATORIA_RUTA_PREVIEW',
        'ESTATUS',
    ];

    protected $casts = [
        'FECHA_INICIO' => 'date',
        'FECHA_FIN'    => 'date',
        'SUELDO'       => 'decimal:2',
    ];

    public function getConvocatoriaRutaFotoAttribute(?string $value): ?string
    {
        return StorageUrl::normalize($value);
    }

    public function getDocumentoUrlAttribute(): ?string
    {
        if (! $this->documento_existe) {
            return null;
        }

        return $this->CONVOCATORIA_RUTA_FOTO;
    }

    public function getDocumentoRutaStorageAttribute(): ?string
    {
        return StorageUrl::relativePath($this->attributes['CONVOCATORIA_RUTA_FOTO'] ?? null);
    }

    public function getDocumentoTipoAttribute(): ?string
    {
        return StorageUrl::tipoArchivo($this->attributes['CONVOCATORIA_RUTA_FOTO'] ?? null);
    }

    public function getDocumentoApiUrlAttribute(): ?string
    {
        if (! $this->documento_existe) {
            return null;
        }

        return StorageUrl::apiUrl('convocatorias/'.$this->ID.'/documento');
    }

    public function getDocumentoUrlAccesoAttribute(): ?string
    {
        if (! $this->documento_existe) {
            return null;
        }

        $signed = URL::temporarySignedRoute(
            'convocatorias.documento',
            now()->addHours(24),
            ['id' => $this->ID]
        );

        if (! app()->runningInConsole() && request()->hasHeader('Host')) {
            $signed = preg_replace('#^https?://[^/]+#', StorageUrl::baseUrl(), $signed);
        }

        return $signed;
    }

    public function getDocumentoExisteAttribute(): bool
    {
        $path = $this->documento_ruta_storage;

        return $path !== null && Storage::disk('public')->exists($path);
    }

    public function getDocumentoImagenRutaStorageAttribute(): ?string
    {
        $preview = $this->attributes['CONVOCATORIA_RUTA_PREVIEW'] ?? null;

        if ($preview && Storage::disk('public')->exists($preview)) {
            return $preview;
        }

        if ($this->documento_tipo === 'imagen' && $this->documento_existe) {
            return $this->documento_ruta_storage;
        }

        return null;
    }

    public function getDocumentoImagenExisteAttribute(): bool
    {
        $path = $this->documento_imagen_ruta_storage;

        return $path !== null && Storage::disk('public')->exists($path);
    }

    public function getDocumentoImagenUrlAttribute(): ?string
    {
        if (! $this->documento_imagen_existe) {
            return null;
        }

        return StorageUrl::publicUrl($this->documento_imagen_ruta_storage);
    }

    public function getDocumentoImagenApiUrlAttribute(): ?string
    {
        if (! $this->documento_imagen_existe) {
            return null;
        }

        return StorageUrl::apiUrl('convocatorias/'.$this->ID.'/imagen');
    }

    public function tieneDocumento(): bool
    {
        return $this->documento_existe;
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(ConvocatoriaPostulacion::class, 'CONVOCATORIA_ID', 'ID');
    }

    public static function sincronizarEstatusVencidas(): void
    {
        static::query()
            ->where('ESTATUS', self::ESTATUS_ABIERTA)
            ->whereDate('FECHA_FIN', '<', Carbon::today())
            ->update(['ESTATUS' => self::ESTATUS_CERRADA]);
    }

    public function sincronizarEstatus(): void
    {
        if ($this->FECHA_FIN && Carbon::today()->gt($this->FECHA_FIN)) {
            if ($this->ESTATUS !== self::ESTATUS_CERRADA) {
                $this->ESTATUS = self::ESTATUS_CERRADA;
                $this->saveQuietly();
            }
        }
    }

    public function estaAbierta(): bool
    {
        $this->sincronizarEstatus();

        $hoy = Carbon::today();

        return $this->ESTATUS === self::ESTATUS_ABIERTA
            && $hoy->gte($this->FECHA_INICIO)
            && $hoy->lte($this->FECHA_FIN);
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        $hoy = Carbon::today();

        return $query
            ->where('ESTATUS', self::ESTATUS_ABIERTA)
            ->whereDate('FECHA_INICIO', '<=', $hoy)
            ->whereDate('FECHA_FIN', '>=', $hoy);
    }

    public static function resolverEstatusPorFecha(Carbon|string $fechaFin): string
    {
        $fin = $fechaFin instanceof Carbon ? $fechaFin : Carbon::parse($fechaFin);

        return Carbon::today()->gt($fin)
            ? self::ESTATUS_CERRADA
            : self::ESTATUS_ABIERTA;
    }
}
