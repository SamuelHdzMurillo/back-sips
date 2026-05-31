<?php

use App\Support\StorageUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $convocatorias = DB::table('convocatorias')
            ->whereNotNull('CONVOCATORIA_RUTA_FOTO')
            ->get(['ID', 'CONVOCATORIA_RUTA_FOTO']);

        foreach ($convocatorias as $convocatoria) {
            $path = StorageUrl::relativePath($convocatoria->CONVOCATORIA_RUTA_FOTO);

            if ($path !== null) {
                DB::table('convocatorias')
                    ->where('ID', $convocatoria->ID)
                    ->update(['CONVOCATORIA_RUTA_FOTO' => $path]);
            }
        }
    }

    public function down(): void
    {
        // No reversible de forma segura.
    }
};
