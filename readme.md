### 简介

> laravel 前后端分离项目，快速构建内容管理系统后端服务

### 注册服务提供者

> config\app.php中注册服务提供者

```injectablephp
'providers' => [

    //此处省略laravel的其服务提供者.....
    \Magein\Admin\ViewServiceProvider::class
]
```

跨域处理

```php
// 在config/cors.php中添加

[
    'paths' => [
        'api/*', 
        'admin/*', // 增加admin的api请求
        'view/*', // 增加view的api请求
    ],
]

```

