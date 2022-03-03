<?php

namespace Magein\Admin\View\Page;

use Magein\Admin\Models\SystemPermission;
use Magein\Admin\View\Page;
use magein\tools\common\Variable;

class SystemPermissionPage extends Page
{
    public $model = SystemPermission::class;

    public $auth = '权限';

    public $rules = [
        'group' => 'bail|required|string|max:30',
        'name' => 'bail|required|string|max:30',
        'path' => 'bail|required|string|max:60',
        'description' => 'bail|required|string|max:140',
    ];

    /**
     * @return array
     */
    public $message = [
        'group.required' => '所属分组不能为空',
        'group.string' => '所属分组需要一个字符串',
        'group.max' => '所属分组最大长度为30',
        'name.required' => '权限名称不能为空',
        'name.string' => '权限名称需要一个字符串',
        'name.max' => '权限名称最大长度为30',
        'path.required' => '权限路径不能为空',
        'path.string' => '权限路径需要一个字符串',
        'path.max' => '权限路径最大长度为60',
        'description.required' => '权限描述不能为空',
        'description.string' => '权限描述需要一个字符串',
        'description.max' => '权限描述最大长度为140',
    ];

    public $search = [
        'group',
        ['name', 'like'],
    ];

    public function search(array $params = []): array
    {
        $path = request()->input('path');
        if ($path) {
            $params[] = ['path', '=', Variable::instance()->pascal($path)];
        }
        return parent::search($params);
    }
}

