<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlatformAndDescriptionFieldIntoBots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->text('description')->nullable()->after('bot_name');
            $table->unsignedInteger('platform_id')->nullable()->after('id');
            $table->foreign('platform_id')
                ->references('id')->on('platforms')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropForeign(['platform_id']);
            $table->dropColumn(['platform_id', 'description']);
        });
    }
}
