<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChatterUserDiscussionPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chatter_user_discussion', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->index();
            $table->unsignedBigInteger('discussion_id')->index();

            $table->primary(['user_id', 'discussion_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('discussion_id')
                ->references('id')
                ->on('chatter_discussion')
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
        Schema::drop('chatter_user_discussion');
    }
}
