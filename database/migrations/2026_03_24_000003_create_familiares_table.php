<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familiares', function (Blueprint $table) {
            $table->id('ID');
            $table->integer('EMPLEADO_NO');

            $table->string('NOMBRE', 100);
            $table->string('APELLIDO_PATERNO', 100);
            $table->string('APELLIDO_MATERNO', 100);

            $table->string('PARENTESCO', 50);
            $table->string('DOCUMENTO_PARENTESCO', 255)->nullable();

            $table->foreign('EMPLEADO_NO')
                ->references('EMPLEADO_NO')
                ->on('empleados')
                ->onDelete('cascade');
        });

        DB::statement("
            ALTER TABLE familiares
            ADD COLUMN NOMBRE_COMPLETO VARCHAR(255)
            GENERATED ALWAYS AS (
                CONCAT(
                    APELLIDO_PATERNO, ' ',
                    APELLIDO_MATERNO, ' ',
                    NOMBRE
                )
            ) STORED
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('familiares');
    }
};
