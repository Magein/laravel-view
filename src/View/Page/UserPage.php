<?php

namespace Magein\Admin\View\Page;

use Magein\Admin\Models\User;
use Magein\Admin\View\Page;

class UserPage extends Page
{
    public $model = User::class;

    public $search = [
        'name',
        'phone',
        'email',
        ['created_at', 'date']
    ];

    public $rules = [
        'email' => 'bail|required|string|max:191',
        'name' => 'bail|required|string|max:6',
        'nickname' => 'bail|required|string|max:30',
        'phone' => 'bail|required|string|size:11',
    ];

    public $message = [
        'email.required' => '邮箱地址不能为空',
        'email.string' => '邮箱地址需要一个字符串',
        'email.max' => '邮箱地址最大长度为191',
        'password.required' => '密码不能为空',
        'password.string' => '密码需要一个字符串',
        'password.max' => '密码最大长度为191',
        'name.required' => '真实姓名不能为空',
        'name.string' => '真实姓名需要一个字符串',
        'name.max' => '真实姓名最大长度为6',
        'nickname.required' => '昵称不能为空',
        'nickname.string' => '昵称需要一个字符串',
        'nickname.max' => '昵称最大长度为30',
        'phone.required' => '手机号码不能为空',
        'phone.string' => '手机号码需要一个字符串',
        'phone.size' => '手机号码限定长度为11个字符',
        'signature.required' => '签名不能为空',
        'signature.string' => '签名需要一个字符串',
        'signature.max' => '签名最大长度为191',
        'avatar.required' => '头像不能为空',
        'avatar.string' => '头像需要一个字符串',
        'avatar.max' => '头像最大长度为191',
    ];

}