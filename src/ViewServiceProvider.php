<?php

namespace Magein\Admin;

use Illuminate\Support\ServiceProvider;
use Magein\Admin\Commands\MakeApiViewPage;
use Magein\Admin\Commands\MakeApiViewPageAuth;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config.php', 'view');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 注册admin路由
        $path = __DIR__ . '/RouterAdmin.php';
        $this->loadRoutesFrom($path);

        // 注册视图路由
        $path = __DIR__ . '/RouterView.php';
        $this->loadRoutesFrom($path);

        // 加载数据库迁移文件
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        // 加载命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeApiViewPage::class,
                MakeApiViewPageAuth::class
            ]);
        }
    }
}
