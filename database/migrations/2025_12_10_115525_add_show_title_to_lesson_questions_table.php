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
            // Adiciona a coluna booleana com valor padrão 'true'
            $table->boolean('show_title')->default(true)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropColumn('show_title');
        });
    }
};
