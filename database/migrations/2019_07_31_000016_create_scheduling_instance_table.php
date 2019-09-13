<?php

use App\SchedulingInstance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulingInstanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduling_instances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('instance_id');

            $table->enum('status', [
                SchedulingInstance::STATUS_ACTIVE,
                SchedulingInstance::STATUS_INACTIVE
            ])->default(SchedulingInstance::STATUS_ACTIVE);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

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
        Schema::table('scheduling_instances', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'instance_id']);
        });

        Schema::dropIfExists('scheduling_instances');
    }
}
