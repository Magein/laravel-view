<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;

/**
 * @property integer $user_id
 * @property string $permission_id
 * @property integer $role_id
 * @property string $theme
 *
 * @method static _userId($user_id);
 */
class UserSetting extends BaseModel
{
    protected $fillable = [
        'user_id',
        'permission_id',
        'role_id',
        'theme',
    ];

    protected $casts = [
        'permission_id' => 'array',
        'role_id' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->theme)) {
                $model->theme = '';
            }
            if (empty($model->permission_id)) {
                $model->permission_id = [];
            }
            if (empty($model->role_id)) {
                $model->role_id = [];
            }
        });
    }

    public function setRoleIdAttribute($value)
    {
        $this->attributes['role_id'] = $this->asIntJson($value);
    }

    public function setPermissionIdAttribute($value)
    {
        $this->attributes['permission_id'] = $this->asIntJson($value);
    }
}
