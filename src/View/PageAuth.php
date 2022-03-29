<?php

namespace Magein\Admin\View;

use Magein\Admin\Models\UserAction;
use Magein\Admin\Service\CacheService;
use Magein\Admin\Service\UserService;
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
        'create' => '新增',
        'edit' => '编辑',
        'update' => '更新',
        'get' => '获取',
        'list' => '列表',
        'delete' => '删除',
        'restore' => '恢复',
        'clean' => '清除',
        'download' => '下载',
    ];

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

        $page_auth = config('view.page_auth');
        if ($page_auth === false || $action === 'tree' || $action == 'columns') {
            $this->action();
            return true;
        }

        $pages = CacheService::instance()->userAuthPaths(UserService::id());

        $path = Variable::instance()->pascal($name) . '/' . Variable::instance()->camelCase($action);

        if (!in_array($path, $pages)) {
            return false;
        }

        $this->action();
        return true;
    }

    /**
     * 记录行为日志
     */
    public function action()
    {
        $page_action = config('view.page_action');
        if ($page_action === true) {
            $model = new UserAction();
            $model->user_id = UserService::id();
            $model->save();
        }
    }
}
