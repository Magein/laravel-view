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

    protected $casts = [
        'path' => 'array',
        'role_id' => 'array'
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
        if (is_array($value)) {
            $value = array_values($value);
        }

        return $this->attributes['path'] = $value;
    }
}
