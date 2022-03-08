<?php

namespace Magein\Admin\Models;

use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Magein\Common\AssetPath;
use Magein\Common\BaseModel;

use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * @property integer $id
 * @property string $phone
 * @property string $email
 * @property string $password
 * @property string $name
 * @property string $nickname
 * @property string $avatar
 * @property string $sex
 * @property string $status
 * @property string $email_verified_at
 * @property string $remember_token
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @method static _email($email)
 * @method static _phone($phone)
 */
class User extends BaseModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use HasApiTokens;
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use MustVerifyEmail;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nickname',
        'name',
        'phone',
        'email',
        'avatar',
        'sex',
        'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'sex_text',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'int',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->password)) {
                $model->password = '';
            }
            if (empty($model->avatar)) {
                $model->avatar = '';
            }
            if (empty($model->sex)) {
                $model->sex = 0;
            }
            if (empty($model->status)) {
                $model->sex = 0;
            }
        });
    }

    /**
     * @param $value
     * @return void
     */
    public function setNickNameAttribute($value)
    {
        if ($value) {
            $value = mb_substr($value, 0, 12);
        }

        $this->attributes['nickname'] = $value ?: '';
    }

    public function setSexAttribute($value)
    {
        $this->attributes['sex'] = $value ?: 0;
    }

    /**
     * @return string
     */
    public function getSexTextAttribute(): string
    {
        $sex = $this->attributes['sex'] ?? 0;

        $data = [
            0 => '保密',
            1 => '男',
            2 => '女',
        ];

        return $data[$sex] ?? '保密';
    }

    /**
     * @param $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        if (empty($value)) {
            $value = '123456';
        }

        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * @param $value
     */
    public function setAvatarAttribute($value)
    {
        if ($value) {
            $this->attributes['avatar'] = AssetPath::toSavePath($value);
        }
    }

    /**
     * @param $value
     * @return string
     */
    public function getAvatarAttribute($value): string
    {
        if ($this->attributes['avatar'] ?? '') {
            return AssetPath::getVisitPath($this->attributes['avatar']);
        }

        return '';
    }
}
