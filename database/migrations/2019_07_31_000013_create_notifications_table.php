<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use mysql_xdevapi\Collection;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->enum('event', collect(config('events'))->keys()->all());
            $table->enum('action', collect(config('actions'))->keys()->all());

            $table->string('subject');
            $table->text('message');

            $table->text('payload');
            $table->string('icon')->nullable();

            $table->enum('type', ['email', 'push', 'sms'])->default('email');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->enum('delivery', ['queued', 'sent', 'error'])->default('queued');

            $table->timestamp('instance_stop_time')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
