<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_lentes', function (Blueprint $table) {
            $table->unsignedBigInteger('LOTE_ID')->nullable()->after('ID');
            $table->foreign('LOTE_ID')->references('ID')->on('lotes_lentes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_lentes', function (Blueprint $table) {
            $table->dropForeign(['LOTE_ID']);
            $table->dropColumn('LOTE_ID');
        });
    }
};
