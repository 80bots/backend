<?php

use App\Message;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('author_id');
            $table->text('content')->nullable();

            $table->enum('status', [
                Message::STATUS_ACTIVE,
                Message::STATUS_INACTIVE
            ])->default(Message::STATUS_ACTIVE);

            $table->boolean('moderation')->default(false);

            $table->timestamps();

            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('messages')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('author_id')
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
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['post_id', 'parent_id', 'author_id']);
        });

        Schema::dropIfExists('messages');
    }
}
