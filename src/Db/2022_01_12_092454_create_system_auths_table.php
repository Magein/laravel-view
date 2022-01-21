<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_auths', function (Blueprint $table) {
            $table->id();
            $table->string('group', 30)->comment('所属分组');
            $table->string('name', 30)->comment('权限名称');
            $table->string('path', 60)->unique()->comment('权限路径');
            $table->string('description', 140)->default('')->comment('权限描述');
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
        Schema::dropIfExists('system_auths');
    }
}
