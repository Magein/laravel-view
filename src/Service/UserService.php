<?php

namespace Magein\Admin\Service;

use Illuminate\Contracts\Auth\Authenticatable;
use Magein\Admin\Models\User;
use Magein\Common\BaseService;
use Magein\Common\MsgContainer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    /**
     * @return Authenticatable|null
     */
    public function getInfo()
    {
        $user = Auth::user();
        if ($user) {
            $user['setting'] = SystemService::instance()->getUserSetting($user->id ?? '');
        }
        return $user;
    }

    public function setUserInfo($data)
    {
        if (!Auth::id()) {
            return MsgContainer::msg('请先登录', 403);
        }
        $user = User::find(Auth::id());
        $user->fill($data);
        return $user->save();
    }

    public function setPassword($password, $new, $confirm)
    {
        if (empty($password)) {
            return MsgContainer::msg('请输入密码');
        }

        if (empty($new) || empty($confirm)) {
            return MsgContainer::msg('请输入新密码');
        }

        if (!preg_match('/[\w]{6,18}/', $new)) {
            return MsgContainer::msg('密码仅允许数字、字母、下划线且长度为6~18个字符');
        }

        if ($new != $confirm) {
            return MsgContainer::msg('请输入新密码和确认密码不一致');
        }

        $user = User::find(Auth::id());
        if (!Hash::check($password, $user->password)) {
            return MsgContainer::msg('旧密码不正确');
        }
        $user->password = $new;
        $user->pass_updated_at = now();
        return $user->save();
    }

    /**
     * @param $email
     * @param $password
     * @return MsgContainer|null[]
     */
    public function login($email, $password)
    {
        $user = User::where(['email' => $email])->first();

        if (empty($user) || !Hash::check($password, $user->password)) {
            return $this->error('用户不存在');
        }

        // 设置请求权限
        CacheService::instance()->userAuthPaths($user->id);

        $user->login_at = now();
        $user->login_ip = request()->ip();
        $user->save();

        return [
            'token' => $user->createToken('user' . $user->id)->plainTextToken,
        ];
    }
}
