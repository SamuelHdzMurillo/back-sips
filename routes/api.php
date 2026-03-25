<?php

use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EmpleadoPlazaController;
use App\Http\Controllers\EstudioController;
use App\Http\Controllers\FamiliarController;
use App\Http\Controllers\PerfilProfesionalController;
use App\Http\Controllers\SolicitudLentesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Empleados
|--------------------------------------------------------------------------
*/
Route::get('/empleados', [EmpleadoController::class, 'index']);
Route::post('/empleados', [EmpleadoController::class, 'store']);
Route::get('/empleados/{no}', [EmpleadoController::class, 'show']);
Route::put('/empleados/{no}', [EmpleadoController::class, 'update']);
Route::delete('/empleados/{no}', [EmpleadoController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Perfil Profesional
|--------------------------------------------------------------------------
*/
Route::get('/empleados/{no}/perfil', [PerfilProfesionalController::class, 'showByEmpleado']);
Route::post('/empleados/{no}/perfil', [PerfilProfesionalController::class, 'storeByEmpleado']);
Route::put('/perfil/{id}', [PerfilProfesionalController::class, 'update']);
Route::delete('/perfil/{id}', [PerfilProfesionalController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Familiares
|--------------------------------------------------------------------------
*/
Route::get('/empleados/{no}/familiares', [FamiliarController::class, 'indexByEmpleado']);
Route::post('/empleados/{no}/familiares', [FamiliarController::class, 'storeByEmpleado']);
Route::get('/familiares/{id}', [FamiliarController::class, 'show']);
Route::put('/familiares/{id}', [FamiliarController::class, 'update']);
Route::delete('/familiares/{id}', [FamiliarController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Plazas
|--------------------------------------------------------------------------
*/
Route::get('/empleados/{no}/plazas', [EmpleadoPlazaController::class, 'indexByEmpleado']);
Route::post('/empleados/{no}/plazas', [EmpleadoPlazaController::class, 'storeByEmpleado']);
Route::get('/plazas/{id}', [EmpleadoPlazaController::class, 'show']);
Route::put('/plazas/{id}', [EmpleadoPlazaController::class, 'update']);
Route::delete('/plazas/{id}', [EmpleadoPlazaController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Estudios
|--------------------------------------------------------------------------
*/
Route::get('/perfil/{perfil_id}/estudios', [EstudioController::class, 'indexByPerfil']);
Route::post('/perfil/{perfil_id}/estudios', [EstudioController::class, 'storeByPerfil']);
Route::get('/estudios/{id}', [EstudioController::class, 'show']);
Route::put('/estudios/{id}', [EstudioController::class, 'update']);
Route::delete('/estudios/{id}', [EstudioController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Solicitud Lentes
|--------------------------------------------------------------------------
*/
Route::get('/solicitud-lentes', [SolicitudLentesController::class, 'index']);
Route::post('/solicitud-lentes', [SolicitudLentesController::class, 'store']);
Route::get('/solicitud-lentes/{id}', [SolicitudLentesController::class, 'show']);
Route::put('/solicitud-lentes/{id}', [SolicitudLentesController::class, 'update']);
Route::delete('/solicitud-lentes/{id}', [SolicitudLentesController::class, 'destroy']);
Route::get('/empleados/{no}/solicitud-lentes', [SolicitudLentesController::class, 'indexByEmpleado']);
