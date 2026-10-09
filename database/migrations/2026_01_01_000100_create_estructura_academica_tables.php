<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Institución, programa, cohorte y reglas versionadas (normativa traducida a reglas de sistema).
     */
    public function up()
    {
        Schema::create('instituciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('sigla', 20)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('programas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones');
            $table->string('nombre');
            $table->string('sigla', 20);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['institucion_id', 'sigla']);
        });

        // Versión de reglas de un programa (p. ej. "Reglamento VIP 28/2024")
        Schema::create('regla_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas');
            $table->string('version', 20);
            $table->string('nombre');
            $table->string('normativa')->nullable();
            $table->date('vigente_desde');
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->unique(['programa_id', 'version']);
        });

        Schema::create('cohortes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas');
            $table->foreignId('regla_version_id')->constrained('regla_versiones');
            $table->unsignedSmallInteger('anio');
            $table->string('nombre');
            $table->timestamps();
            $table->unique(['programa_id', 'anio']);
        });

        // Hitos que define la regla (se copian a cada tesis al inscribirla)
        Schema::create('hito_plantillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_version_id')->constrained('regla_versiones');
            $table->string('codigo', 30);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('orden');
            // Plazo en meses desde la inscripción de la tesis
            $table->unsignedSmallInteger('plazo_meses');
            $table->boolean('requiere_entrega')->default(true);
            $table->boolean('requiere_evaluacion')->default(false);
            $table->string('referencia_normativa')->nullable();
            $table->timestamps();
            $table->unique(['regla_version_id', 'codigo']);
        });

        // Estados posibles de una tesis
        Schema::create('estados_tesis', function (Blueprint $table) {
            $table->string('codigo', 30)->primary();
            $table->string('nombre');
            $table->string('color', 20);
            $table->unsignedSmallInteger('orden');
            $table->boolean('final')->default(false);
        });

        // Transiciones permitidas: quién (rol) puede mover una tesis de un estado a otro
        Schema::create('transiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_version_id')->constrained('regla_versiones');
            $table->string('estado_desde', 30);
            $table->string('estado_hacia', 30);
            $table->string('rol', 50);
            $table->string('accion');
            $table->boolean('requiere_fundamento')->default(true);
            $table->boolean('requiere_documento')->default(false);
            $table->timestamps();
            $table->foreign('estado_desde')->references('codigo')->on('estados_tesis');
            $table->foreign('estado_hacia')->references('codigo')->on('estados_tesis');
            $table->unique(['regla_version_id', 'estado_desde', 'estado_hacia', 'rol']);
        });

        // Ámbito de cada persona: a qué programa(s) pertenece y con qué rol
        Schema::create('programa_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas');
            $table->foreignId('user_id')->constrained('users');
            $table->string('rol', 50);
            $table->timestamps();
            $table->unique(['programa_id', 'user_id', 'rol']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('programa_user');
        Schema::dropIfExists('transiciones');
        Schema::dropIfExists('estados_tesis');
        Schema::dropIfExists('hito_plantillas');
        Schema::dropIfExists('cohortes');
        Schema::dropIfExists('regla_versiones');
        Schema::dropIfExists('programas');
        Schema::dropIfExists('instituciones');
    }
};
