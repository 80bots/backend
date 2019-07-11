<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


class AddDefaultValueToSentEmailStatusInToTblUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
           DB::statement('ALTER TABLE `users` CHANGE `sent_email_status` `sent_email_status` DOUBLE NOT NULL DEFAULT "0"');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            DB::statement('ALTER TABLE `users` CHANGE `sent_email_status` `sent_email_status` DOUBLE NOT NULL DEFAULT "NULL"');
        });
    }
}
