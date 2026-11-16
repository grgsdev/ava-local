<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->unique();
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropUnique(['course_id']);
            $table->dropColumn('course_id');
        });
    }
};
