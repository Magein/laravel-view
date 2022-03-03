<?php

namespace Magein\Admin\Service;

use Magein\Admin\Models\SystemPermission;
use Magein\Admin\Models\UserRole;
use Magein\Admin\Models\UserSetting;
use Magein\Common\BaseService;
use Magein\Common\MsgContainer;

class SystemService extends BaseService
{
    /**
     * @param $user_id
     * @param bool|array $rel
     * @return array
     */
    public function getUserSetting($user_id, $rel = false)
    {
        $record = UserSetting::where('user_id', $user_id)->first();

        if (empty($record)) {
            return [];
        }

        if ($rel) {
            if ($record->permission_id) {
                $record->permission = SystemPermission::whereIn('id', $record->permission_id)->get();
            }
            if ($record->role_id) {
                $record->role = UserRole::whereIn('id', $record->role_id)->get();
            }
        }

        return $record;
    }

    private function checkAuthParams(...$params): array
    {
        $data = [];
        foreach ($params as $item) {
            if (empty($item) || !is_array($item)) {
                return [];
            }
            $item = array_unique($item);
            $data[] = array_filter($item);
        }
        return array_filter($data);
    }

    /**
     * @param array $user_id
     * @param array $permission_id
     * @return bool
     */
    public function setUserPermission(array $user_id, array $permission_id)
    {
        $params = $this->checkAuthParams($user_id, $permission_id);
        if (empty($params)) {
            return false;
        }
        list($user_id, $permission_id) = $params;
        foreach ($user_id as $uuid) {
            $userSetting = UserSetting::where('user_id', $uuid)->first();
            $userSetting->permission_id = array_merge($userSetting->permission_id ?: [], $permission_id);
            $userSetting->save();
        }
        return true;
    }

    /**
     * @param int $user_id
     * @param array $paths
     * @return bool
     */
    public function removeUserPermission(int $user_id, array $permission_id)
    {
        $user_permission_id = $this->getUserSetting($user_id)['permission_id'] ?? [];

        if (empty($user_permission_id)) {
            return true;
        }

        $permission_id = array_diff($user_permission_id, $permission_id);

        CacheService::instance()->userAuthPaths($user_id, true);

        return UserSetting::updateOrCreate(['user_id' => $user_id], ['permission_id' => $permission_id]);
    }

    /**
     * 设置用户角色
     * @param int $user_id
     * @param array $role_id
     * @return false
     */
    public function setUserRole(int $user_id, array $role_id = [])
    {
        if (empty($user_id) || empty($role_id)) {
            return false;
        }

        // 获取角色下的权限路径
        $role_paths = UserRole::whereIn('id', $role_id)->pluck('permission_id')->toArray();
        $permission_ids = [];
        if ($role_paths) {
            foreach ($role_paths as $item) {
                $permission_ids = array_merge($permission_ids, $item);
            }
        }

        $setting = UserSetting::where('user_id', intval($user_id))->first();
        if ($setting) {
            $permission_ids = array_merge($setting->permission_id, $permission_ids);
        }

        return UserSetting::updateOrCreate(['user_id' => intval($user_id)], ['role_id' => $role_id, 'permission_id' => $permission_ids]);
    }

    /**
     * 设置角色的权限
     * @param array $role_id
     * @param array $permission_id
     * @return bool
     */
    public function setRolePermission(array $role_id, array $permission_id)
    {
        $params = $this->checkAuthParams($role_id, $permission_id);

        if (empty($params)) {
            return false;
        }

        list($role_id, $permission_id) = $params;
        // 设置用户的权限
        foreach ($role_id as $item) {
            $role = UserRole::find($item);
            if ($role) {
                $role->permission_id = array_merge($permission_id, $role->permission_id);
                $role->save();
            }
        }

        // 给拥有此角色的用户分配权限
        $userSetting = UserSetting::all();
        if ($userSetting->isEmpty()) {
            return true;
        }

        foreach ($userSetting as $user) {
            if (empty($user->role_id ?? [])) {
                continue;
            }
            // 计算交集，有交集这表示用户需要赋予全选
            if (array_intersect($user->role_id, $role_id)) {
                UserSetting::updateOrCreate(
                    [
                        'user_id' => $user->user_id
                    ],
                    [
                        'permission_id' => array_merge($user->permission_id ?? [], $permission_id)
                    ]
                );
            }
        }

        return true;
    }

    public function removeRolePermission(int $role_id, array $permission_id)
    {
        $userRole = UserRole::find($role_id);
        if (empty($userRole) || empty($permission_id)) {
            return MsgContainer::msg('参数错误');
        }
        $userRole->permission_id = array_diff($userRole->permission_id, $permission_id);
        $userRole->save();

        // 拥有此角色的用户删除角色权限
        $userSetting = UserSetting::all();
        if ($userSetting->isEmpty()) {
            return true;
        }

        foreach ($userSetting as $user) {
            if (empty($user->role_id ?? [])) {
                continue;
            }
            if (in_array($role_id, $user->role_id)) {
                UserSetting::updateOrCreate(
                    [
                        'user_id' => $user->user_id
                    ],
                    [
                        'permission_id' => array_diff($user->permission_id, $permission_id)
                    ]
                );
            }
        }

        return true;
    }
}
