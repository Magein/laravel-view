<?php

namespace Magein\Admin\Controllers;

use Magein\Admin\Service\UserService;
use Magein\Common\ApiResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Request;


class User extends BaseController
{
    public function login(Request $request)
    {
        $email = $request::input('username');
        $password = $request::input('password');

        return ApiResponse::auto(UserService::instance()->login($email, $password));
    }

    public function loginByPhone(Request $request)
    {
        $phone = $request::input('phone');
        $code = $request::input('code');

        return ApiResponse::auto(UserService::instance()->loginByPhone($phone, $code));
    }

    public function loginByQrcode(Request $request)
    {
        $token = $request::input('token');

        return ApiResponse::auto(UserService::instance()->loginByQrcode($token));
    }

    public function findPass()
    {

    }
}
