<?php

namespace App;

class DeleteSecurityGroup extends BaseModel
{
    protected $table = 'delete_security_groups';

    protected $fillable = [
        'group_id',
        'group_name',
    ];
}
