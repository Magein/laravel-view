<?php

namespace Magein\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Magein\Admin\Models\SystemPermission;
use Magein\Admin\Models\UserRole;
use Magein\Admin\Models\UserSetting;
use Magein\Admin\Models\User;
use Magein\Admin\View\PageAuth;
use magein\tools\common\Variable;

class MakeApiViewPageAuth extends Command
{
    /**
     * The name and signature of the console command.
     * 创建权限                      分组          路径        名称      描述
     * php artisan view:auth --add="system_role  role/post  新增角色  管理员新增角色"
     *
     * 下面命令将创建 system_role 的资源权限
     * php artisan view:auth --res="system(组名称)  system_role（对应UserRolePage或者UserRoleModel） 名称（如：角色，用户，文章）"
     *
     * 检索 View/Page/*.php 和 配置文件中的page路径并进行路径创建
     * php artisan view:auth --page
     *
     * 下面命将创建Admin\Controller\**.php中方法的权限,文件中需要包含@requestAuth
     * php artisan view:auth --controller
     *
     * 创建用户、用户组
     * php artisan view:auth --user
     *
     * 初始化的时候，需要创建超级管理员的所有权限
     * php artisan view:auth --supper
     *
     * @var string
     */
    protected $signature = 'view:auth {--A|add=} {--res=} {--controller} {--page} {--user} {--supper}  ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建用户、权限命令';

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
        $add = $this->option('add');
        $res = $this->option('res');
        $page = $this->option('page');
        $controller = $this->option('controller');
        $user = $this->option('user');
        $supper = $this->option('supper');


        if ($add) {
            $this->add($add);
        }

        if ($res) {
            $this->resource($res);
        }

        if ($page) {
            $this->page();
        }

        if ($controller) {
            $this->controller();
        }

        if ($user) {
            $this->createUser();
        }

        if ($supper) {
            $this->createPermission();
        }
    }

    private function add($params)
    {
        $params = $this->getParams($params);
        if (empty($params)) {
            $this->error('新增权限路径失败:参数不正确,应该输入 group path name desc四个参数，如--add="system role/post 角色新增 用户区分系统用户角色 "');
        }

        try {
            $this->insert($params['path'], $params['group'], $params['name'], $params['desc']);
        } catch (\Exception $exception) {
            if (preg_match('/1062 Duplicate entry/', $exception->getMessage())) {
                $this->error('权限路径已经存在:' . $params['path']);
            } else {
                $this->error($exception->getMessage());
            }
        }
    }

    private function resource($params)
    {
        $params = $this->getParams($params);
        $resource = $params['path'];
        if (!preg_match('/^[a-zA-Z_]+$/', $resource)) {
            $this->error('资源权限名称错误(可行的值如role、user)，输入的值：' . $resource);
        }
        // 系统路径
        $pageAuth = new PageAuth();
        $lists = $pageAuth->list;
        if ($lists) {
            foreach ($lists as $key => $item) {
                $this->insert($params['path'] . '/' . $key, $params['group'], $this->concatName($params['name'], $item));
            }
        }
    }

    private function page()
    {
        $page_path = config('view.page_path');
        $real_path = preg_replace(['/App/', '/\\\\/'], ['', '/'], $page_path);
        $path = app_path() . $real_path;
        $files = glob($path . '/*');
        if ($files) {
            // 系统路径
            $pageAuth = new PageAuth();
            $lists = $pageAuth->list;
            $system = [
                'User' => '用户',
                'UserRole' => '角色',
                'UserAction' => '行为日志',
                'UserSetting' => '用户设置',
                'SystemPermission' => '权限',
            ];
            if ($lists) {
                foreach ($system as $path => $name) {
                    foreach ($lists as $key => $item) {
                        $this->insert($path . '/' . $key, 'System', $this->concatName($name, $item));
                    }
                }
            }
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
                                $this->insert($path . '/' . $key, $group, $this->concatName($name, $item));
                            }
                        }
                    }
                }
            }
        } else {
            $this->error($path . '路径下没有文件');
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

    private function getParams($params)
    {
        $params = explode(' ', $params);
        $params = array_filter($params);

        if (empty($params)) {
            return [];
        }

        if (!($params[0] ?? '') || !($params[1] ?? '') || !($params[2] ?? '')) {
            return [];
        }

        return [
            'group' => $params[0],
            'path' => $params[1],
            'name' => $params[2],
            'desc' => $params[3] ?? '',
        ];
    }

    private function concatName($first, $second)
    {
        return trim($second . $first, '');
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
            $this->success(SystemPermission::updateOrCreate(
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

    private function success(SystemPermission $auth)
    {
        $this->info($auth->name . ' ' . $auth->path . ' 插入成功');
    }

    public function createPermission()
    {
        $init = function ($permission_ids, $id, $role_name) {
            if (!$permission_ids) {
                $this->error('请先生成权限路径:php artisan --page');
                exit();
            }
            if (empty($id)) {
                $this->error('请先生成用户:php artisan --user');
                exit();
            }
            UserSetting::updateOrCreate(['user_id' => $id], ['permission_id' => $permission_ids]);
            $this->info('创建' . $role_name . '用户权限成功');
        };

        $permission_ids = SystemPermission::pluck('id')->toArray();
        $user = User::where('id', 1)->first();
        $init($permission_ids, $user->id ?? 0, '超级管理员');
        
        $permission_ids = SystemPermission::where('group', '<>', 'system')->pluck('id')->toArray();
        $user = User::where('id', 2)->first();
        $init($permission_ids, $user->id ?? 0, '普通管理员');
    }

    private function createUser()
    {
        // 创建三个角色
        $roles = [
            ['admin', '超级管理员', '超级管理员拥有最高权限'],
            ['admin', '普通管理员', '除系统管理权限外的其他所有权限'],
            ['user', '普通用户', '普通用户'],
        ];

        foreach ($roles as $item) {
            $group = $item[0];
            $name = $item[1];
            $description = $item[2] ?? '';
            UserRole::updateOrCreate(['name' => $name], [
                'group' => $group,
                'description' => $description,
                'sort' => 99,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
            $this->info('创建组：' . $name . ' 完成');
        }

        // 创建三个用户
        $emails = [
            'supper@hz-bc.cn' => ['超级管理员', '超管'],
            'normal@hz-bc.cn' => ['普通管理员', '普管'],
            'user@hz-bc.cn' => ['普通用户', 'magein'],
        ];
        foreach ($emails as $email => $item) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $item[0] ?? '',
                'nickname' => $item[1] ?? '',
                'phone' => '139' . rand(1000, 9999) . rand(1000, 9999),
                'email_verified_at' => now(),
                'password' => "123456",
                'pass_updated_at' => Date::now(),
                'remember_token' => Str::random(10),
            ]);
            $this->info('创建用户：' . $email . ' 完成');
            if ($user->id ?? '') {
                UserSetting::updateOrCreate(['user_id' => $user->id], [
                    'role_id' => [$user->id],
                ]);
            }
        }
    }
}
