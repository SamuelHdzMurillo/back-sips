<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_lentes', function (Blueprint $table) {
            $table->id('ID');
            $table->integer('EMPLEADO_NO');
            $table->unsignedBigInteger('PLAZA_ID')->nullable();
            $table->unsignedBigInteger('FAMILIAR_ID')->nullable();

            $table->string('RECETA_ISTE_NUMERO', 100)->nullable();
            $table->string('RECETA_ISTE_ARCHIVO', 255)->nullable();

            $table->string('ESTATUS', 50)->default('PENDIENTE');
            $table->text('OBSERVACIONES')->nullable();

            $table->timestamp('CREATED_AT')->useCurrent();

            $table->foreign('EMPLEADO_NO')
                ->references('EMPLEADO_NO')
                ->on('empleados');

            $table->foreign('FAMILIAR_ID')
                ->references('ID')
                ->on('familiares');

            $table->foreign('PLAZA_ID')
                ->references('ID')
                ->on('empleado_plazas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_lentes');
    }
};
