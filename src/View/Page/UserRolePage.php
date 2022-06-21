<?php

namespace Magein\Admin\View\Page;

use Magein\Admin\Models\SystemPermission;
use Magein\Admin\Models\UserRole;
use Magein\Admin\Service\SystemService;
use Magein\Admin\View\Page;

class UserRolePage extends Page
{
    public $model = UserRole::class;

    public $auth = '用户角色';

    /**
     * @return array
     */
    public $rules = [
        'group' => 'bail|required|string|max:30',
        'name' => 'bail|required|string|max:30',
        'description' => 'bail|required|string|max:140',
        'sort' => 'bail|required|integer|max:99|min:1',
    ];

    /**
     * @return array
     */
    public $message = [
        'group.required' => '角色名称不能为空',
        'group.string' => '角色名称需要一个字符串',
        'group.max' => '角色名称最大长度为30',
        'name.required' => '角色名称不能为空',
        'name.string' => '角色名称需要一个字符串',
        'name.max' => '角色名称最大长度为30',
        'description.required' => '角色描述不能为空',
        'description.string' => '角色描述需要一个字符串',
        'description.max' => '角色描述最大长度为140',
        'sort.required' => '排序不能为空',
        'sort.integer' => '排序需要一个整数',
        'sort.max' => '排序最大值为99',
        'sort.min' => '排序最小值为1',
    ];

    public $search = [
        'group',
        'name'
    ];

    public $columns = 'name';

    public function complete($output, $action)
    {
        if ($output) {
            if ($action == 'get' && $output->permission_id) {
                $output->permission = SystemPermission::whereIn('id', $output->permission_id)->get();
            }
        }

        return parent::complete($output, $action);
    }
}

