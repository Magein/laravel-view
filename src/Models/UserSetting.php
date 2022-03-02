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
}
