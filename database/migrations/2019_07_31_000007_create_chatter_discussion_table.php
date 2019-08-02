<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChatterDiscussionTable extends Migration
{
    public function up()
    {
        Schema::create('chatter_discussion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedInteger('chatter_category_id')->default('1');
            $table->boolean('sticky')->default(false);
            $table->unsignedInteger('views')->default('0');
            $table->boolean('answered')->default(0);
            $table->unsignedBigInteger('popularity')->default('0');
            $table->string('color', 20)->nullable()->default('#232629');
            $table->timestamp('last_reply_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('chatter_category_id')
                ->references('id')
                ->on('chatter_categories')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('chatter_discussion', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'chatter_category_id']);
        });

        Schema::drop('chatter_discussion');
    }
}
