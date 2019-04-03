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
        Schema::create('bot_tags', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->unsignedInteger('bots_id')->nullable();

            $table->foreign('bots_id')
                ->references('id')->on('bots')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unsignedInteger('tags_id')->nullable();

            $table->foreign('tags_id')
                ->references('id')->on('tags')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bot_tags');
    }
}
