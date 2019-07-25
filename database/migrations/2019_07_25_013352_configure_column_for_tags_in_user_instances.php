<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConfigureColumnForTagsInUserInstances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_instances', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('tag_name')->nullable()->after('id');
            $table->string('tag_user_email')->nullable()->after('tag_name');
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
            $table->string('name')->nullable()->after('id');
            $table->dropColumn('tag_name');
            $table->dropColumn('tag_user_email');
        });
    }
}
