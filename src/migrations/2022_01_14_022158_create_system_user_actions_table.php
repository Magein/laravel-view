<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemUserActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_user_actions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30)->comment('用户标识');
            $table->string('path', 60)->comment('请求路径');
            $table->string('method', 60)->comment('请求方法');
            $table->text('params')->comment('请求参数');
            $table->string('user_agent', 800)->comment('请求标识');
            $table->string('ip', 60)->comment('ip地址');
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
        Schema::dropIfExists('system_user_actions');
    }
}
