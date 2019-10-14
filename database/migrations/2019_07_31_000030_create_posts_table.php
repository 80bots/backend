<?php

use App\Post;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('author_id')->nullable();
            $table->unsignedInteger('bot_id')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->text('content')->nullable();

            $table->enum('status', [
                Post::STATUS_DRAFT,
                Post::STATUS_ACTIVE,
                Post::STATUS_INACTIVE
            ])->default(Post::STATUS_DRAFT);

            $table->enum('type', [
                Post::TYPE_BOT,
                Post::TYPE_POST
            ])->default(Post::TYPE_POST);

            $table->timestamps();

            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('bot_id')
                ->references('id')
                ->on('bots')
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
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['author_id', 'bot_id']);
        });

        Schema::dropIfExists('posts');
    }
}
