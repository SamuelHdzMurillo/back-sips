<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_lentes', function (Blueprint $table) {
            $table->string('OPTICA_NOMBRE', 255)->nullable()->after('RECETA_ISTE_ARCHIVO');
            $table->string('FACTURA_COMPRA_ARCHIVO', 255)->nullable()->after('OPTICA_NOMBRE');
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_lentes', function (Blueprint $table) {
            $table->dropColumn(['OPTICA_NOMBRE', 'FACTURA_COMPRA_ARCHIVO']);
        });
    }
};
