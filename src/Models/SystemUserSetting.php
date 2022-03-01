<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;

/**
 * @property integer $id
 * @property integer $user_id
 * @property string $path
 * @property integer $role_id
 * @property string $theme
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class SystemUserSetting extends BaseModel
{
    protected $fillable = [
        'user_id',
        'path',
        'role_id',
        'theme',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (empty($model->theme)) {
                $model->theme = '';
            }
            if (empty($model->path)) {
                $model->path = '';
            }
            if (empty($model->role_id)) {
                $model->role_id = '';
            }
        });
    }

    public function setPathAttribute($value)
    {
        if ($value && is_array($value)) {
            $value = array_unique($value);
            $value = array_filter($value);
            $value = implode(',', $value);
        }

        return $this->attributes['path'] = $value;
    }

    public function getPathAttribute($value)
    {
        if ($value && is_string($value)) {
            $value = explode(',', $value);
        } else {
            $value = [];
        }

        return $value;
    }

    public function setRoleIdAttribute($value)
    {
        if (is_array($value)) {
            $value = array_reduce($value, function ($res, $item) {
                $res[] = intval($item);
                return $res;
            });
        } else {
            $value = explode(',', $value);
        }

        $value = array_filter($value);
        $value = array_unique($value);
        $value = implode(',', $value);

        $this->attributes['role_id'] = $value ?: '';
    }

    public function getRoleIdAttribute($value)
    {
        if ($value && is_string($value)) {
            $value = explode(',', $value);
        } else {
            $value = [];
        }

        return $value;
    }
}
