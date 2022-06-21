<?php

namespace Magein\Admin\Service;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Validator;
use Magein\Admin\Models\User;
use Magein\Admin\View\Page\UserPage;
use Magein\Common\BaseService;
use Illuminate\Support\Facades\Hash;
use Magein\Common\Output;
use Magein\Common\RedisCache;
use Magein\Sms\Facades\Sms;
use Magein\Sms\Lib\SmsCode;

class UserService
{
    use BaseService;

    public static function id()
    {
        return request()->user()->id ?? null;
    }

    /**
     * @return Authenticatable|null
     */
    public function getInfo()
    {
        $user = request()->user();
        if ($user) {
            $user['setting'] = SystemService::instance()->getUserSetting($user->id ?? '');
        }
        return $user;
    }

    public function setUserInfo($data)
    {
        if (!self::id()) {
            return new Output('请先登录', 403);
        }
        $userPage = new UserPage();
        $validate = Validator::make($data, $userPage->rules, $userPage->message);
        if ($validate->fails()) {
            return new Output($validate->errors()->first());
        }

        $user = User::find(self::id());
        $user->fill($data);
        return $user->save();
    }

    public function setPassword($password, $new, $confirm)
    {
        if (empty($password)) {
            return new Output('请输入密码');
        }

        if (empty($new) || empty($confirm)) {
            return new Output('请输入新密码');
        }

        if (!preg_match('/[\w]{6,18}/', $new)) {
            return new Output('密码仅允许数字、字母、下划线且长度为6~18个字符');
        }

        if ($new != $confirm) {
            return new Output('请输入新密码和确认密码不一致');
        }

        $user = User::find(self::id());
        if (!Hash::check($password, $user->password)) {
            return new Output('旧密码不正确');
        }
        $user->password = $new;
        $user->pass_updated_at = now();
        return $user->save();
    }

    private function loginAfter($user)
    {
        if ($user->status == 0) {
            return new Output('用户已经被禁止登录');
        }

        // 设置请求权限
        CacheService::instance()->userAuthPaths($user->id);

        $user->login_at = now();
        $user->login_ip = request()->ip();
        $user->save();

        return Output::success([
            'token' => $user->createToken('user' . $user->id)->plainTextToken,
        ]);
    }

    public function login($email, $password)
    {
        $user = User::_email($email);

        if (empty($user) || !Hash::check($password, $user->password)) {
            return new Output('用户不存在');
        }

        return $this->loginAfter($user);
    }


    public function loginByPhone($phone, $code)
    {
        $user = User::_phone($phone);

        if (empty($user)) {
            return new Output('用户不存在');
        }

        if (Sms::validate($phone, $code, SmsCode::SCENE_LOGIN)->fail()) {
            return new Output('验证码不正确');
        }

        return $this->loginAfter($user);
    }

    public function loginByQrcode($token)
    {
        $user_id = RedisCache::get($token);
        if (empty($user_id)) {
            return new Output('无效的token');
        }
        $user = User::find($user_id);
        if (empty($user)) {
            return new Output('用户不存在');
        }
        return $this->loginAfter($user);
    }
}
