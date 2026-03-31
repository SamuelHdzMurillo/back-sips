<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = ['name', 'label', 'description'];

    /**
     * Módulos que el rol empleado puede acceder (siempre fijos, sin asignación).
     */
    const EMPLEADO_MODULES = [
        'solicitud-lentes',
        'estudios',
        'familiares',
        'perfil',
        'plazas',
    ];

    /**
     * Acciones permitidas por módulo para el rol empleado.
     */
    const EMPLEADO_ACTIONS = [
        'solicitud-lentes' => ['ver', 'crear', 'editar'],
        'estudios'         => ['ver', 'crear', 'editar'],
        'familiares'       => ['ver', 'crear', 'editar'],
        'perfil'           => ['ver', 'editar'],
        'plazas'           => ['ver'],
    ];

    /**
     * Acciones permitidas para superadmin y admin.
     */
    const ADMIN_ACTIONS = ['ver', 'crear', 'editar', 'eliminar'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_modules');
    }
}
