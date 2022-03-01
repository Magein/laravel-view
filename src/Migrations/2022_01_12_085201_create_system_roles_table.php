<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_roles', function (Blueprint $table) {
            $table->id();
            $table->string('group', 30)->comment('角色组');
            $table->string('name', 30)->comment('角色名称');
            $table->string('description', 140)->default('')->comment('角色描述');
            $table->text('path')->comment('权限路径');
            $table->tinyInteger('sort')->default(99)->comment('排序');
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
        Schema::dropIfExists('system_roles');
    }
}
