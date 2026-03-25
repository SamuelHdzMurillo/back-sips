<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'EMPLEADO_NO';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'EMPLEADO_NO',
        'EMPLEADO_APELLIDO_PATERNO',
        'EMPLEADO_APELLIDO_MATERNO',
        'EMPLEADO_NOMBRE',
        'EMPLEADO_CURP',
        'EMPLEADO_RFC',
        'EMPLEADO_NSS',
        'EMPLEADO_TIPO_SANGRE',
        'EMPLEADO_FECHA_INGRESO',
        'EMPLEADO_ANTIGUEDAD',
        'EMPLEADO_ACTIVO',
        'EMPLEADO_CORREO_ELECTRONICO',
        'EMPLEADO_CLAVE_ACCESO',
        'EMPLEADO_RUTA_FOTO',
        'EMPLEADO_RUTA_QR',
        'EMPLEADO_ULTIMO_INGRESO',
    ];

    protected $hidden = [
        'EMPLEADO_CLAVE_ACCESO',
    ];

    protected $casts = [
        'EMPLEADO_FECHA_INGRESO'   => 'date',
        'EMPLEADO_ULTIMO_INGRESO'  => 'datetime',
        'EMPLEADO_NO'              => 'integer',
        'EMPLEADO_ANTIGUEDAD'      => 'integer',
    ];

    public function perfil(): HasOne
    {
        return $this->hasOne(PerfilProfesional::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }

    public function familiares(): HasMany
    {
        return $this->hasMany(Familiar::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }

    public function plazas(): HasMany
    {
        return $this->hasMany(EmpleadoPlaza::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }

    public function solicitudesLentes(): HasMany
    {
        return $this->hasMany(SolicitudLentes::class, 'EMPLEADO_NO', 'EMPLEADO_NO');
    }
}
