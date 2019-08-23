<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('type', ['public', 'private'])->default('public');
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
