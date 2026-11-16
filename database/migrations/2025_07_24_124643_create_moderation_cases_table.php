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
        Schema::create('moderation_cases', function (Blueprint $table) {
            $table->id();

            // A qual comentário este caso se refere?
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');

            // Status do caso: pendente, aprovado, rejeitado, etc.
            $table->string('status')->default('pending');

            // Quem moderou e quando? (nullable porque pode estar pendente)
            $table->foreignId('moderator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();

            // Notas do moderador sobre a ação tomada
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_cases');
    }
};
