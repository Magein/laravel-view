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

    public function loginByPhone()
    {

    }


    public function loginByQrcode()
    {

    }

    public function findPass()
    {

    }

    public function logout(Request $request)
    {
        if ($request::user()) {
            $request::user()->currentAccessToken()->delete();
        }

        return ApiResponse::success('success');
    }
}
