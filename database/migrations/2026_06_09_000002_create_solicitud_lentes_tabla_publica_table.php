<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_lentes_tabla_publica', function (Blueprint $table) {
            $table->id('ID');
            $table->boolean('COMPARTIR')->default(false);
            $table->string('TOKEN', 64)->unique();
            $table->string('TITULO', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_lentes_tabla_publica');
    }
};
