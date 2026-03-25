<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estudio extends Model
{
    protected $table = 'estudios';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'PERFIL_ID',
        'NIVEL',
        'CARRERA',
        'INSTITUCION',
        'FECHA_INICIO',
        'FECHA_FIN',
        'DOCUMENTO',
    ];

    protected $casts = [
        'PERFIL_ID'    => 'integer',
        'FECHA_INICIO' => 'date',
        'FECHA_FIN'    => 'date',
    ];

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(PerfilProfesional::class, 'PERFIL_ID', 'ID');
    }
}
