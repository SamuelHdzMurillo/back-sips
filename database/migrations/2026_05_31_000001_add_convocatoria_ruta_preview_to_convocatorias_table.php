<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->string('CONVOCATORIA_RUTA_PREVIEW', 255)->nullable()->after('CONVOCATORIA_RUTA_FOTO');
        });
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropColumn('CONVOCATORIA_RUTA_PREVIEW');
        });
    }
};
