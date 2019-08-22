<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_instances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tag_name')->nullable();
            $table->string('tag_user_email')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('bot_id')->nullable();
            $table->unsignedInteger('aws_region_id')->nullable();
            $table->double('used_credit')->default(0);
            $table->double('up_time')->default(0);
            $table->double('temp_up_time')->default(0);
            $table->double('cron_up_time')->default(0);
            $table->string('aws_instance_id')->nullable();
            $table->string('aws_ami_id')->nullable();
            $table->string('aws_ami_name')->nullable();
            $table->string('aws_security_group_id')->nullable();
            $table->string('aws_security_group_name')->nullable();
            $table->string('aws_public_ip')->nullable();
            $table->string('aws_public_dns')->nullable();
            $table->string('aws_pem_file_path')->nullable();
            $table->enum('status', [
                'pending',
                'running',
                'stopped',
                'terminated'
            ])->default('pending');
            $table->boolean('is_in_queue')->default(1);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
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
        Schema::table('user_instances', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'bot_id', 'aws_region_id']);
        });

        Schema::dropIfExists('user_instances');
    }
}
