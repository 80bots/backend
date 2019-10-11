<?php

use App\S3Object;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateS3ObjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('s3_objects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('instance_id');
            $table->date('folder')->nullable();
            $table->text('link');
            $table->timestamp('expires');
            $table->enum('type', [
                S3Object::TYPE_SCREENSHOTS,
                S3Object::TYPE_IMAGES,
                S3Object::TYPE_LOGS,
                S3Object::TYPE_JSON
            ]);

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
        Schema::table('s3_objects', function (Blueprint $table) {
            $table->dropForeign(['instance_id']);
        });

        Schema::dropIfExists('s3_objects');
    }
}
