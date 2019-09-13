<?php

use App\Notification as SaaSNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->string('title');
            $table->unsignedInteger('to_id');
            $table->unsignedInteger('from_id');
            $table->string('message');
            $table->string('icon')->nullable();
            $table->text('payload');

            $table->enum('push_status', [
                SaaSNotification::STATUS_QUEUED,
                SaaSNotification::STATUS_SENT,
                SaaSNotification::STATUS_NOT_REQUIRED
            ])->default(SaaSNotification::STATUS_QUEUED);

            $table->enum('email_status', [
                SaaSNotification::STATUS_QUEUED,
                SaaSNotification::STATUS_SENT,
                SaaSNotification::STATUS_NOT_REQUIRED
            ])->default(SaaSNotification::STATUS_QUEUED);

            $table->enum('sms_status', [
                SaaSNotification::STATUS_QUEUED,
                SaaSNotification::STATUS_SENT,
                SaaSNotification::STATUS_NOT_REQUIRED
            ])->default(SaaSNotification::STATUS_QUEUED);

            $table->timestamp('instance_stop_time')->nullable();
            $table->timestamps();

            $table->foreign('to_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('from_id')
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
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['to_id', 'from_id']);
        });

        Schema::dropIfExists('notifications');
    }
}
