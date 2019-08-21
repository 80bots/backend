<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChatterPostTable extends Migration
{
    public function up()
    {
        Schema::create('chatter_post', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('chatter_discussion_id');
            $table->text('body');
            $table->boolean('markdown')->default(0);
            $table->boolean('locked')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('chatter_discussion_id')
                ->references('id')
                ->on('chatter_discussion')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('chatter_post', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'chatter_discussion_id']);
        });

        Schema::drop('chatter_post');
    }
}
