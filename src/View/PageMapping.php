<?php

namespace Magein\Admin\View;

use Magein\Admin\Models\UserAction;
use Magein\Admin\Models\UserSetting;
use Magein\Admin\View\Page\SystemPermissionPage;
use Magein\Admin\View\Page\UserPage;
use Magein\Admin\View\Page\UserRolePage;
use magein\tools\common\Variable;

class PageMapping
{
    /**
     * 获取page页面
     * @param $name
     * @return Page|null
     */
    public function page($name)
    {
        $name = Variable::instance()->pascal($name);
        $mapping = $this->mapping($name);
        if (is_string($mapping)) {
            return new Page($mapping);
        }
        if ($mapping instanceof Page) {
            return $mapping;
        }
        $path = config('view.page_path');
        $namespace = $path . '\\' . $name . 'Page';

        try {
            if (class_exists($namespace)) {
                return new $namespace();
            }
        } catch (\Exception $exception) {

        }

        return null;
    }

    /**
     * @param $name
     * @return string|null
     */
    protected function mapping($name)
    {
        $data = [
            'User' => new UserPage(),
            'UserRole' => new UserRolePage(),
            'UserAction' => UserAction::class,
            'UserSetting' => UserSetting::class,
            'SystemPermission' => new SystemPermissionPage(),
        ];

        return $data[$name] ?? null;
    }
}
