<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatoria_postulaciones', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('CONVOCATORIA_ID');
            $table->integer('EMPLEADO_NO');
            $table->timestamp('FECHA_POSTULACION')->useCurrent();
            $table->text('OBSERVACIONES')->nullable();

            $table->unique(['CONVOCATORIA_ID', 'EMPLEADO_NO']);

            $table->foreign('CONVOCATORIA_ID')
                ->references('ID')
                ->on('convocatorias')
                ->onDelete('cascade');

            $table->foreign('EMPLEADO_NO')
                ->references('EMPLEADO_NO')
                ->on('empleados');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatoria_postulaciones');
    }
};
