<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            // Este é o novo fundo interativo
            $table->string('global_background_color')->default('transparent')->after('type');
            $table->float('global_background_opacity')->default(1)->after('global_background_color');
            $table->json('global_background_coordinates')->nullable()->after('global_background_opacity');

            // Textos do Fundo interativo
            $table->text('global_background_text')->nullable()->after('global_background_coordinates');
            $table->string('global_background_text_color')->default('#ffffff')->after('global_background_text');
            $table->integer('global_background_text_font_size')->default(24)->after('global_background_text_color');
            $table->string('global_background_text_font_family')->default('Arial, sans-serif')->after('global_background_text_font_size');
            $table->string('global_background_text_align')->default('center')->after('global_background_text_font_family');
            $table->string('global_background_vertical_align')->default('center')->after('global_background_text_align');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            $table->dropColumn([
                'global_background_color',
                'global_background_opacity',
                'global_background_coordinates',
                'global_background_text',
                'global_background_text_color',
                'global_background_text_font_size',
                'global_background_text_font_family',
                'global_background_text_align',
                'global_background_vertical_align'
            ]);
        });
    }
};
