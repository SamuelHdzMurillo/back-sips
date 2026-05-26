<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
        'ESTATUS',
    ];

    protected $casts = [
        'FECHA_INICIO' => 'date',
        'FECHA_FIN'    => 'date',
        'SUELDO'       => 'decimal:2',
    ];

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
