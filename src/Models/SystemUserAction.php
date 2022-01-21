<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;

/**
 * @property integer $id
 * @property integer $user_id
 * @property string $path
 * @property string $method
 * @property string $params
 * @property string $user_agent
 * @property string $ip
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class SystemUserAction extends BaseModel
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
            $agent = new Agent();
            $model->user_agent = $agent->getUserAgent();
            $model->ip = request()->ip();
            $model->path = request()->path();
            $model->method = request()->method();
            $model->params = request()->all() ? var_export(request()->all(), true) : '';
        });
    }
}
