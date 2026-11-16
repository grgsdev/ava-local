<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            // Adiciona as colunas permitindo valores nulos e com precisão de 3 casas decimais (milissegundos)
            $table->decimal('loop_start_seconds', 10, 3)->nullable()->after('loop_seconds');
            $table->decimal('loop_end_seconds', 10, 3)->nullable()->after('loop_start_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            // Remove as colunas caso você precise reverter a migration no futuro
            $table->dropColumn(['loop_start_seconds', 'loop_end_seconds']);
        });
    }
};
