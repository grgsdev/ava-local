<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            // Coordenadas JSON (ex: {top: 10, left: 50})
            $table->json('modal_coordinates')->nullable()->after('type');
            $table->json('question_coordinates')->nullable()->after('modal_coordinates');

            // Cores
            $table->string('title_color', 7)->default('#000000')->after('title');
            $table->string('background_color', 7)->default('#ffffff')->after('title_color');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->string('text_color', 7)->default('#000000')->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropColumn([
                'modal_coordinates',
                'question_coordinates',
                'title_color',
                'background_color'
            ]);
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('text_color');
        });
    }
};
