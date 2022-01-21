<?php

namespace Magein\Admin\Service;

use Illuminate\Contracts\Cache\Repository;
use Magein\Common\BaseService;
use Magein\Common\RedisCache;

class CacheService extends BaseService
{
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

        $pages = RedisCache::get($key);

        if (empty($pages)) {
            $pages = SystemService::instance()->getUserSetting($user_id)['path'] ?? [];
            $pages && $this->drive()->put($key, $pages);
        }

        return $pages;
    }
}
