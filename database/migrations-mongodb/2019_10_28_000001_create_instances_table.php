<?php

use App\BotInstance;
use Illuminate\Database\Migrations\Migration;
use Jenssegers\Mongodb\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instances', function (Blueprint $collection) {
            $collection->unsignedBigInteger('instance_id')->index();
            $collection->string('tag_name')->nullable();
            $collection->string('tag_user_email')->nullable();
            $collection->string('bot_path')->nullable();
            $collection->string('bot_name')->nullable();
            $collection->string('aws_region')->nullable();
            $collection->string('aws_instance_type')->nullable();
            $collection->unsignedSmallInteger('aws_storage_gb')->nullable();
            $collection->string('aws_image_id')->nullable();
            $collection->json('params');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instances');
    }
}
