<?php

use App\Bot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('platform_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('parameters')->nullable();
            $table->text('path')->nullable();
            $table->text('aws_startup_script')->nullable();
            $table->text('aws_custom_script')->nullable();
            $table->json('aws_custom_package_json')->nullable();

            $table->enum('status', [
                Bot::STATUS_ACTIVE,
                Bot::STATUS_INACTIVE
            ])->default(Bot::STATUS_ACTIVE);

            $table->enum('type', [
                Bot::TYPE_PUBLIC,
                Bot::TYPE_PRIVATE
            ])->default(Bot::TYPE_PUBLIC);

            $table->timestamps();

            $table->foreign('platform_id')
                ->references('id')
                ->on('platforms')
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
        Schema::table('bots', function (Blueprint $table) {
            $table->dropForeign(['platform_id']);
        });

        Schema::dropIfExists('bots');
    }
}
