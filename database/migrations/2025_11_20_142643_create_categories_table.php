<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Core Data
            $table->string('name');
            $table->string('slug');
            $table->string('type')->index(); // 'article', 'product', 'course'
            $table->text('description')->nullable();

            // Nested Set Columns (_lft, _rgt, parent_id)
            $table->nestedSet();

            $table->timestamps();

            // Garante que o slug é único DENTRO do mesmo tipo (ex: pode ter 'novidades' em blog e 'novidades' em produtos)
            $table->unique(['slug', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
