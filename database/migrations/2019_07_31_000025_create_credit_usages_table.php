<?php

use App\CreditUsage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCreditUsagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_usages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('instance_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->integer('credits');
            $table->integer('total');

            $table->enum('action', [
                CreditUsage::ACTION_ADDED,
                CreditUsage::ACTION_USED
            ]);

            $table->string('subject')->nullable();
            $table->timestamps();

            $table->foreign('instance_id')
                ->references('id')
                ->on('bot_instances')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::table('credit_usages', function (Blueprint $table) {
            $table->dropForeign(['instance_id', 'user_id']);
        });

        Schema::dropIfExists('credit_usages');
    }
}
