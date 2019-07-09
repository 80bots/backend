<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCronDataTimezoneToInstanceSessionsHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instance_sessions_history', function (Blueprint $table) {
            $table->text('cron_data')->nullable()->after('selected_time');
            $table->text('current_time_zone')->nullable()->after('cron_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instance_sessions_history', function (Blueprint $table) {
            $table->dropColumn(['cron_data','current_time_zone']);
        });
    }
}
