<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'admin', 'empleado'])->default('empleado')->after('email');
            $table->integer('empleado_no')->nullable()->after('role');

            $table->foreign('empleado_no')
                  ->references('EMPLEADO_NO')
                  ->on('empleados')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empleado_no']);
            $table->dropColumn(['role', 'empleado_no']);
        });
    }
};
