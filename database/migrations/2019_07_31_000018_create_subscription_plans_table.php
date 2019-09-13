<?php

use App\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name');
            $table->integer('price');
            $table->integer('credit');
            $table->string('slug')->unique();
            $table->string('stripe_plan');

            $table->enum('status', [
                SubscriptionPlan::STATUS_ACTIVE,
                SubscriptionPlan::STATUS_INACTIVE
            ])->default(SubscriptionPlan::STATUS_ACTIVE);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_plans');
    }
}
