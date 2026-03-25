<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerfilProfesional extends Model
{
    protected $table = 'perfil_profesional';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'EMPLEADO_NO',
        'PERFIL_DESCRIPCION',
    ];

    protected $casts = [
        'EMPLEADO_NO' => 'integer',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }

    public function estudios(): HasMany
    {
        return $this->hasMany(Estudio::class, 'PERFIL_ID', 'ID');
    }
}
