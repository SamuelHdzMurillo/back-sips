<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_lentes', function (Blueprint $table) {
            $table->id('ID');
            $table->string('NOMBRE', 150);
            $table->text('DESCRIPCION')->nullable();
            $table->date('FECHA_INICIO');
            $table->date('FECHA_FIN');
            $table->string('ESTATUS', 50)->default('ABIERTO');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_lentes');
    }
};
