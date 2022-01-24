<?php

namespace Magein\Admin\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Magein\Admin\Service\SystemService;
use Magein\Admin\View\ViewData;
use Magein\Common\ApiResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Request;
use Magein\Common\Upload\Driver\UploadLocal;

class System extends BaseController
{
    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function view(Request $request)
    {
        $params = $request::route()->parameters;
        $viewData = new ViewData($params['name'], $params['action']);
        return $viewData->response();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return ResponseFactory|Response
     */
    public function upload(\Illuminate\Http\Request $request)
    {
        $upload = new UploadLocal($request->file('file'));
        return ApiResponse::auto($upload->move());
    }

    /**
     * @aname 获取用户设置
     * @adesc 获取用户设置 包含权限、主题、角色、等
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function getUserSetting(Request $request)
    {
        $user_id = $request::input('user_id');

        return ApiResponse::auto(SystemService::instance()->getUserSetting($user_id));
    }

    /**
     * @aname 设置用户权限
     * @adesc 直接重置用户的权限
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function setUserAuth(Request $request)
    {
        $user_id = $request::input('user_id');
        $auth_id = $request::input('auth_id');

        return ApiResponse::auto(SystemService::instance()->setUserAuth($user_id, $auth_id));
    }

    /**
     * @aname 移除用户权限
     * @adesc 移除用户已经拥有的部分权限
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function removeUserAuth(Request $request)
    {
        $user_id = $request::input('user_id');
        $paths = $request::input('paths');

        return ApiResponse::auto(SystemService::instance()->removeUserAuth($user_id, $paths));
    }

    /**
     * @aname 设置用户角色
     * @adesc 重置用户的角色
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function setUserRole(Request $request)
    {
        $user_id = $request::input('user_id');
        $role_id = $request::input('role_id');

        return ApiResponse::auto(SystemService::instance()->setUserRole($user_id, $role_id));
    }

    /**
     * @aname 设置角色权限
     * @adesc 给拥有角色的用户追加权限
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function setRoleAuth(Request $request)
    {
        $role_id = $request::input('role_id');
        $auth_id = $request::input('auth_id');

        return ApiResponse::auto(SystemService::instance()->setRoleAuth($role_id, $auth_id));
    }
}
