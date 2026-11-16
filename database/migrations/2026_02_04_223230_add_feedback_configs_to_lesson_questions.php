<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->json('correct_feedback_config')->nullable()->after('correct_feedback');
            $table->json('wrong_feedback_config')->nullable()->after('wrong_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropColumn([
                'correct_feedback_config',
                'wrong_feedback_config',
            ]);
        });
    }
};
