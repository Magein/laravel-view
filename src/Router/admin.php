<?php

use Illuminate\Support\Facades\Route;
use Magein\Admin\Controllers\User;
use Magein\Admin\Controllers\UserCenter;
use Magein\Admin\Controllers\System;

//用户【登录、登出】
Route::prefix('user')->group(function () {
    Route::post('login', [User::class, 'login']);
    Route::post('lbp', [User::class, 'loginByPhone']);
    Route::post('lbq', [User::class, 'loginByQrcode']);
    Route::post('findpass', [User::class, 'findPass']);
    Route::get('logout', [User::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    /**
     * 个人中心 设置个人信息，修改密码，上传头像等等
     */
    Route::prefix('uc')->group(function () {
        Route::get('getUserInfo', [UserCenter::class, 'getInfo']);
        Route::post('setUserInfo', [UserCenter::class, 'setUserInfo']);
        Route::post('setPassword', [UserCenter::class, 'setPassword']);
    });

    /**
     * 系统设置，如分配权限等
     */
    Route::prefix('system')->group(function () {
        Route::post('getUserSetting', [System::class, 'getUserSetting']);
        Route::post('setUserAuth', [System::class, 'setUserAuth']);
        Route::post('removeUserAuth', [System::class, 'removeUserAuth']);
        Route::post('setUserRole', [System::class, 'setUserRole']);
        Route::post('setRoleAuth', [System::class, 'setRoleAuth']);
    });
});







