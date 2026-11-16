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
            $table->integer('title_font_size')->default(20)->after('title_color'); // Tamanho em px
            $table->string('title_font_family')->default('Arial, sans-serif')->after('title_font_size');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->integer('text_font_size')->default(14)->after('text_color');
            $table->string('text_font_family')->default('Arial, sans-serif')->after('text_font_size');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropColumn(['title_font_size', 'title_font_family']);
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn(['text_font_size', 'text_font_family']);
        });
    }
};
