<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_user_settings', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30)->unique()->comment('用户标识');
            $table->text('path')->comment('权限路径');
            $table->string('role_id')->comment('角色')->default('');
            $table->text('theme')->comment('主题设置');
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
        Schema::dropIfExists('system_user_settings');
    }
}
