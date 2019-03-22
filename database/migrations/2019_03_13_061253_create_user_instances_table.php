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
            $table->string('name')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->double('used_credit')->default(0);
            $table->double('up_time')->default(0);
            $table->string('aws_instance_id')->nullable();
            $table->string('aws_ami_id')->nullable();
            $table->string('aws_ami_name')->nullable();
            $table->string('aws_security_group_id')->nullable();
            $table->string('aws_security_group_name')->nullable();
            $table->string('aws_public_ip')->nullable();
            $table->string('aws_public_dns')->nullable();
            $table->string('aws_pem_file_path')->nullable();
            $table->enum('status', ['start', 'running', 'stop', 'terminated'])->default('running');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_instances');
    }
}
