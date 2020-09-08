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
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('parameters')->nullable();
            $table->text('path')->nullable();
            $table->string('s3_path')->nullable();

            $table->enum('status', [
                Bot::STATUS_ACTIVE,
                Bot::STATUS_INACTIVE
            ])->default(Bot::STATUS_ACTIVE);

            $table->enum('type', [
                Bot::TYPE_PUBLIC,
                Bot::TYPE_PRIVATE
            ])->default(Bot::TYPE_PUBLIC);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bots');
    }
}
