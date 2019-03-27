<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBotIdIntoUserInstances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_instances', function (Blueprint $table) {
            $table->unsignedInteger('bot_id')->nullable()->after('user_id');

            $table->foreign('bot_id')
                ->references('id')->on('bots')
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
        Schema::table('user_instances', function (Blueprint $table) {
            $table->dropForeign(['bot_id']);
            $table->dropColumn('bot_id');
        });
    }
}
