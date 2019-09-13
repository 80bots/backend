<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDislikeMessageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dislike_message', function (Blueprint $table) {

            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();

            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::table('dislike_message', function (Blueprint $table) {
            $table->dropForeign(['message_id', 'user_id']);
        });

        Schema::dropIfExists('dislike_message');
    }
}
