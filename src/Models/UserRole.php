<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;

/**
 * @property string $name
 * @property array $permission_id
 * @property string $description
 * @property string $sort
 */
class UserRole extends BaseModel
{
    protected $fillable = [
        'group',
        'name',
        'permission_id',
        'description',
        'sort',
    ];

    protected $casts = [
        'permission_id' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->permission_id)) {
                $model->permission_id = [];
            }
        });
    }

    public function setPermissionIdAttribute($value)
    {
        $this->attributes['permission_id'] = $this->asIntJson($value);
    }
}
