<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdIntoSchedulingInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduling_instances', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unsignedInteger('user_instances_id');
            $table->foreign('user_instances_id')
                ->references('id')->on('user_instances')
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
        Schema::table('scheduling_instances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['user_instances_id']);
            $table->dropColumn(['user_id', 'user_instances_id']);
        });
    }
}
