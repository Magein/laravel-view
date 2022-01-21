<?php

namespace Magein\Admin\View;

use App\Admin\Service\CacheService;
use Illuminate\Support\Facades\Auth;
use magein\tools\common\Variable;

/**
 * 页面的权限控制
 * 1. 用于生成权限
 * 2. 用于页面是否需要验证权限
 */
class PageAuth
{
    /**
     * 组
     * @var string
     */
    public $group = '';

    /**
     * 名称
     * @var string
     */
    public $name = '';

    /**
     * @var array
     */
    public $list = [
        'post' => '新增',
        'put' => '编辑',
        'patch' => '修改',
        'get' => '获取',
        'list' => '列表',
        'trash' => '回收',
        'clean' => '清除',
        'recovery' => '恢复'
    ];

    public $skip = true;

    public function __construct($group = '', string $name = '', $list = [])
    {
        $this->group = $group;
        $this->name = $name;
        if ($list === null) {
            $this->list = [];
        } elseif ($list) {
            $this->list = $list;
        }
    }

    /**
     * @param $name
     * @param $action
     * @return bool
     */
    public function verify($name, $action): bool
    {
        if (empty($this->list)) {
            return true;
        }

        if ($this->skip) {
            return true;
        }

        $pages = CacheService::instance()->userAuthPaths(Auth::id());

        $path = Variable::instance()->pascal($name) . '/' . $action;

        if (in_array($path, $pages)) {
            $this->action();
            return true;
        }

        return false;
    }

    /**
     * 记录行为日志
     */
    public function action()
    {
        $model = new SystemUserAction();
        $model->user_id = Auth::id();
        $model->save();
    }
}
