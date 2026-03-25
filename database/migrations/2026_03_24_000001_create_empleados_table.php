<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->integer('EMPLEADO_NO')->primary();

            $table->string('EMPLEADO_APELLIDO_PATERNO', 100);
            $table->string('EMPLEADO_APELLIDO_MATERNO', 100);
            $table->string('EMPLEADO_NOMBRE', 100);

            $table->string('EMPLEADO_CURP', 20)->nullable();
            $table->string('EMPLEADO_RFC', 20)->nullable();
            $table->string('EMPLEADO_NSS', 20)->nullable();
            $table->string('EMPLEADO_TIPO_SANGRE', 5)->nullable();

            $table->date('EMPLEADO_FECHA_INGRESO')->nullable();
            $table->integer('EMPLEADO_ANTIGUEDAD')->nullable();
            $table->char('EMPLEADO_ACTIVO', 1)->nullable();

            $table->string('EMPLEADO_CORREO_ELECTRONICO', 150)->nullable();
            $table->string('EMPLEADO_CLAVE_ACCESO', 255)->nullable();

            $table->string('EMPLEADO_RUTA_FOTO', 255)->nullable();
            $table->string('EMPLEADO_RUTA_QR', 255)->nullable();

            $table->dateTime('EMPLEADO_ULTIMO_INGRESO')->nullable();
        });

        DB::statement("
            ALTER TABLE empleados
            ADD COLUMN EMPLEADO_NOMBRE_COMPLETO VARCHAR(255)
            GENERATED ALWAYS AS (
                CONCAT(
                    EMPLEADO_APELLIDO_PATERNO, ' ',
                    EMPLEADO_APELLIDO_MATERNO, ' ',
                    EMPLEADO_NOMBRE
                )
            ) STORED
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
