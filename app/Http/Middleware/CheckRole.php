<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'message'       => 'No tienes el rol requerido para esta acción.',
                'required_role' => implode(' o ', $roles),
                'your_role'     => $user?->role ?? 'sin autenticar',
            ], 403);
        }

        return $next($request);
    }
}
