<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoteLentes extends Model
{
    protected $table = 'lotes_lentes';
    protected $primaryKey = 'ID';

    protected $fillable = [
        'NOMBRE',
        'DESCRIPCION',
        'FECHA_INICIO',
        'FECHA_FIN',
        'ESTATUS',
    ];

    protected $casts = [
        'FECHA_INICIO' => 'date',
        'FECHA_FIN'    => 'date',
    ];

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudLentes::class, 'LOTE_ID', 'ID');
    }
}
