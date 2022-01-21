<?php

namespace Magein\Admin\Providers;

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\ServiceProvider;
use Magein\Admin\Commands\MakeApiViewPage;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 注册admin路由
        $path = __DIR__ . '/../Router/admin.php';
        $this->loadRoutesFrom($path);

        // 注册视图路由
        $path = __DIR__ . '/../Router/view.php';
        $this->loadRoutesFrom($path);

        // 加载数据库迁移文件
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        // 加载命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeApiViewPage::class,
                MakesHttpRequests::class
            ]);
        }
    }
}