<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_lentes_tabla_publica_lotes', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('TABLA_PUBLICA_ID');
            $table->unsignedBigInteger('LOTE_ID');

            $table->unique(['TABLA_PUBLICA_ID', 'LOTE_ID'], 'sl_tabla_publica_lotes_unique');

            $table->foreign('TABLA_PUBLICA_ID')
                ->references('ID')
                ->on('solicitud_lentes_tabla_publica')
                ->cascadeOnDelete();

            $table->foreign('LOTE_ID')
                ->references('ID')
                ->on('lotes_lentes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_lentes_tabla_publica_lotes');
    }
};
