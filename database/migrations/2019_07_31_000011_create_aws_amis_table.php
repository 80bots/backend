<?php

use App\AwsAmi;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAwsAmisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aws_amis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('aws_region_id')->nullable();
            $table->string('name');
            $table->text('description');
            $table->string('image_id');
            $table->string('architecture');
            $table->string('source');
            $table->string('image_type');
            $table->string('owner');

            $table->enum('visibility', [
                AwsAmi::VISIBILITY_PUBLIC,
                AwsAmi::VISIBILITY_PRIVATE
            ])->default(AwsAmi::VISIBILITY_PRIVATE);

            $table->string('status');
            $table->boolean('ena_support')->default(true);
            $table->string('hypervisor');
            $table->string('root_device_name');
            $table->string('root_device_type');
            $table->string('sriov_net_support');
            $table->string('virtualization_type');
            $table->timestamp('creation_date');
            $table->timestamps();

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
        Schema::table('aws_amis', function (Blueprint $table) {
            $table->dropForeign(['aws_region_id']);
        });

        Schema::dropIfExists('aws_amis');
    }
}
