<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_tag', function (Blueprint $table) {
            $table->unsignedInteger('bot_id')->nullable();
            $table->unsignedInteger('tag_id')->nullable();

            $table->foreign('bot_id')
                ->references('id')
                ->on('bots')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('tag_id')
                ->references('id')
                ->on('tags')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bot_tag', function (Blueprint $table) {
            $table->dropForeign(['bot_id', 'tag_id']);
        });

        Schema::dropIfExists('bot_tag');
    }
}
