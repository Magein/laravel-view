<?php

namespace Magein\Admin\Commands;

use Illuminate\Console\Command;
use Magein\Admin\Models\SystemAuth;
use Magein\Admin\Models\SystemUserSetting;
use Magein\Admin\Models\User;
use Magein\Admin\View\PageAuth;
use magein\tools\common\Variable;

class MakeApiViewPageAuth extends Command
{
    /**
     * The name and signature of the console command.
     *                          分组          路径             名称     描述
     * php artisan request:auth system_role  role/post  新增角色  管理员新增角色
     * php artisan request:auth --group=role --p=admin/role/add --n=新增角色 --d=管理员新增角色
     * php artisan request:auth -G role -P admin/role/add -N 新增角色 -D 管理员新增角色
     * php artisan request:auth -G role -P admin/role/add
     * 下面命令将创建Admin\View\Page\SystemRolePage的restful权限 包含：post、put、patch、get、list、trash、clean、recovery
     * php artisan request:auth system_role 角色
     * 下面命令将创建Admin\View\Page\**Page.php的resetful权限，会根据**Page.php类中的auth方法生成
     * php artisan request:auth --all=page
     * php artisan request:auth -A page
     * 下面命将创建Admin\Controller\**.php中方法的权限,文件中需要包含@requestAuth
     * php artisan request:auth --all=controller
     * php artisan request:auth -A controller
     * 初始化的时候，需要创建超级管理员的所有权限
     * php artisan request:auth --all=supper
     * php artisan request:auth -A s
     * @var string
     */
    protected $signature = 'request:auth {info?*}  {--G|group=} {--N|name=} {--P|path=} {--D|description=} {--A|all=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建page controller的接口';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $all = $this->option('all');

        $info = $this->argument('info');
        $group = $this->option('group');
        $path = $this->option('path');
        $name = $this->option('name');
        $description = $this->option('description') ?: '';

        if ($info && is_array($info)) {
            $group = $info[0];
            $path = $info[1];
            $name = $info[2] ?? '';
            $description = $info[3] ?? '';
        }

        if ($group && $path && $name) {
            try {
                $this->insert($path, $group, $name, $description);
            } catch (\Exception $exception) {
                if (preg_match('/1062 Duplicate entry/', $exception->getMessage())) {
                    $this->error('权限路径已经存在:' . $path);
                } else {
                    $this->error($exception->getMessage());
                }
            }
        }

        if ($all) {
            if ($all == 'page' || $all == 'p') {
                $this->page();
            } elseif ($all == 'controller' || $all == 'c') {
                $this->controller();
            } elseif ($all == 'supper' || $all == 's') {
                $this->supper();
            }
        }
    }

    private function page()
    {
        $page_path = config('view.page_path');
        $real_path = preg_replace('/App/', '', $page_path);
        $path = app_path() . $real_path;
        $files = glob($path . '/*');
        if ($files) {
            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $path = preg_replace('/Page/', '', $filename);
                $namespace = $page_path . '\\' . $filename;

                if (class_exists($namespace)) {
                    $pageClass = new $namespace();
                    if (method_exists($pageClass, 'auth')) {
                        /**
                         * @var $auth PageAuth
                         */
                        $auth = $pageClass->auth();
                        $list = $auth->list;
                        $name = $auth->name;
                        $group = $auth->group;
                        if ($list && $group) {
                            foreach ($list as $key => $item) {
                                $this->insert($path . '/' . $key, $group, $name . $item);
                            }
                        }
                    }
                }
            }
        }
    }

    private function controller()
    {
        $files = glob(app_path() . '/Admin/Controllers/*.php');

        if ($files) {
            foreach ($files as $item) {
                $content = file_get_contents($item);
                if (!preg_match('/@requestAuth/', $content)) {
                    continue;
                }
                $group = pathinfo($item, PATHINFO_FILENAME);
                preg_match_all('/\/\*[\s\S]*?\*\/[\s\S]*?public\s*function\s*\w+/', $content, $matches);
                $remarks = $matches[0] ?? [];
                if ($remarks) {
                    foreach ($remarks as $remark) {
                        preg_match('/@aname=(.*)/', $remark, $name);
                        preg_match('/@adesc=(.*)/', $remark, $desc);
                        preg_match('/public\s*function\s*(\w+)/', $remark, $function);
                        $this->insert($function[1] ?? '', $group, $name[1] ?? '', $desc[1] ?? '');
                    }
                }
            }
        }
    }

    private function getPath($path)
    {
        if (!is_string($path) || empty($path)) {
            return '';
        }
        $path = trim($path);
        $path = trim($path, '/');
        $path = preg_replace('/([\W]*)([\w\/]*)/', '$2', $path);
        $path = preg_replace('/([\d]*)([A-Za-z_\/]*)/', '$2', $path);
        return Variable::instance()->camelCase($path);
    }

    /**
     * @param string $path
     * @param string $group
     * @param string $name
     * @param string $description
     */
    private function insert(string $path, string $group, string $name, string $description = '')
    {
        if ($path = $this->getPath($path)) {
            $this->success(SystemAuth::updateOrCreate(
                ['path' => $path],
                [
                    'name' => $name,
                    'group' => $group,
                    'description' => $description
                ]
            ));
        } else {
            $this->error('权限请求路径异常');
        }
    }

    private function success(SystemAuth $auth)
    {
        $this->info($auth->name . ' ' . $auth->path . ' 插入成功');
    }

    public function supper()
    {
        $paths = SystemAuth::pluck('path');
        $user = User::where('id', 1)->first();
        if ($paths && ($user->id ?? 0)) {
            SystemUserSetting::updateOrCreate(['user_id' => $user->id], ['path' => $paths]);
            $this->info('设置超级管理员权限成功');
        } else {
            $this->error('设置失败，请检查user、system_auth表');
        }
    }
}
