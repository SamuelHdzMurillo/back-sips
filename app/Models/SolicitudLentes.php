<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudLentes extends Model
{
    protected $table = 'solicitud_lentes';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    const CREATED_AT = 'CREATED_AT';

    protected $fillable = [
        'LOTE_ID',
        'EMPLEADO_NO',
        'PLAZA_ID',
        'FAMILIAR_ID',
        'RECETA_ISTE_NUMERO',
        'RECETA_ISTE_ARCHIVO',
        'ESTATUS',
        'OBSERVACIONES',
    ];

    protected $casts = [
        'LOTE_ID'     => 'integer',
        'EMPLEADO_NO' => 'integer',
        'PLAZA_ID'    => 'integer',
        'FAMILIAR_ID' => 'integer',
        'CREATED_AT'  => 'datetime',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteLentes::class, 'LOTE_ID', 'ID');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }

    public function familiar(): BelongsTo
    {
        return $this->belongsTo(Familiar::class, 'FAMILIAR_ID', 'ID');
    }

    public function plaza(): BelongsTo
    {
        return $this->belongsTo(EmpleadoPlaza::class, 'PLAZA_ID', 'ID');
    }
}
