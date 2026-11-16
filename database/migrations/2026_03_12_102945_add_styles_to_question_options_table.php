<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->string('background_color')->default('transparent')->after('show_text');
            $table->string('text_align')->default('center')->after('background_color');
            $table->string('vertical_align')->default('center')->after('text_align');
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn(['background_color', 'text_align', 'vertical_align']);
        });
    }
};
