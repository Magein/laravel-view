<?php

use Illuminate\Support\Facades\Route;
use Magein\Admin\Controllers\User;
use Magein\Admin\Controllers\UserCenter;
use Magein\Admin\Controllers\System;

//用户【登录、登出】
Route::prefix('admin')->group(function () {
    //用户【登录、登出】
    Route::prefix('user')->group(function () {
        Route::post('login', [User::class, 'login']);
        Route::post('lbp', [User::class, 'loginByPhone']);
        Route::post('lbq', [User::class, 'loginByQrcode']);
        Route::post('findpass', [User::class, 'findPass']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        /**
         * 个人中心 设置个人信息，修改密码，上传头像等等
         */
        Route::prefix('uc')->group(function () {
            // 二维码登录，绑定token和用户信息
            Route::post('setQrcodeToken', [UserCenter::class, 'setQrcodeToken']);
            Route::get('logout', [UserCenter::class, 'logout']);
            Route::get('getUserInfo', [UserCenter::class, 'getInfo']);
            Route::post('setUserInfo', [UserCenter::class, 'setUserInfo']);
            Route::post('setPassword', [UserCenter::class, 'setPassword']);
            Route::get('updatePermission', [UserCenter::class, 'updatePermission']);
        });
        /**
         * 系统设置，如分配权限等
         */
        Route::prefix('system')->group(function () {
            Route::post('getUserSetting', [System::class, 'getUserSetting']);

            Route::post('setUserPermission', [System::class, 'setUserPermission']);
            Route::post('removeUserPermission', [System::class, 'removeUserPermission']);

            Route::post('setRolePermission', [System::class, 'setRolePermission']);
            Route::post('removeRolePermission', [System::class, 'removeRolePermission']);

        });
    });
});
