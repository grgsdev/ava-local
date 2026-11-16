<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->json('confirmation_config')
                ->nullable()
                ->after('wrong_feedback_config');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropColumn('confirmation_config');
        });
    }
};
