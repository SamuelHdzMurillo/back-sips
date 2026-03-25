<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Familiar extends Model
{
    protected $table = 'familiares';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'EMPLEADO_NO',
        'NOMBRE',
        'APELLIDO_PATERNO',
        'APELLIDO_MATERNO',
        'PARENTESCO',
        'DOCUMENTO_PARENTESCO',
    ];

    protected $casts = [
        'EMPLEADO_NO' => 'integer',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }

    public function solicitudesLentes(): HasMany
    {
        return $this->hasMany(SolicitudLentes::class, 'FAMILIAR_ID', 'ID');
    }
}
