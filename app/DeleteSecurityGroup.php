<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeleteSecurityGroup extends Model
{
    protected $table = 'delete_security_groups';

    protected $fillable = [
        'group_id',
        'group_name',
    ];
}
