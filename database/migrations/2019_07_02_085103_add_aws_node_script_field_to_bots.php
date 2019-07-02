<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAwsNodeScriptFieldToBots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->text('aws_custom_script')->nullable()->after('aws_startup_script');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn(['aws_custom_script']);
        });
    }
}
