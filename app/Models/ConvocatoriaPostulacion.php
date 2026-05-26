<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvocatoriaPostulacion extends Model
{
    protected $table = 'convocatoria_postulaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    const CREATED_AT = 'FECHA_POSTULACION';

    protected $fillable = [
        'CONVOCATORIA_ID',
        'EMPLEADO_NO',
        'OBSERVACIONES',
    ];

    protected $casts = [
        'CONVOCATORIA_ID'   => 'integer',
        'EMPLEADO_NO'       => 'integer',
        'FECHA_POSTULACION' => 'datetime',
    ];

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class, 'CONVOCATORIA_ID', 'ID');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }
}
