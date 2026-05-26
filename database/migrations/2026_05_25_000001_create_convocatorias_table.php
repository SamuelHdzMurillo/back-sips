<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->id('ID');

            $table->date('FECHA_INICIO');
            $table->date('FECHA_FIN');
            $table->string('DENOMINACION', 255);
            $table->string('NIVEL_SALARIAL', 100)->nullable();
            $table->string('ESTATUS_PLAZA', 50);
            $table->decimal('SUELDO', 12, 2)->nullable();
            $table->string('LUGAR_ADSCRIPCION', 255)->nullable();
            $table->string('TURNO', 50)->nullable();
            $table->string('ESTATUS', 50)->default('BORRADOR');

            $table->timestamps();

            $table->index('ESTATUS');
            $table->index('FECHA_INICIO');
            $table->index('FECHA_FIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatorias');
    }
};
