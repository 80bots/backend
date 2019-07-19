<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUserSubscriptionPlansStartAndExpiredSubscriptionDatatype extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_subscription_plans', function (Blueprint $table) {
              $table->timestamp('start_subscription')->change();
              $table->timestamp('expired_subscription')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_subscription_plans', function (Blueprint $table) {
            $table->time('start_subscription')->change();
            $table->time('expired_subscription')->change();
        });
    }
}
