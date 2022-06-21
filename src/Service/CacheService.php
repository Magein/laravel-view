<?php

namespace Magein\Admin\Service;

use Illuminate\Contracts\Cache\Repository;
use Magein\Admin\Models\SystemPermission;
use Magein\Common\BaseService;
use Magein\Common\Output;
use Magein\Common\RedisCache;

class CacheService
{

    use BaseService;

    /**
     * @return Repository
     */
    protected function drive()
    {
        return RedisCache::app();
    }

    public function userAuthPaths($user_id, $clear = false)
    {
        $key = 'system_user_auths_' . $user_id;
        if ($clear) {
            $this->drive()->put($key, null);
            return [];
        }
        $paths = RedisCache::get($key);
        if (empty($paths)) {
            $permission_ids = SystemService::instance()->getUserSetting($user_id)['permission_id'] ?? [];
            $paths = SystemPermission::whereIn('id', $permission_ids)->pluck('path')->toArray();
            $paths && $this->drive()->put($key, $paths);
        }
        return $paths;
    }

    public function setQrcodeToken($user_id, $token)
    {
        if (empty($token) || empty($user_id)) {
            return new Output('参数错误');
        }

        RedisCache::put($token, $user_id, 300);

        return true;
    }
}
