<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_plazas', function (Blueprint $table) {
            $table->id('ID');
            $table->integer('EMPLEADO_NO');

            $table->string('EMPLEADO_CCT_CLAVE', 20)->nullable();
            $table->string('EMPLEADO_CCT_NOMBRE', 150)->nullable();

            $table->string('EMPLEADO_PUESTO', 150)->nullable();
            $table->string('EMPLEADO_CATEGORIA', 100)->nullable();
            $table->string('EMPLEADO_FUNCION', 100)->nullable();

            $table->char('EMPLEADO_TIPO_PLAZA', 1)->nullable();
            $table->integer('HORAS')->nullable();

            $table->timestamps();

            $table->foreign('EMPLEADO_NO')
                ->references('EMPLEADO_NO')
                ->on('empleados')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_plazas');
    }
};
