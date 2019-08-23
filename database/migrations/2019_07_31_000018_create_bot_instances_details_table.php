<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotInstancesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_instances_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('instance_id');
            $table->string('tag_name')->nullable();
            $table->string('tag_user_email')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->double('total_time')->default(0);

            $table->string('aws_instance_id')->nullable();
            $table->string('aws_instance_type')->nullable();
            $table->integer('aws_storage_gb')->nullable();
            $table->string('aws_image_id')->nullable();
            $table->string('aws_security_group_id')->nullable();
            $table->string('aws_security_group_name')->nullable();
            $table->string('aws_public_ip')->nullable();
            $table->string('aws_public_dns')->nullable();
            $table->string('aws_pem_file_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instance_id')
                ->references('id')
                ->on('bot_instances')
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
        Schema::table('bot_instances_details', function (Blueprint $table) {
            $table->dropForeign(['instance_id']);
        });

        Schema::dropIfExists('bot_instances_details');
    }
}
