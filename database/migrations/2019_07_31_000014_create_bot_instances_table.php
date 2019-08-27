<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_instances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('bot_id')->nullable();
            $table->unsignedInteger('aws_region_id')->nullable();
            $table->double('used_credit')->default(0);
            $table->double('up_time')->default(0);
            $table->double('temp_up_time')->default(0);
            $table->double('cron_up_time')->default(0);
            $table->boolean('is_in_queue')->default(1);
            $table->enum('aws_status', [
                'pending',
                'running',
                'stopped',
                'terminated'
            ])->default('pending');
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('bot_id')
                ->references('id')
                ->on('bots')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('aws_region_id')
                ->references('id')
                ->on('aws_regions')
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
        Schema::table('bot_instances', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'bot_id', 'aws_region_id']);
        });

        Schema::dropIfExists('bot_instances');
    }
}