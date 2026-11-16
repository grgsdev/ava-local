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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Instrutor/Criador

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('headline')->nullable(); // Frase curta de impacto
            $table->longText('description')->nullable(); // O que vou aprender?

            $table->decimal('price', 10, 2)->default(0); // 0 = Grátis
            $table->boolean('is_published')->default(false);

            // Nível (Iniciante, Avançado) e Duração total
            $table->string('level')->default('beginner');
            $table->integer('duration_minutes')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela Pivô para Categorias (Muitos-para-Muitos)
        Schema::create('category_course', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
