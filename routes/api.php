<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EmpleadoPlazaController;
use App\Http\Controllers\EstudioController;
use App\Http\Controllers\FamiliarController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PerfilProfesionalController;
use App\Http\Controllers\LoteLentesController;
use App\Http\Controllers\SolicitudLentesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticación (público)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register-empleado', [AuthController::class, 'registerEmpleado']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/available-modules', [AuthController::class, 'availableModules'])
            ->middleware('role:superadmin');
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('role:superadmin');
    });
});

/*
|--------------------------------------------------------------------------
| Gestión de módulos (solo superadmin)
|--------------------------------------------------------------------------
*/
Route::prefix('modules')->middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    Route::get('/', [ModuleController::class, 'index']);
    Route::post('/assign', [ModuleController::class, 'assignToUser']);
    Route::delete('/revoke', [ModuleController::class, 'revokeFromUser']);
    Route::get('/user/{userId}', [ModuleController::class, 'userModules']);
});

/*
|--------------------------------------------------------------------------
| Empleados
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'module:empleados'])->group(function () {
    Route::get('/empleados', [EmpleadoController::class, 'index']);
    Route::post('/empleados', [EmpleadoController::class, 'store']);
    Route::get('/empleados/{no}', [EmpleadoController::class, 'show']);
    Route::put('/empleados/{no}', [EmpleadoController::class, 'update']);
    Route::delete('/empleados/{no}', [EmpleadoController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Perfil Profesional
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'module:perfil'])->group(function () {
    Route::get('/empleados/{no}/perfil', [PerfilProfesionalController::class, 'showByEmpleado']);
    Route::post('/empleados/{no}/perfil', [PerfilProfesionalController::class, 'storeByEmpleado']);
    Route::put('/perfil/{id}', [PerfilProfesionalController::class, 'update']);
    Route::delete('/perfil/{id}', [PerfilProfesionalController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Familiares
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'module:familiares'])->group(function () {
    Route::get('/empleados/{no}/familiares', [FamiliarController::class, 'indexByEmpleado']);
    Route::post('/empleados/{no}/familiares', [FamiliarController::class, 'storeByEmpleado']);
    Route::get('/familiares/{id}', [FamiliarController::class, 'show']);
    Route::put('/familiares/{id}', [FamiliarController::class, 'update']);
    Route::delete('/familiares/{id}', [FamiliarController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Plazas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'module:plazas'])->group(function () {
    Route::get('/empleados/{no}/plazas', [EmpleadoPlazaController::class, 'indexByEmpleado']);
    Route::post('/empleados/{no}/plazas', [EmpleadoPlazaController::class, 'storeByEmpleado']);
    Route::get('/plazas/{id}', [EmpleadoPlazaController::class, 'show']);
    Route::put('/plazas/{id}', [EmpleadoPlazaController::class, 'update']);
    Route::delete('/plazas/{id}', [EmpleadoPlazaController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Estudios
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'module:estudios'])->group(function () {
    Route::get('/perfil/{perfil_id}/estudios', [EstudioController::class, 'indexByPerfil']);
    Route::post('/perfil/{perfil_id}/estudios', [EstudioController::class, 'storeByPerfil']);
    Route::get('/estudios/{id}', [EstudioController::class, 'show']);
    Route::put('/estudios/{id}', [EstudioController::class, 'update']);
    Route::delete('/estudios/{id}', [EstudioController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Solicitud Lentes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'module:solicitud-lentes'])->group(function () {
    Route::get('/solicitud-lentes', [SolicitudLentesController::class, 'index']);
    Route::post('/solicitud-lentes', [SolicitudLentesController::class, 'store']);
    Route::get('/solicitud-lentes/{id}', [SolicitudLentesController::class, 'show']);
    Route::put('/solicitud-lentes/{id}', [SolicitudLentesController::class, 'update']);
    Route::delete('/solicitud-lentes/{id}', [SolicitudLentesController::class, 'destroy']);
    Route::get('/empleados/{no}/solicitud-lentes', [SolicitudLentesController::class, 'indexByEmpleado']);
});

/*
|--------------------------------------------------------------------------
| Lotes Lentes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'module:lotes-lentes'])->group(function () {
    Route::get('/lotes-lentes/periodo', [LoteLentesController::class, 'solicitudesByPeriodo']);
    Route::get('/lotes-lentes', [LoteLentesController::class, 'index']);
    Route::post('/lotes-lentes', [LoteLentesController::class, 'store']);
    Route::get('/lotes-lentes/{id}', [LoteLentesController::class, 'show']);
    Route::put('/lotes-lentes/{id}', [LoteLentesController::class, 'update']);
    Route::delete('/lotes-lentes/{id}', [LoteLentesController::class, 'destroy']);
    Route::post('/lotes-lentes/{id}/solicitudes/{solicitudId}', [LoteLentesController::class, 'addSolicitud']);
    Route::delete('/lotes-lentes/{id}/solicitudes/{solicitudId}', [LoteLentesController::class, 'removeSolicitud']);
});
