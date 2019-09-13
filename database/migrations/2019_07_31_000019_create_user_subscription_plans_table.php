<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSubscriptionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_subscription_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('plans_id');
            $table->integer('credit');
            $table->string('slug')->unique();
            $table->string('stripe_plan');
            $table->double('total_credit');
            $table->date('start_subscription');
            $table->date('expired_subscription');
            $table->enum('auto_renewal', ['active', 'inactive']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('plans_id')
                ->references('id')
                ->on('subscription_plans')
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
        Schema::table('user_subscription_plans', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'plans_id']);
        });

        Schema::dropIfExists('user_subscription_plans');
    }
}
