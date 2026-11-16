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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('nickname')->nullable();
            $table->string('username')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->string('phone_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('cpf')->nullable();
            $table->string('cnpj')->nullable();
            $table->boolean('is_active')->nullable()->default(0);
            $table->string('timezone')->nullable()->default('America/Sao_Paulo');

            $table->boolean('deleted_by_parent')->default(0);
            $table->string('verification_code', 4)->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->unsignedTinyInteger('verification_resend_count')->default(0)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
