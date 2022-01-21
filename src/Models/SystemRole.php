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
}
