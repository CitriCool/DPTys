<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora inalterable: solo se insertan filas. Cada evento guarda el hash del
     * anterior, por lo que cualquier alteración rompe la cadena y es detectable.
     */
    public function up()
    {
        Schema::create('eventos_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('rol_ejercido', 50)->nullable();
            $table->string('accion', 60);
            $table->string('entidad_tipo', 60);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->unsignedBigInteger('tesis_id')->nullable()->index();
            $table->text('datos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->char('hash_anterior', 64)->nullable();
            $table->char('hash', 64);
            $table->dateTime('created_at');
        });

        // En SQL Server se bloquea a nivel de base cualquier UPDATE o DELETE sobre la bitácora
        if (DB::getDriverName() === 'sqlsrv') {
            DB::unprepared("
                CREATE TRIGGER trg_eventos_auditoria_inmutable
                ON eventos_auditoria
                INSTEAD OF UPDATE, DELETE
                AS
                BEGIN
                    RAISERROR('La bitacora de auditoria es inalterable: solo se permiten inserciones.', 16, 1);
                    ROLLBACK TRANSACTION;
                END
            ");
        }
    }

    public function down()
    {
        if (DB::getDriverName() === 'sqlsrv') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_eventos_auditoria_inmutable');
        }
        Schema::dropIfExists('eventos_auditoria');
    }
};
