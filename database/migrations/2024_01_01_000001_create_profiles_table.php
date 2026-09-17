<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Datos de contacto / personales
            $table->string('full_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();

            // Perfil profesional
            $table->string('headline')->nullable(); // ej: "Desarrollador Backend Jr"
            $table->text('summary')->nullable();     // resumen / perfil profesional

            // Fotografía
            $table->string('photo_path')->nullable();      // foto original subida
            $table->string('photo_ats_path')->nullable();  // foto procesada por IA

            // Enlace público
            $table->string('public_slug')->unique()->nullable();
            $table->boolean('is_public')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
