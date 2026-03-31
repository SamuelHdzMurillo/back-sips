<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Lista todos los módulos disponibles en el sistema.
     */
    public function index(): JsonResponse
    {
        $modules = Module::all(['id', 'name', 'label', 'description']);

        return response()->json(['data' => $modules]);
    }

    /**
     * Asigna módulos a un admin.
     */
    public function assignToUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'   => 'required|integer|exists:users,id',
            'modules'   => 'required|array|min:1',
            'modules.*' => 'string|exists:modules,name',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Solo se pueden asignar módulos a usuarios con rol admin.',
            ], 422);
        }

        $moduleIds = Module::whereIn('name', $request->modules)->pluck('id');
        $user->modules()->syncWithoutDetaching($moduleIds);

        return response()->json([
            'message' => 'Módulos asignados correctamente.',
            'user_id' => $user->id,
            'modules' => $user->modules()->get(['name', 'label']),
        ]);
    }

    /**
     * Quita módulos a un admin.
     */
    public function revokeFromUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'   => 'required|integer|exists:users,id',
            'modules'   => 'required|array|min:1',
            'modules.*' => 'string|exists:modules,name',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Solo se pueden gestionar módulos de usuarios con rol admin.',
            ], 422);
        }

        $moduleIds = Module::whereIn('name', $request->modules)->pluck('id');
        $user->modules()->detach($moduleIds);

        return response()->json([
            'message' => 'Módulos revocados correctamente.',
            'user_id' => $user->id,
            'modules' => $user->modules()->get(['name', 'label']),
        ]);
    }

    /**
     * Lista los módulos asignados a un admin específico.
     */
    public function userModules(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Este endpoint solo aplica para usuarios con rol admin.',
            ], 422);
        }

        return response()->json([
            'user_id' => $user->id,
            'name'    => $user->name,
            'modules' => $user->modules()->get(['name', 'label']),
        ]);
    }
}
