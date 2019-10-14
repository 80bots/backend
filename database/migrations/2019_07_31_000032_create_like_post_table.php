<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLikePostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('like_post', function (Blueprint $table) {

            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();

            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
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
        Schema::table('like_post', function (Blueprint $table) {
            $table->dropForeign(['post_id', 'user_id']);
        });

        Schema::dropIfExists('like_post');
    }
}
