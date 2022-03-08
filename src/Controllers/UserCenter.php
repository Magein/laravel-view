<?php

namespace Magein\Admin\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Magein\Admin\Service\CacheService;
use Magein\Admin\Service\UserService;
use Magein\Common\ApiResponse;
use Illuminate\Support\Facades\Request;

/**
 * @requestAuth
 */
class UserCenter
{
    public function logout(Request $request)
    {
        if ($request::user()) {
            CacheService::instance()->userAuthPaths($request::user()->id, true);
            $request::user()->currentAccessToken()->delete();
        }

        return ApiResponse::success('success');
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function setQrcodeToken(Request $request)
    {
        return ApiResponse::success(CacheService::instance()->setQrcodeToken($request::user()->id, $request::input('token')));
    }

    /**
     * @aname=获取个人信息
     * @adesc=用户登录后获取的个人信息
     */
    public function getInfo()
    {
        return ApiResponse::success(UserService::instance()->getInfo());
    }

    /**
     * @aname=设置个人信息
     * @adesc=个人中心设置的用户信息
     */
    public function setUserInfo(Request $request)
    {
        return ApiResponse::auto(UserService::instance()->setUserInfo($request::all()));
    }

    public function setPassword(Request $request)
    {
        $password = $request::input('password');
        $new = $request::input('new');
        $confirm = $request::input('confirm');

        return ApiResponse::auto(UserService::instance()->setPassword($password, $new, $confirm));
    }

    public function updatePermission(Request $request)
    {
        CacheService::instance()->userAuthPaths(Auth::id(), true);

        CacheService::instance()->userAuthPaths(Auth::id());

        return ApiResponse::success([Auth::id()]);
    }
}
