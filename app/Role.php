<?php

namespace App;

class Role extends BaseModel
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const ROLE_USER         = 'User';
    const ROLE_ADMIN        = 'Admin';

    public function scopeGetUserRole($query)
    {
        return $query->where('name', '=', self::ROLE_USER)->first();
    }

    public function scopeGetAdminRole($query)
    {
        return $query->where('name', '=', self::ROLE_ADMIN)->first();
    }
}
