<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfil_profesional', function (Blueprint $table) {
            $table->id('ID');
            $table->integer('EMPLEADO_NO');
            $table->text('PERFIL_DESCRIPCION')->nullable();

            $table->foreign('EMPLEADO_NO')
                ->references('EMPLEADO_NO')
                ->on('empleados')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfil_profesional');
    }
};
