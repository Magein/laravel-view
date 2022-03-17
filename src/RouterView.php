<?php

use Illuminate\Support\Facades\Route;
use Magein\Admin\Controllers\System;

/**
 * 快速渲染数据【CURD】需要检查权限
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::any('view/{name}/{action}', [System::class, 'view']);
    Route::post('view/upload', [System::class, 'upload']);
});

Route::get('vue', function () {
    echo '<pre/>';
    echo htmlspecialchars((new \Magein\Admin\View\PageMake())->vue());
});