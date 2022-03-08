<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWeChatAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wechat_auths', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->comment('会员ID')->default(0);
            $table->char('uuid', 32)->unique()->comment('授权标识 接口中不能暴露用户的open_id');
            $table->string('open_id')->unique()->comment('微信授权OPEN ID');
            $table->string('union_id')->comment('微信唯一标识')->default('');
            $table->string('nickname', 64)->comment('名称')->default('');
            $table->tinyInteger('gender')->comment('性别')->default(0);
            $table->string('language')->comment('语言')->default('');
            $table->string('city')->comment('城市')->default('');
            $table->string('province')->comment('省份')->default('');
            $table->string('country')->comment('国家')->default('');
            $table->string('avatar_url')->comment('头像')->default('');
            $table->string('watermark')->comment('路径')->default('');
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `user_wechat_auths` comment '用户微信授权表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_auths');
    }
}
