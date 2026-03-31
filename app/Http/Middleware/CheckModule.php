<?php

namespace App\Http\Middleware;

use App\Models\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // superadmin tiene acceso total sin restricciones
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        // empleado: bloquear DELETE en cualquier módulo
        if ($user->role === 'empleado' && $request->isMethod('DELETE')) {
            return response()->json([
                'message'   => 'No tienes permiso para eliminar registros.',
                'your_role' => 'empleado',
            ], 403);
        }

        // verificar si tiene acceso al módulo
        if (!$user->hasModule($module)) {
            return response()->json([
                'message'         => 'No tienes acceso al módulo requerido.',
                'required_module' => $module,
                'your_modules'    => $this->getUserModuleNames($user),
            ], 403);
        }

        // empleado: verificar que solo accede a sus propios recursos
        if ($user->role === 'empleado') {
            $request->merge(['_empleado_no' => $user->empleado_no]);
        }

        return $next($request);
    }

    private function getUserModuleNames($user): array
    {
        if ($user->role === 'empleado') {
            return Module::EMPLEADO_MODULES;
        }

        return $user->modules()->pluck('name')->toArray();
    }
}
