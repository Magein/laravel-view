<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;

/**
 * @property integer $user_id
 * @property string $permission_id
 * @property integer $role_id
 * @property string $theme
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
        });
    }

    public function setRoleIdAttribute($value)
    {
        if (is_array($value)) {
            $value = array_filter($value);
            $value = array_unique($value);
            $value = $value ? array_reduce($value, function ($value, $item) {
                $value[] = intval($item);
                return $value;
            }) : [];
        } else {
            $value = [];
        }

        $this->attributes['role_id'] = $value;
    }

    public function setPermissionIdAttribute($value)
    {
        if (is_array($value)) {
            $value = array_filter($value);
            $value = array_unique($value);
            $value = array_reduce($value, function ($value, $item) {
                $value[] = intval($item);
                return $value;
            });
        } else {
            $value = [];
        }

        $this->attributes['permission_id'] = $value;
    }
}
