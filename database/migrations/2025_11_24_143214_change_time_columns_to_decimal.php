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
        // Alteramos para decimal com 3 casas (milissegundos)
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->decimal('timestamp_seconds', 10, 3)->change();
            $table->decimal('loop_seconds', 10, 3)->change();
            $table->decimal('correct_seek_to', 10, 3)->nullable()->change();
            $table->decimal('wrong_seek_to', 10, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->integer('timestamp_seconds')->change();
            $table->integer('loop_seconds')->change();
            $table->integer('correct_seek_to')->change();
            $table->integer('wrong_seek_to')->change();
        });
    }
};
