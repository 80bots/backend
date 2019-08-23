<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            //$table->integerIncrements('id');
            $table->unsignedInteger('bot_id')->nullable();
            $table->unsignedInteger('tag_id')->nullable();
            //$table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            //$table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));

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
