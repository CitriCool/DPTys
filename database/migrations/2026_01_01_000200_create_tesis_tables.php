<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expediente de tesis: responsables, hitos, entregas con versiones, observaciones,
     * evaluaciones, decisiones y actas.
     *
     * Nota: sin borrados en cascada (SQL Server no admite múltiples rutas de cascada
     * y el expediente no se borra: se cierra, suspende o retira).
     */
    public function up()
    {
        Schema::create('tesis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones');
            $table->foreignId('programa_id')->constrained('programas');
            $table->foreignId('cohorte_id')->constrained('cohortes');
            $table->foreignId('regla_version_id')->constrained('regla_versiones');
            $table->foreignId('doctorando_id')->constrained('users');
            $table->string('codigo', 30)->unique();
            $table->string('titulo', 500);
            $table->text('resumen')->nullable();
            $table->string('estado', 30);
            $table->date('fecha_inscripcion');
            $table->string('resolucion')->nullable();
            // Referencia a registros oficiales (matrícula u otro) sin duplicarlos
            $table->string('referencia_externa')->nullable();
            $table->timestamps();
            $table->foreign('estado')->references('codigo')->on('estados_tesis');
        });

        Schema::create('tesis_responsables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis');
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo', 20); // guia | coguia
            $table->date('desde');
            $table->date('hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('tesis_hitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis');
            $table->foreignId('hito_plantilla_id')->nullable()->constrained('hito_plantillas');
            $table->string('nombre');
            $table->unsignedSmallInteger('orden');
            $table->date('fecha_limite');
            // pendiente | entregado | observado | aprobado
            $table->string('estado', 20)->default('pendiente');
            $table->boolean('requiere_entrega')->default(true);
            $table->boolean('requiere_evaluacion')->default(false);
            $table->string('referencia_normativa')->nullable();
            $table->dateTime('fecha_cumplimiento')->nullable();
            $table->timestamps();
        });

        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis');
            $table->foreignId('tesis_hito_id')->constrained('tesis_hitos');
            $table->foreignId('user_id')->constrained('users');
            $table->string('titulo');
            $table->timestamps();
        });

        // Cada archivo subido es una versión inmutable con su huella SHA-256
        Schema::create('entrega_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedSmallInteger('numero');
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->unsignedBigInteger('tamano');
            $table->string('mime', 150)->nullable();
            $table->char('sha256', 64);
            $table->text('comentario')->nullable();
            $table->timestamps();
            $table->unique(['entrega_id', 'numero']);
        });

        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis');
            $table->foreignId('tesis_hito_id')->nullable()->constrained('tesis_hitos');
            $table->foreignId('entrega_version_id')->nullable()->constrained('entrega_versiones');
            $table->foreignId('parent_id')->nullable()->constrained('observaciones');
            $table->foreignId('user_id')->constrained('users');
            $table->string('rol_ejercido', 50);
            $table->text('texto');
            $table->boolean('resuelta')->default(false);
            $table->timestamps();
        });

        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis');
            $table->foreignId('tesis_hito_id')->nullable()->constrained('tesis_hitos');
            $table->foreignId('entrega_version_id')->nullable()->constrained('entrega_versiones');
            $table->foreignId('evaluador_id')->constrained('users');
            $table->foreignId('asignado_por')->constrained('users');
            $table->date('acceso_hasta');
            // aprobado | aprobado_con_observaciones | rechazado
            $table->string('dictamen', 40)->nullable();
            $table->text('fundamento')->nullable();
            $table->dateTime('fecha_dictamen')->nullable();
            $table->timestamps();
        });

        // Decisiones: cambios de estado y excepciones (prórroga, cambio de guía, suspensión, retiro)
        Schema::create('decisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis');
            $table->foreignId('user_id')->constrained('users');
            $table->string('rol_ejercido', 50);
            $table->string('tipo', 40);
            $table->string('estado_desde', 30)->nullable();
            $table->string('estado_hacia', 30)->nullable();
            $table->text('fundamento');
            $table->string('documento_nombre')->nullable();
            $table->string('documento_ruta')->nullable();
            $table->char('documento_sha256', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('actas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis');
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo', 30); // inscripcion | examen | cierre | otra
            $table->string('numero')->nullable();
            $table->date('fecha');
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->char('sha256', 64);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('actas');
        Schema::dropIfExists('decisiones');
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('observaciones');
        Schema::dropIfExists('entrega_versiones');
        Schema::dropIfExists('entregas');
        Schema::dropIfExists('tesis_hitos');
        Schema::dropIfExists('tesis_responsables');
        Schema::dropIfExists('tesis');
    }
};
