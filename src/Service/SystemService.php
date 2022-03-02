<?php

namespace Magein\Admin\Service;

use Magein\Admin\Models\SystemPermission;
use Magein\Admin\Models\UserRole;
use Magein\Admin\Models\UserSetting;
use Magein\Common\BaseService;

class SystemService extends BaseService
{
    /**
     * @param $user_id
     * @return array
     */
    public function getUserSetting($user_id)
    {
        $record = UserSetting::where('user_id', $user_id)->first();

        if (empty($record)) {
            return [];
        }

        if ($record->path) {
            $record->auth = SystemPermission::whereIn('path', $record->path)->get();
        } else {
            $record->auth = [];
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
     * @param array $auth_id
     * @return bool
     */
    public function setUserAuth(array $user_id, array $auth_id)
    {
        $params = $this->checkAuthParams($user_id, $auth_id);
        if (empty($params)) {
            return false;
        }
        list($user_id, $auth_id) = $params;
        $paths = SystemPermission::whereIn('id', $auth_id)->pluck('path')->toArray();
        if (empty($paths)) {
            return false;
        }
        foreach ($user_id as $uuid) {
            $userSetting = UserSetting::where('user_id', $uuid)->first();
            $path = $userSetting->path ?: [];
            $userSetting->path = array_merge($path, $paths);
            $userSetting->save();
        }
        return true;
    }

    /**
     * @param int $user_id
     * @param array $paths
     * @return bool
     */
    public function removeUserAuth(int $user_id, array $paths)
    {
        $user_paths = $this->getUserSetting($user_id)['path'] ?? [];

        if (empty($user_paths)) {
            return true;
        }

        $paths = array_diff($user_paths, $paths);

        CacheService::instance()->userAuthPaths($user_id, true);

        return UserSetting::updateOrCreate(['user_id' => $user_id], ['path' => $paths]);
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
        $role_paths = UserRole::whereIn('id', $role_id)->pluck('path')->toArray();
        $paths = [];
        if ($role_paths) {
            foreach ($role_paths as $item) {
                $paths = array_merge($paths, $item);
            }
        }

        $setting = UserSetting::where('user_id', intval($user_id))->first();

        if ($setting) {
            $paths = array_merge($setting->path, $paths);
        }

        return UserSetting::updateOrCreate(['user_id' => intval($user_id)], ['role_id' => $role_id, 'path' => $paths]);
    }

    /**
     * 设置角色的权限
     * @param array $role_id
     * @param array $auth_id
     * @return bool
     */
    public function setRoleAuth(array $role_id, array $auth_id)
    {
        $params = $this->checkAuthParams($role_id, $auth_id);

        if (empty($params)) {
            return false;
        }

        list($role_id, $auth_id) = $params;

        $paths = SystemPermission::whereIn('id', $auth_id)->pluck('path');
        if (empty($paths)) {
            return false;
        }

        $paths = $paths->toArray();
        foreach ($role_id as $item) {
            $role = UserRole::find($item);
            if ($role) {
                $role_path = array_merge($role->path, $paths);
                $role->path = $role_path;
                $role->save();
            }
        }

        $userSetting = UserSetting::all();
        if ($userSetting->isEmpty()) {
            return true;
        }

        foreach ($userSetting as $user) {
            $user_role_id = $user->role_id ?? [];
            if (empty($user_role_id)) {
                continue;
            }

            // 计算交集，有交集这表示用户需要赋予全选
            $user_paths = $user->path ?? [];
            if (array_intersect($user_role_id, $role_id)) {
                $user_paths = array_merge($user_paths, $paths);
            }
            $user_paths = array_unique($user_paths);
            UserSetting::updateOrCreate(['user_id' => $user->user_id], ['path' => $user_paths]);
        }

        return true;
    }
}
