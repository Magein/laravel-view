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

        return ApiResponse::auto(SystemService::instance()->getUserSetting($user_id, true));
    }

    /**
     * @aname 设置用户权限
     * @adesc 直接重置用户的权限
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function setUserPermission(Request $request)
    {
        $user_id = $request::input('user_id');
        $permission_id = $request::input('permission_id');

        return ApiResponse::auto(SystemService::instance()->setUserPermission($user_id, $permission_id));
    }

    /**
     * @aname 移除用户权限
     * @adesc 移除用户已经拥有的部分权限
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function removeUserPermission(Request $request)
    {
        $user_id = $request::input('user_id');
        $permission_id = $request::input('permission_id');

        return ApiResponse::auto(SystemService::instance()->removeUserPermission($user_id, $permission_id));
    }

    /**
     * @aname 设置角色权限
     * @adesc 给拥有角色的用户追加权限
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function setRolePermission(Request $request)
    {
        $role_id = $request::input('role_id');
        $permission_id = $request::input('permission_id');

        return ApiResponse::auto(SystemService::instance()->setRolePermission($role_id, $permission_id));
    }

    public function removeRolePermission(Request $request)
    {
        $role_id = $request::input('role_id');
        $permission_id = $request::input('permission_id');

        return ApiResponse::auto(SystemService::instance()->removeRolePermission($role_id, $permission_id));
    }
}
