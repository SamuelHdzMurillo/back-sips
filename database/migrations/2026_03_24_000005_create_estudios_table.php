<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudios', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('PERFIL_ID');

            $table->string('NIVEL', 50)->nullable();
            $table->string('CARRERA', 150)->nullable();
            $table->string('INSTITUCION', 150)->nullable();

            $table->date('FECHA_INICIO')->nullable();
            $table->date('FECHA_FIN')->nullable();

            $table->string('DOCUMENTO', 255)->nullable();

            $table->foreign('PERFIL_ID')
                ->references('ID')
                ->on('perfil_profesional')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudios');
    }
};
