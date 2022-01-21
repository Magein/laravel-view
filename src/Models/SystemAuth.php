<?php

namespace Magein\Admin\Models;

use Magein\Common\BaseModel;
use magein\tools\common\Variable;

/**
 * @property integer $id
 * @property string $group
 * @property string $name
 * @property string $path
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class SystemAuth extends BaseModel
{
    protected $fillable = [
        'group',
        'name',
        'path',
        'description',
    ];

    public function setGroupAttribute($value)
    {
        if ($value) {
            $this->attributes['group'] = trim(trim(Variable::instance()->pascal($value), '/'));
        }
    }

    public function setPathAttribute($value)
    {
        if ($value) {
            $this->attributes['path'] = trim(trim(Variable::instance()->pascal($value), '/'));
        }
    }

    public function setNameAttribute($value)
    {
        if ($value) {
            $this->attributes['name'] = trim($value);
        }
    }
}
