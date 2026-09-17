<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_template_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['pdf', 'docx', 'link']);
            $table->string('file_path')->nullable(); // null si type = link
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_exports');
    }
};
