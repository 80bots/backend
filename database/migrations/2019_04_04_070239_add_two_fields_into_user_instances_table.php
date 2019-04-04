<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTwoFieldsIntoUserInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_instances', function (Blueprint $table) {
            $table->double('temp_up_time')->default(0)->after('up_time');
            $table->double('cron_up_time')->default(0)->after('temp_up_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_instances', function (Blueprint $table) {
            $table->dropColumn(['temp_up_time', 'cron_up_time']);
        });
    }
}
