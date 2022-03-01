<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;

/**
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $sort
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class SystemRole extends BaseModel
{
    protected $fillable = [
        'group',
        'name',
        'description',
        'sort',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->path)) {
                $model->path = '';
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

        $this->attributes['path'] = $value ?: '';
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
}
