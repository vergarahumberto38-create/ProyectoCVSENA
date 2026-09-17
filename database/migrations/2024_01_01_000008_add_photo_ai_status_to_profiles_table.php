<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // pending -> en cola / processing -> llamando a la API / completed / failed
            $table->string('photo_ai_status')->nullable()->after('photo_ats_path');
            $table->text('photo_ai_error')->nullable()->after('photo_ai_status');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['photo_ai_status', 'photo_ai_error']);
        });
    }
};
