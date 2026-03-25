<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmpleadoPlaza extends Model
{
    protected $table = 'empleado_plazas';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';

    protected $fillable = [
        'EMPLEADO_NO',
        'EMPLEADO_CCT_CLAVE',
        'EMPLEADO_CCT_NOMBRE',
        'EMPLEADO_PUESTO',
        'EMPLEADO_CATEGORIA',
        'EMPLEADO_FUNCION',
        'EMPLEADO_TIPO_PLAZA',
        'HORAS',
    ];

    protected $casts = [
        'EMPLEADO_NO' => 'integer',
        'HORAS'       => 'integer',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }

    public function solicitudesLentes(): HasMany
    {
        return $this->hasMany(SolicitudLentes::class, 'PLAZA_ID', 'ID');
    }
}
