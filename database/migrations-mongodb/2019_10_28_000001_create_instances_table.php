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
            $collection->unsignedInteger('aws_region_id')->nullable();
            $collection->unsignedInteger('used_credit')->default(0);
            $collection->unsignedInteger('total_up_time')->default(0);
            $collection->json('params');
            $collection->json('details');
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
