<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login para todos los roles.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->buildUserPayload($user),
        ]);
    }

    /**
     * Registro manual de superadmin o admin (solo superadmin puede ejecutar esto).
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:superadmin,admin',
            'modules'  => 'nullable|array',
            'modules.*' => 'string|exists:modules,name',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => $request->role,
        ]);

        if ($request->role === 'admin' && !empty($request->modules)) {
            $moduleIds = Module::whereIn('name', $request->modules)->pluck('id');
            $user->modules()->sync($moduleIds);
        }

        $response = [
            'message' => 'Usuario registrado correctamente.',
            'user'    => $this->buildUserPayload($user),
        ];

        if ($request->role === 'admin') {
            $response['available_modules'] = $this->buildAvailableModules();
        }

        return response()->json($response, 201);
    }

    /**
     * Devuelve todos los módulos disponibles para asignar a un admin.
     */
    public function availableModules(): JsonResponse
    {
        return response()->json([
            'available_modules' => $this->buildAvailableModules(),
        ]);
    }

    /**
     * Auto-registro de empleado: valida número de empleado + RFC antes de crear cuenta.
     */
    public function registerEmpleado(Request $request): JsonResponse
    {
        $request->validate([
            'numero_empleado' => 'required|integer',
            'rfc'             => 'required|string',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|string|min:8|confirmed',
        ]);

        $empleado = Empleado::where('EMPLEADO_NO', $request->numero_empleado)
            ->where('EMPLEADO_RFC', strtoupper($request->rfc))
            ->first();

        if (!$empleado) {
            return response()->json([
                'message' => 'No se encontró ningún empleado con ese número y RFC.',
            ], 422);
        }

        $yaRegistrado = User::where('empleado_no', $empleado->EMPLEADO_NO)->exists();

        if ($yaRegistrado) {
            return response()->json([
                'message' => 'Este empleado ya tiene una cuenta registrada.',
            ], 422);
        }

        $user = User::create([
            'name'        => trim("{$empleado->EMPLEADO_NOMBRE} {$empleado->EMPLEADO_APELLIDO_PATERNO} {$empleado->EMPLEADO_APELLIDO_MATERNO}"),
            'email'       => $request->email,
            'password'    => $request->password,
            'role'        => 'empleado',
            'empleado_no' => $empleado->EMPLEADO_NO,
        ]);

        $empleado->update(['EMPLEADO_CORREO_ELECTRONICO' => $request->email]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada correctamente.',
            'token'   => $token,
            'user'    => $this->buildUserPayload($user->load('empleado')),
        ], 201);
    }

    /**
     * Retorna el usuario autenticado con su rol y módulos.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('empleado', 'modules');

        return response()->json([
            'user' => $this->buildUserPayload($user),
        ]);
    }

    /**
     * Revoca el token actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Construye el payload del usuario según su rol.
     */
    private function buildUserPayload(User $user): array
    {
        $payload = [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];

        $payload['modules'] = $this->buildModulesPayload($user);
        $payload['empleado'] = $user->role === 'empleado'
            ? $user->empleado
            : null;

        return $payload;
    }

    /**
     * Construye el catálogo de módulos disponibles para asignar a un admin.
     */
    private function buildAvailableModules(): array
    {
        return Module::all(['name', 'label', 'description'])
            ->map(fn($m) => [
                'name'        => $m->name,
                'label'       => $m->label,
                'description' => $m->description,
                'actions'     => Module::ADMIN_ACTIONS,
            ])->values()->toArray();
    }

    /**
     * Construye el array de módulos con sus acciones según el rol.
     */
    private function buildModulesPayload(User $user): array
    {
        if ($user->role === 'superadmin') {
            $allModules = Module::all();
            return $allModules->map(fn($m) => [
                'slug'    => $m->name,
                'label'   => $m->label,
                'actions' => Module::ADMIN_ACTIONS,
            ])->values()->toArray();
        }

        if ($user->role === 'admin') {
            $modules = $user->relationLoaded('modules')
                ? $user->modules
                : $user->modules()->get();

            return $modules->map(fn($m) => [
                'slug'    => $m->name,
                'label'   => $m->label,
                'actions' => Module::ADMIN_ACTIONS,
            ])->values()->toArray();
        }

        // empleado: módulos fijos con acciones limitadas
        $empleadoModules = Module::whereIn('name', Module::EMPLEADO_MODULES)->get()->keyBy('name');

        return collect(Module::EMPLEADO_MODULES)->map(function ($slug) use ($empleadoModules) {
            $mod = $empleadoModules->get($slug);
            return [
                'slug'    => $slug,
                'label'   => $mod ? $mod->label : $slug,
                'actions' => Module::EMPLEADO_ACTIONS[$slug] ?? ['ver'],
            ];
        })->toArray();
    }
}
