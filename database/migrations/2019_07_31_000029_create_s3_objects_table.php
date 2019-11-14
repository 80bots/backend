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
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name')->index();
            $table->string('path')->index();
            $table->text('link')->nullable();
            $table->timestamp('expires')->nullable();
            $table->enum('entity', [
                S3Object::ENTITY_FOLDER,
                S3Object::ENTITY_FILE
            ]);
            $table->enum('type', [
                S3Object::TYPE_ENTITY,
                S3Object::TYPE_SCREENSHOTS,
                S3Object::TYPE_IMAGES,
                S3Object::TYPE_LOGS,
                S3Object::TYPE_JSON
            ])->default(S3Object::TYPE_ENTITY);
            $table->timestamps();

            $table->index('created_at');

            $table->foreign('instance_id')
                ->references('id')
                ->on('bot_instances')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('s3_objects')
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
            $table->dropForeign(['instance_id', 'parent_id']);
        });

        Schema::dropIfExists('s3_objects');
    }
}
