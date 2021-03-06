<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;

/**
 * @property integer $user_id
 * @property string $path
 * @property string $method
 * @property string $params
 * @property string $user_agent
 * @property string $ip
 */
class UserAction extends BaseModel
{
    protected $fillable = [
        'user_id',
        'path',
        'method',
        'params',
        'user_agent',
        'ip',
    ];

    public static function booted()
    {

        static::creating(function ($model) {
            $model->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $model->ip = request()->ip();
            $model->path = request()->path();
            $model->method = request()->method();
            $model->params = request()->all() ? var_export(request()->all(), true) : '';
        });
    }
}
