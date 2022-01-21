<?php

namespace Magein\Admin\Service;

use Magein\Admin\Models\SystemAuth;
use Magein\Admin\Models\SystemUserSetting;
use Magein\Common\BaseService;

class SystemService extends BaseService
{
    /**
     * @param $user_id
     * @return array
     */
    public function getUserSetting($user_id)
    {
        $record = SystemUserSetting::where('user_id', $user_id)->first();

        if (empty($record)) {
            return [];
        }

        if ($record->path) {
            $record->auth = SystemAuth::whereIn('path', $record->path)->get();
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
     * @param int $user_id
     * @param array $auth_id
     * @return bool
     */
    public function setUserAuth(int $user_id, array $auth_id)
    {
        $params = $this->checkAuthParams($user_id, $auth_id);
        if (empty($params)) {
            return false;
        }
        list($user_id, $auth_id) = $params;
        $paths = SystemAuth::whereIn('id', $auth_id)->pluck('path');
        if (empty($paths)) {
            return false;
        }
        foreach ($user_id as $uuid) {
            SystemUserSetting::updateOrCreate(['user_id' => intval($uuid)], ['path' => $paths]);
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

        return SystemUserSetting::updateOrCreate(['user_id' => $user_id], ['path' => $paths]);
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

        return SystemUserSetting::updateOrCreate(['user_id' => intval($user_id)], ['role_id' => $role_id]);
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

        $paths = SystemAuth::whereIn('id', $auth_id)->pluck('path');
        if (empty($paths)) {
            return false;
        }

        $paths = $paths->toArray();
        $users = SystemUserSetting::all();
        if ($users->isEmpty()) {
            return true;
        }

        foreach ($users as $user) {
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

            SystemUserSetting::updateOrCreate(['user_id' => $user->user_id], ['path' => $user_paths]);
        }

        return true;
    }
}
