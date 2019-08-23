<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->unsignedTinyInteger('role_id');
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
            $table->double('remaining_credits')->default(0);
            $table->double('temp_remaining_credits')->default(0);
            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
            $table->double('sent_email_status')->default(0);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade')->onUpdate('cascade');
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
            $table->dropForeign(['role_id', 'timezone_id']);
        });

        Schema::dropIfExists('users');
    }
}
