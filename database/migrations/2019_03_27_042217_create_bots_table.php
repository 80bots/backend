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
            $table->integerIncrements('id');
            $table->string('bot_name')->nullable();
            $table->string('aws_ami_image_id')->nullable();
            $table->string('aws_ami_name')->nullable();
            $table->string('aws_instance_type')->nullable();
            $table->text('aws_startup_script')->nullable();
            $table->integer('aws_storage_gb')->nullable();
            $table->enum('status',['active','inactive'])->default('active');
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
        Schema::dropIfExists('bots');
    }
}
