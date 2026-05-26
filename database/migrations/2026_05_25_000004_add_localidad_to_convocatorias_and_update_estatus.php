<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->string('LOCALIDAD', 50)->default('LOCAL')->after('TURNO');
            $table->index('LOCALIDAD');
        });

        DB::table('convocatorias')->where('ESTATUS', 'BORRADOR')->update(['ESTATUS' => 'ABIERTA']);

        DB::table('convocatorias')
            ->whereDate('FECHA_FIN', '<', now()->toDateString())
            ->update(['ESTATUS' => 'CERRADA']);
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropIndex(['LOCALIDAD']);
            $table->dropColumn('LOCALIDAD');
        });
    }
};
