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

