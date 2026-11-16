<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_lesson_id')->constrained()->cascadeOnDelete();

            $table->string('title'); // A pergunta em si
            $table->integer('timestamp_seconds'); // Momento exato do vídeo (em segundos)
            $table->string('type')->default('single_choice'); // 'single_choice', 'multiple_choice'

            // Lógica ao Acertar
            $table->string('correct_action')->default('continue'); // 'continue', 'seek'
            $table->integer('correct_seek_to')->nullable(); // Para onde pular (segundos)
            $table->text('correct_feedback')->nullable(); // Mensagem de parabéns (opcional)

            // Lógica ao Errar
            $table->string('wrong_action')->default('continue'); // 'continue', 'seek'
            $table->integer('wrong_seek_to')->nullable(); // Geralmente volta para rever o conteúdo
            $table->text('wrong_feedback')->nullable(); // "Que pena, reveja o conceito X"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_questions');
    }
};
