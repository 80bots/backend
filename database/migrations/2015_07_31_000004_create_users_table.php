<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('timezone_id')->nullable();
            $table->unsignedInteger('region_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('verification_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('password_reset_token')->nullable();
            $table->string('auth_token')->nullable();
            $table->rememberToken();

            $table->enum('status', [
                User::STATUS_PENDING,
                User::STATUS_ACTIVE,
                User::STATUS_INACTIVE
            ])->default(User::STATUS_PENDING);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('timezone_id')
                ->references('id')->on('timezones')
                ->onDelete('no action')->onUpdate('no action');

            $table->foreign('region_id')
                ->references('id')->on('aws_regions')
                ->onDelete('no action')->onUpdate('no action');
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
            $table->dropForeign(['timezone_id', 'region_id']);
        });

        Schema::dropIfExists('users');
    }
}
