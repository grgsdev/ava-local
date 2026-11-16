<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->foreignId('correct_target_lesson_id')->nullable()->after('correct_action')->constrained('course_lessons')->nullOnDelete();
            $table->foreignId('wrong_target_lesson_id')->nullable()->after('wrong_action')->constrained('course_lessons')->nullOnDelete();
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->foreignId('correct_target_lesson_id')->nullable()->after('correct_action')->constrained('course_lessons')->nullOnDelete();
            $table->foreignId('wrong_target_lesson_id')->nullable()->after('wrong_action')->constrained('course_lessons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropForeign(['correct_target_lesson_id']);
            $table->dropForeign(['wrong_target_lesson_id']);
            $table->dropColumn(['correct_target_lesson_id', 'wrong_target_lesson_id']);
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropForeign(['correct_target_lesson_id']);
            $table->dropForeign(['wrong_target_lesson_id']);
            $table->dropColumn(['correct_target_lesson_id', 'wrong_target_lesson_id']);
        });
    }
};
