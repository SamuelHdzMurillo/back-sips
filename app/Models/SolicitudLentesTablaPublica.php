<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class SolicitudLentesTablaPublica extends Model
{
    protected $table = 'solicitud_lentes_tabla_publica';
    protected $primaryKey = 'ID';

    protected $fillable = [
        'COMPARTIR',
        'TOKEN',
        'TITULO',
    ];

    protected $casts = [
        'COMPARTIR' => 'boolean',
    ];

    public static function configuracion(): self
    {
        return static::query()->firstOrCreate(
            ['ID' => 1],
            ['TOKEN' => Str::random(48), 'COMPARTIR' => false]
        );
    }

    public function lotes(): BelongsToMany
    {
        return $this->belongsToMany(
            LoteLentes::class,
            'solicitud_lentes_tabla_publica_lotes',
            'TABLA_PUBLICA_ID',
            'LOTE_ID',
            'ID',
            'ID'
        )->withCount('solicitudes');
    }
}
