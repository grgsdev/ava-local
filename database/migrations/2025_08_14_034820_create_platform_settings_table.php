<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    /**
     * Run the migrations.
     * @version V1
     * @return void
     */
    public function up()
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();

            $table->string('platform_name', 255);
            $table->json('contact_info')->nullable();
            $table->json('social_links')->nullable();
            $table->text('physical_address')->nullable();
            $table->boolean('deleted_by_parent')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists('platform_settings');
    }
};
