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
        Schema::table('question_options', function (Blueprint $table) {
            $table->string('correct_action')->nullable()->after('is_correct');
            $table->decimal('correct_seek_to', 10, 3)->nullable()->after('correct_action');

            $table->string('wrong_action')->nullable()->after('correct_seek_to');
            $table->decimal('wrong_seek_to', 10, 3)->nullable()->after('wrong_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('correct_action');
            $table->dropColumn('correct_seek_to');
            $table->dropColumn('wrong_action');
            $table->dropColumn('wrong_seek_to');
        });
    }
};
