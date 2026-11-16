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
            $table->integer('loop_seconds')->default(0)->after('timestamp_seconds')
                ->comment('Se > 0, o vídeo entra em loop de X segundos antes da pergunta');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropColumn('loop_seconds');
        });
    }
};
