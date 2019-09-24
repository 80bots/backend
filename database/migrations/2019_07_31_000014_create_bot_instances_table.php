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
            $table->unsignedInteger('used_credit')->default(0);
            $table->unsignedInteger('up_time')->default(0);
            $table->unsignedInteger('total_up_time')->default(0);
            $table->unsignedInteger('cron_up_time')->default(0);
            $table->boolean('is_in_queue')->default(1);
            $table->enum('aws_status', [
                'running',
                'pending',
                'stopped',
                'terminated'
            ])->default('pending');
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamp('start_time')->nullable();
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
