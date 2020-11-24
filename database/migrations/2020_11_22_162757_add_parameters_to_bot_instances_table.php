<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParametersToBotInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bot_instances', function (Blueprint $table) {
            $table->json('parameters')->nullable();
            $table->string('path', 200)->nullable();
            $table->string('s3_path', 200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bot_instances', function (Blueprint $table) {
            $table->dropColumn('parameters');
            $table->dropColumn('path');
            $table->dropColumn('s3_path');
        });
    }
}
